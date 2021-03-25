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


/**
 * Class Apiv2 for cpay
 * @package addons\epay\controller
 */
class Apiv14 extends Controller
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
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v14/submit');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
      
        $out_trade_no = $this->request->request("out_trade_no");
        $amount = $this->request->request('amount');
        $title = $this->request->request("title");

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        $paytype = 'alipay_v14';
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


        $mch_id = $this->config['mch_id'];
      	//$mch_id = 1000395;
        $out_trade_no = $out_trade_no;    //订单号
        $total_fee = $amount;    //交易金额
        $timestamp = time();  //订单时间
        $notify_url =  urlencode($this->config['pay_notifyurl']);   //服务端返回地址
        $child_type = "H5";  //页面跳转返回地址
        $mch_secret = $this->config['Md5key'];   //密钥
//        $apiUrl = $this->config['apiUrl'];   //提交地址
        $pay_type = $this->config['pay_type'];    //银行编码
        $native = array(
            "mch_id" => $mch_id,
          	"child_type" => $child_type,
            "out_trade_no" => $out_trade_no,
          	"pay_type" => $pay_type,
            "total_fee" => $total_fee,
            "notify_url" => $notify_url,
            "timestamp" => $timestamp,
            
        );
      	$nativeSign = $native;
      	$nativeSign['mch_secret'] = $mch_secret;
        ksort($nativeSign);
        $md5str = "";
        foreach ($nativeSign as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = substr($md5str,0,strlen($md5str)-1);
        $sign =strtoupper(md5($md5str));
        $native["sign"] = $sign;
        //$native['pay_attach'] = "";
        //$native['pay_productname'] =$title;
      	//var_dump($md5str);
      	//die();

        //exit(ToolReq::createForm($this->config['apiUrl'], $native));
      	$data = "";
       	$res = ToolReq::post2($this->config['apiUrl'], $native);
      	//var_dump($res);
      	//die();
      	$res = json_decode($res,true);
      	if(empty($res) || $res['code'] != 100)
       {
  		var_dump($data);
  		exit;
		}

	header("Location:" . $res['data']['url']);

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {
		 try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v14/notifyx');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
       
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'alipay_v14')->find();
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
        $payData['mch_id'] = $this->request->request('mch_id','');
        $payData['out_trade_no'] = $this->request->request('out_trade_no','');
        $payData['order_no'] = $this->request->request('order_no','');
        $payData['total_fee'] = $this->request->request('total_fee','');
        $payData['pay_time'] = $this->request->request('pay_time','');
        $payData['timestamp'] = $this->request->request('timestamp','');

        /*if(empty($payData['datetime']))
        {
        	die(500);
        }*/

        $payData['mch_secret'] = $this->config['Md5key'];
        ksort($payData);
        reset($payData);
        $md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = substr($md5str,0,strlen($md5str)-1);
      	
        $payData['sign'] = strtoupper(md5($md5str));
        $sign = $this->request->request('sign','');
        if ($sign===$payData['sign'])//
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['out_trade_no'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['order_no'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = $payData['pay_time'];
                    $myOrder['realprice'] = $payData['total_fee'];
                    $myOrder['paytime'] = strtotime($myOrder['paydate']);
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
            $payTypeInfo = $PayTypeM->where('type', 'alipay_v14')->find();
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



}
