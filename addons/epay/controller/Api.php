<?php

namespace addons\epay\controller;

use addons\epay\library\Service;
use app\admin\controller\pay\Order;
use app\admin\model\Admin;
use app\admin\model\pay\Type;
use app\admin\model\PayBill;
use app\admin\model\PayOrder;
use app\common\model\Log;
use Endroid\QrCode\QrCode;
use think\addons\Controller;
use think\Response;
use Yansongda\Pay\Pay;
use think\Config;
use think\Exception;
use tool\Request as ToolReq;

/**
 * API接口控制器
 *
 * @package addons\epay\controller
 */
class Api extends Controller
{

    protected $layout = 'default';
    protected $config = [];

    /**
     * 默认方法
     */
    public function index()
    {
        $this->error();
    }

    /**
     * 外部提交
     */
    public function submit()
    {


        $out_trade_no = $this->request->request("out_trade_no");
        $title = $this->request->request("title");
        $amount = $this->request->request('amount');
        $type = $this->request->request('type');
        $method = $this->request->request('method', 'web');
        $openid = $this->request->request('openid', '');
        $auth_code = $this->request->request('auth_code', '');
        /*$notifyurl = $this->request->request('notifyurl', '');
        $returnurl = $this->request->request('returnurl', '');*/

//        $logM->addLog($type,'api/submit/paytype');


        if (!$amount || $amount < 0) {
            $this->error("支付金额必须大于0");
        }


        if (!$type || !in_array($type, ['alipay', 'wechat'])) {
            $this->error("支付类型错误");
        }

        /*$params = [
            'type'         => $type,
            'out_trade_no' => $out_trade_no,
            'title'        => $title,
            'amount'       => $amount,
            'method'       => $method,
            'openid'       => $openid,
            'auth_code'    => $auth_code,
            'notifyurl'    => $notifyurl,
            'returnurl'    => $returnurl,
        ];*/
        $config = Config::get("payment");
        $notifyurl = $config['sys_notifyurl'];
        $returnurl = $config['sys_returnurl'];

//        return Service::submitOrder($params);
        return Service::submitOrder($amount, $out_trade_no, $type, $title, $notifyurl, $returnurl, $method);
    }

    /**
     * 微信支付扫码支付
     * @return string
     * @throws Exception
     * @throws \Yansongda\Pay\Exceptions\GatewayException
     * @throws \Yansongda\Pay\Exceptions\InvalidArgumentException
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     */
    public function wechat()
    {
        $config = Service::getConfig('wechat');

        $body = $this->request->request("body");
        $code_url = $this->request->request("code_url");
        $out_trade_no = $this->request->request("out_trade_no");
        $return_url = $this->request->request("return_url");
        $total_fee = $this->request->request("total_fee");

        $sign = $this->request->request("sign");

        $data = [
            'body'         => $body,
            'code_url'     => $code_url,
            'out_trade_no' => $out_trade_no,
            'return_url'   => $return_url,
            'total_fee'    => $total_fee,
        ];
        if ($sign != md5(implode('', $data) . $config['appid'])) {
            $this->error("签名不正确");
        }

        if ($this->request->isAjax()) {
            $wechat = Pay::wechat($config);
            $order = [
                'out_trade_no' => $out_trade_no
            ];
            $result = $wechat->find($order);
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
                $this->success("", "", ['trade_state' => $result->trade_state]);
            } else {
                $this->error("查询失败");
            }
        }
        $data['sign'] = $sign;
        $this->view->assign("isWechat", stripos($this->request->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false);
        $this->view->assign("isMobile", $this->request->isMobile());
        $this->view->assign("data", $data);
        $this->view->assign("title", "微信支付");
        return $this->view->fetch();
    }

    /**
     * 支付成功回调
     */
    public function notifyx()
    {


        $paytype = $this->request->param('paytype');
        $pay = Service::checkNotify($paytype);
        if (!$pay) {
            echo '签名错误';
            return;
        }
        //你可以在这里你的业务处理逻辑,比如处理你的订单状态、给会员加余额等等功能


        $notifyData = $pay->verify();
        /*$logM = new Log();
        $logM->addLog(json_encode($notifyData),'api/notifyx');*/
        try {

            $OrderM = new PayOrder();

            //支付宝
            if ($paytype == 'alipay')
            {
                //修改订单状态
                $out_trade_no = $notifyData['out_trade_no'];
                $myOrder = array();
                $myOrder['sys_order_id'] = $notifyData['trade_no'];
                $myOrder['status'] = 2;//已经支付
                $myOrder['paydate'] = $notifyData['notify_time'];
                $myOrder['paytime'] = strtotime($myOrder['paydate']);
                $where = array();
                $where['out_order_id'] = $out_trade_no;
                $where['status'] = array('in','0,1');
                $OrderM->where($where)->update($myOrder);

            }

            //订单详情
            $where = array();
            $where['out_order_id'] = $out_trade_no;
            $orderInfo = $OrderM->where($where)->find();
            if (!$orderInfo)
            {
                echo $pay->success();
                return;
            }

            //下发商户通知
            $result = \app\admin\library\Service::notify($orderInfo['id']);

            //扣除费率及记账
//            $this->dealServiceCharge($orderInfo);



            //你可以在此编写订单逻辑
        } catch (Exception $e) {

        }

        //下面这句必须要执行,且在此之前不能有任何输出

        echo $pay->success();
        return;
    }

