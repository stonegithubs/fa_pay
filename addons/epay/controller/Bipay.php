<?php

namespace addons\epay\controller;

use fast\Http;
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


/**
 * Class Apiv2 for cpay
 * @package addons\epay\controller
 */
class Bipay extends Controller
{

    protected $layout = 'default';

    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }


    public function submit()
    {
        try {
            $logM = new Log();
            $ipaddr = ToolReq::getIp();
            $logM->addLog($ipaddr."|".json_encode($this->request->param()),'bipay/submit');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }

        $from_address = $this->request->request('from_address','');
        $out_order_id = $this->request->request('out_order_id');

        //转出地址不能为空
        if (empty($from_address)){
            $this->error("转出地址不能为空");
        }


        $this->result('http://'.$_SERVER['HTTP_HOST'].'/index/payment?id='.$out_order_id,1,'请求成功','json');
    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {
        $params = $this->request->request();

        if(!isset($params['sign'])){
            exit(json_encode(['code'=>-1,'msg'=>'sign not set']));
        }

        $sign = $params['sign'];

        $params_final = $this->filterPara($params);

        $sign_str =  $this->buildRequestMysign($params_final);

        if ($sign_str !== $sign ) {
            exit(json_encode(['code'=>-1,'msg'=>'sign error']));
        }

        $notify_type = $params['notify_type'];
//        $from_address= $params['from_address'];
        $to_address= $params['address'];
        $amount = $params['amount'];
        $txid = $params['txid']; //链上交易ID
        $coin_symbol = $params['coin_symbol'];
        $fee = $params['fee'];

        try {
            //$logM->addLog('ok','api_v3/notifyx');
            $OrderM = new PayOrder();

            //修改订单状态
            $myOrder = array();
            $myOrder['status'] = 2;//已经支付
            $myOrder['paydate'] = date('Y-m-d H:i:s',time());
            $myOrder['realprice'] = $amount;
            $myOrder['paytime'] = time();
            $where = array();
            $where['txid'] = $txid;
            $where['status'] = array('in','0,1');
            $OrderM->where($where)->update($myOrder);

            //订单详情
            $where = array();
            $where['txid'] = $txid;
            $orderInfo = $OrderM->where($where)->find();

            //下发商户通知
            $result = \app\admin\library\Service::notify($orderInfo['id']);

            $APiC = new Api();
            //扣除费率及记账
            $APiC->dealServiceCharge($orderInfo);



            //你可以在此编写订单逻辑
            exit(json_encode(['code'=>0,'msg'=>'success']));
        } catch (Exception $e) {

        }
    }

    /**
     * 支付成功返回
     */
    public function returnx()
    {

        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),'api_v3/returnx');

        $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 流水号
            "returncode" => $_REQUEST["returncode"]
        );
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'alipay_v20')->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
            $this->error('error');
        $md5key = $payTypeInfo['Md5key'];
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $md5key));
        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
                try {
                    $returnData = $this->request->param();
                    $out_order_id = $returnData['orderid'];
                    $OrderM = new PayOrder();
                    $orderInfo = $OrderM->where('out_order_id',$out_order_id)->find();
                    if (!$orderInfo)
                        $this->error('error');
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

            }
        }
        return;
    }

    // curl for request
    public  function curl_post($url, $post_data = '', $timeout = 5)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if ($post_data != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function paraFilter($para) {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if($key == "sign" || $val == "")continue;
            if($key == "s" || $val == "")continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    public function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function createLinkstring($para) {
        $arg  = "";
        foreach ($para as $key => $val) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,strlen($arg)-1);
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }
        return $arg;
    }
    /**
     * 生成md5签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    public function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    public function filterPara($para_temp){
        $para_filter = $this->paraFilter($para_temp);//除去待签名参数数组中的空值和签名参数
        return $this->argSort($para_filter);//对待签名参数数组排序
    }
    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * @return string 签名结果字符串
     */
    public function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        $mysign = "";
        $mysign = $this->md5Sign($prestr, $this->config['md5key']);

        return $mysign;
    }
    /**
     * 生成要发送的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    public function buildRequestPara($para_temp) {
        $para_sort = $this->filterPara($para_temp);//对待签名参数进行过滤
        $para_sort['sign'] = $this->buildRequestMysign($para_sort);//生成签名结果，并与签名方式加入请求提交参数组中
        return $para_sort;
    }
}
