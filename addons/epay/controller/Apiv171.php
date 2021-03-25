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
class Apiv17 extends Controller
{

    protected $layout = 'default';


    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }
  	public function httpPost($url,$postData,$type){
		 //初使化init方法
	   $ch = curl_init();
	   //指定URL
	   curl_setopt($ch, CURLOPT_URL, $url);
	   //设定请求后返回结果
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   //声明使用POST方式来进行发送
	   curl_setopt($ch, CURLOPT_POST, 1);
	   //发送什么数据呢
	   curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	   //忽略证书
	   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	   //忽略header头信息
	   curl_setopt($ch, CURLOPT_HEADER, 0);
	   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	            'Content-Type: application/json; charset=utf-8'
	        )
	    );
	   //设置超时时间
	   curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	   //发送请求
	   $output = curl_exec($ch);
	   //关闭curl
	   curl_close($ch);
	   //返回数据
	   return $output;
	}

    public function submit()
    {

       try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v17/submit');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
      
        $out_trade_no = $this->request->request("out_trade_no");
        $amount = $this->request->request('amount');
        $title = $this->request->request("title");
      	$key = $this->request->request("key");
        if(empty($key) || $key != '123')
        {
         $this->error('支付通道不可用');
        }

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        $paytype = 'alipay_v17';
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


        $merNo = $this->config['merNo'];
      	$version = 'v1.0';
      	$signType = 'md5';
        $merOrderNo = $out_trade_no;    //订单号
      	$requestNo = $out_trade_no;
        $tradeAmt = $amount;    //交易金额
        //$pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $notifyUrl =  $this->config['pay_notifyurl'];   //服务端返回地址
        //$pay_callbackurl = $this->config['pay_callbackurl'];  //页面跳转返回地址
        $Md5key = $this->config['Md5key'];   //密钥
        $apiUrl = $this->config['apiUrl'];   //提交地址
        $payType = $this->config['payType'];    //银行编码
      	$serviceType = $this->config['serviceType'];    //
        $native = array(
            "merNo" => $merNo,
            "merOrderNo" => $merOrderNo,
            "tradeAmt" => $tradeAmt,
            "requestNo" => $requestNo,
            "notifyUrl" => $notifyUrl,
            "payType" => $payType,
            "serviceType" => $serviceType,
          	"version" => $version,
          	"signType" => $signType,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = substr($md5str,0,strlen($md5str)-1);
        $signature = md5($md5str . $Md5key);
        $native["signature"] = $signature;
        //$native['pay_attach'] = "";
        //$native['pay_productname'] =$title;
      	//var_dump($native);
      	//die();

        //exit(ToolReq::createForm($this->config['apiUrl'], $native));
      	//$data = "";
       	$res = $this->httpPost($apiUrl, json_encode($native),'POST');
      	
      	$res = json_decode($res,true);
      	
      	//var_dump($res);
      	//die();
      	if(empty($res) || $res['respCode'] !== "P000")
       {
  		var_dump($res);
  		exit;
		}
	//$res['payurl'] = isset($res['payurl'])?$res['payurl']:"1";
	header("Location:" . $res['payUrl']);

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {
		 try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v17/notifyx');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
       
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'alipay_v17')->find();
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
        $payData['actualPayAmt'] = $this->request->param('actualPayAmt','');
        $payData['merNo'] = $this->request->param('merNo','');
        $payData['merOrderNo'] = $this->request->param('merOrderNo','');
        $payData['notifyType'] = $this->request->param('notifyType','');
        $payData['orderNo'] = $this->request->param('orderNo','');
        $payData['payTime'] = $this->request->param('payTime','');
      	$payData['payType'] = $this->request->param('payType','');
      	$payData['respCode'] = $this->request->param('respCode','');
      	$payData['respDesc'] = $this->request->param('respDesc','');
      	$payData['tradeAmt'] = $this->request->param('tradeAmt','');
      	
      	

        /*if(empty($payData['datetime']))
        {
        	die(500);
        }*/

        $Md5key = $this->config['Md5key'];
        //$logM->addLog($Md5key,'api_v17/notifyx_Md5key');
        ksort($payData);
        reset($payData);
        $md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        //$md5str = rtrim($md5str,"&");
      	$md5str = substr($md5str,0,strlen($md5str)-1);
        //$logM->addLog($md5str,'api_v17/notifyx');
      	//actualPayAmt=1.00&merNo=10060066&merOrderNo=E202002080312433796&notifyType=platform&orderNo=P20020803124481869&payTime=2020-02-08 03:13:01&payType=A57&respCode=0000&respDesc=交易成功&tradeAmt=1.00
        //$logM->addLog($md5str . $Md5key,'api_v17/notifyx');
        $payData['sign'] = md5($md5str . $Md5key);
        //$logM->addLog($payData['sign'],'api_v17/notifyx_sign');
        $payData['signature'] = $this->request->param('signature','');
        //$logM->addLog($payData['signature'],'api_v17/notifyx_signature');
        if ($payData['sign']===$payData['signature'])//
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['merOrderNo'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['orderNo'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = $payData['payTime'];
                    $myOrder['realprice'] = $payData['actualPayAmt'];
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
                  exit("SUCCESS");
                } catch (Exception $e) {

                }

            }
            exit("error");
        }else{
            $this->error('eerror');
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
            $payTypeInfo = $PayTypeM->where('type', 'alipay_v17')->find();
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