    /**
     * 支付成功返回
     */
    public function returnx()
    {   /*$logM = new Log();
        $logM->addLog(json_encode($this->request->param()),'api/returnx');*/

        try {

            $paytype = $this->request->param('paytype');
            $pay = Service::checkReturn($paytype);
            if (!$pay) {
                $this->error('签名错误');
            }

            $returnData = $this->request->param();
            $out_order_id = $returnData['out_trade_no'];
            $OrderM = new PayOrder();
            $orderInfo = $OrderM->where('out_order_id',$out_order_id)->find();
            if ($orderInfo)
                $params = [
                    'appid'       => $orderInfo['appid'],
                    'paytype'       => $orderInfo['paytype'],
                    'title'       => $orderInfo['title'],
                    'out_order_id' => $orderInfo['out_order_id'],
                    'sys_order_id' => $orderInfo['sys_order_id'],
                    'realprice'    => $orderInfo['realprice'],
                    'paytime'      => $orderInfo['paytime'],
                    'paydate'      => $orderInfo['paydate'],
                    'extend'       => $orderInfo['extend'],
                ];
            $adminInfo = Admin::get($orderInfo['admin_id']);
            $appsecret = $adminInfo['appsecret'];
            $params['sign'] = md5(\app\admin\library\Service::build_sign_str($params).$appsecret);

            exit(ToolReq::createForm($orderInfo['returnurl'], $params,'GET'));


        } catch (Exception $e) {

        }

        //你可以在这里定义你的提示信息,但切记不可在此编写逻辑
//        $this->success("恭喜你！支付成功!", addon_url("epay/index/index"));
        $this->success("恭喜你！支付成功!","https://www.alipay.com/");
        return;
    }

    /**
     * 生成二维码
     * @return Response
     */
    public function qrcode()
    {
        $text = $this->request->get('text', 'hello world');
        $size = $this->request->get('size', 250);
        $padding = $this->request->get('padding', 15);
        $errorcorrection = $this->request->get('errorcorrection', 'medium');
        $foreground = $this->request->get('foreground', "#ffffff");
        $background = $this->request->get('background', "#000000");
        $logo = $this->request->get('logo');
        $logosize = $this->request->get('logosize');
        $label = $this->request->get('label');
        $labelfontsize = $this->request->get('labelfontsize');
        $labelhalign = $this->request->get('labelhalign');
        $labelvalign = $this->request->get('labelvalign');

        // 前景色
        list($r, $g, $b) = sscanf($foreground, "#%02x%02x%02x");
        $foregroundcolor = ['r' => $r, 'g' => $g, 'b' => $b];

        // 背景色
        list($r, $g, $b) = sscanf($background, "#%02x%02x%02x");
        $backgroundcolor = ['r' => $r, 'g' => $g, 'b' => $b];

        $qrCode = new QrCode();
        $qrCode
            ->setText($text)
            ->setSize($size)
            ->setPadding($padding)
            ->setErrorCorrection($errorcorrection)
            ->setForegroundColor($foregroundcolor)
            ->setBackgroundColor($backgroundcolor)
            ->setLogoSize($logosize)
            ->setLabelFontPath(ROOT_PATH . 'public/assets/fonts/fzltxh.ttf')
            ->setLabel($label)
            ->setLabelFontSize($labelfontsize)
            ->setLabelHalign($labelhalign)
            ->setLabelValign($labelvalign)
            ->setImageType(QrCode::IMAGE_TYPE_PNG);
        //也可以直接使用render方法输出结果
        //$qrCode->render();
        return new Response($qrCode->get(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }




    //记录到账信息，扣除手续费
    public function dealServiceCharge($orderInfo)
    {
        /*$payConfig = Config::get("payment");
        $payType = $orderInfo['paytype'];
        $OrderM = new PayOrder();
        //支付类型
        $PayTypeM  = new Type();
        $payTypeInfo = $PayTypeM->field('id,type,name,rate')->where('type',$payType)->find();
        $adminInfo = Admin::get($orderInfo['admin_id']);

        if ($payTypeInfo && $adminInfo)
        {


            $BillM = new PayBill();
            $rate = $payTypeInfo['rate'];//百分之...
            //订单支付记录
            $BillM->addBill($orderInfo['admin_id'],$orderInfo['realprice'],1,'订单:'.$orderInfo['out_order_id']);

            //订单手续费
            $rate_cost = $orderInfo['realprice'] * $rate*0.01*(-1);
            //单笔最低手续费（元）
            if (isset($payConfig['min_rate_sost']) && $payConfig['min_rate_sost'] >=($rate_cost*(-1)))
                $rate_cost = $payConfig['min_rate_sost']*(-1);
            $BillM->addBill($orderInfo['admin_id'],$rate_cost,2,'订单:'.$orderInfo['out_order_id']);


            //备注订单费率和费用
            $myOrder = array();
            $myOrder['cost'] = $rate_cost*(-1);
            $myOrder['rate'] = $rate;
            $where = array();
            $where['out_order_id'] = $orderInfo['out_order_id'];
            $OrderM->where($where)->update($myOrder);

        }*/
    }

    /*public function test()
    {
        $OrderM = new PayOrder();
        $where = array();
        $where['out_order_id'] = 'E201904081532521657';
        $orderInfo = $OrderM->where($where)->find();
        $res = $this->dealServiceCharge($orderInfo);
    }*/

}
