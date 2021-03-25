<?php

namespace addons\epay\controller;

use addons\epay\library\Service;
use app\admin\model\PayOrder;
use app\common\model\Log;
use think\addons\Controller;
use Exception;
use KTools\KTools;
use tool\Request as ToolReq;
use app\admin\model\pay\Type;
use think\exception\DbException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use app\admin\model\Admin;


//http://118.31.109.125/nbpay/a/login
//NB pay

class Apiv14 extends Controller
{

    protected $layout = 'default';

    protected $config = [];

    protected $paytype = 'alipay_v14';//支付标识

    protected $token = null;

    public function _initialize()
    {
        parent::_initialize();


    }


 /*   public function index()
    {
        $apiUrl = 'http://118.31.109.125/nbpay/pay/getway/';
        $randomString = date('YmdHis').time();
        $secretKey = 'hiegu98kjgo45';

        $postData = array();
        $postData['sign'] =md5( md5($apiUrl.$randomString).$secretKey);
        $postData['businessId'] = '1133719903038963712';
        $postData['randomString'] = $randomString;
        $postData['businessOrderId'] = 'E'.date('YmdHis').time();
        $postData['description'] = 'goods';
        $postData['extrat'] = 'good goods';
        $postData['money'] = 1;
        $postData['payType'] = 'alipay';
        $postData['notifyUrl'] = 'http://www.xhd9.com/api/index';


        $res = $this->request_post($apiUrl,$postData);
        $resObj = json_decode($res,true);
        if (isset($resObj['status']) && $resObj['status']==0 && isset($resObj['alipayUrl']))
        {
            $order = array();
            $order['qrcode'] = $resObj['qrcode'];
            $order['alipayUrl'] = $resObj['alipayUrl'];
            $order['out_order_id'] = $postData['businessOrderId'];
            $this->view->assign("order", $order);
            return $this->view->fetch('pay');
        }else{
            echo $res;
        }

    }*/

    public function submit()
    {

        $out_trade_no = $this->request->request("out_trade_no");
        $amount = $this->request->request('amount');
        $title = $this->request->request("title");

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        $paytype = $this->paytype;
        try {
            $payTypeInfo = $PayTypeM->where('type', $paytype)->where('status',1)->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
        {
            $this->error('支付通道不可用');
        }
        $payTypeInfo['config'] = json_decode($payTypeInfo['config'],true);
        $this->config = $payTypeInfo['config'];


        $apiUrl = $this->config['apiUrl'];
        $randomString = date('YmdHis').time();
        $secretKey = $this->config['secretKey'];

        $postData = array();
        $postData['sign'] =md5( md5($apiUrl.$randomString).$secretKey);
        $postData['businessId'] = $this->config['businessId'];
        $postData['randomString'] = $randomString;
        $postData['businessOrderId'] = $out_trade_no;
        $postData['description'] = $title;
        $postData['extrat'] = $title;
        $postData['money'] = $amount;
        $postData['payType'] = 'alipay';
        $postData['notifyUrl'] = $this->config['sys_notifyurl'];

        var_dump($apiUrl);
      exit;
        $res = $this->request_post($apiUrl,$postData);
        $resObj = json_decode($res,true);
        if (isset($resObj['status']) && $resObj['status']==0 && isset($resObj['alipayUrl']))
        {
            $order = array();
            $order['qrcode'] = $resObj['qrcode'];
            $order['alipayUrl'] = $resObj['alipayUrl'];
            $order['out_order_id'] = $postData['businessOrderId'];
            $this->view->assign("order", $order);
            return $this->view->fetch('pay');
        }else{
            echo $res;
        }

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {

        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),$this->paytype.'/notifyx');
        $logM->addLog( $_SERVER['REMOTE_ADDR'],$this->paytype.'/notifyx/ip');

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', $this->paytype)->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
        {
            $this->error('支付通道不可用');
        }
        $payTypeInfo['config'] = json_decode($payTypeInfo['config'],true);
        $this->config = $payTypeInfo['config'];


        /*{
            "memberid": "10076",
            "orderid": "E201905052140126956",
            "transaction_id": "20190505214012995651",
            "amount": "1.0000",
            "datetime": "20190505214156",
            "returncode": "00",
            "sign": "593E0B64D40EED3BB9DD0B7CEF0E8DDF",
            "attach": "1234|456",
            "addon": "epay",
            "controller": "apiv3",
            "action": "notifyx"
        }*/

        $payData = array();
        $payData['out_trade_no'] = $this->request->request('out_trade_no','');
        $payData['realMoey'] = $this->request->request('amount',0);

        if (isset($payData['out_trade_no']) &&  $payData['realMoey'] > 0)
        {
          if (sign($this->config['secretKey'], ['amount'=>$amount,'out_trade_no'=>$payData['out_trade_no']]) != $sign) 
            {
                try {

                    $logM->addLog('ok',$this->paytype.'/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['out_trade_no'];
                    $myOrder = array();
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paytime'] = time();
                    $myOrder['paydate'] = date('Y-m-d H:i:s',$myOrder['paytime']);
                    $myOrder['realprice'] = $payData['realMoey'];
                    $where = array();
                    $where['out_order_id'] = $out_trade_no;
                    $where['status'] = array('in','0,1');
                    $OrderM->where($where)->update($myOrder);

                    //订单详情
                    $where = array();
                    $where['out_order_id'] = $out_trade_no;
                    $orderInfo = $OrderM->where($where)->find();


                    //下发商户通知
                    $result = \app\admin\library\Service::notify($orderInfo['id']);

                    $APiC = new Api();
                    //扣除费率及记账
                    $APiC->dealServiceCharge($orderInfo);



                    //你可以在此编写订单逻辑
                } catch (Exception $e) {

                }

            }
            exit("ok");
        }else{
            $this->error('error');
        }
    }


    public function request_post($url = '', $param = '') {

        $headers = array('Content-Type: application/x-www-form-urlencoded');
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
//            echo 'Errno'.curl_error($curl);//捕抓异常
            return false;
        }
        curl_close($curl); // 关闭CURL会话
        return $result;
    }
}
