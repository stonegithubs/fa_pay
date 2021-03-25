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
class Apiv7 extends Controller
{

    protected $layout = 'default';


    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }
  	public function create_password($pw_length)
	{ 
	$randpwd =''; 
	for ($i = 0; $i < $pw_length; $i++) 
	{ 
	$randpwd .= chr(mt_rand(97, 126)); 
	} 
	return $randpwd; 
	} 

    public function submit()
    {

       try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v7/submit');
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
        $paytype = 'alipay_wap_v5';
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


        $coopId = $this->config['coopId'];
      	//$version = '1.0';//版本号，version取值是1.0
        $outOrderNo = $out_trade_no;    //订单号
		$subject = $title;   //商品描述
      	//$sign_type = $this->config['sign_type'];
      	//$nonce_str = $this->create_password(32);//随机字符串
      	//$charset = 'UTF-8';//可选值 UTF-8，默认为UTF-8
        $money = $amount;    //交易金额
        //$timestamp = time();  //订单时间
        $notifyUrl =  $this->config['pay_notifyurl'];   //服务端返回地址
        //$return_url = $this->config['pay_callbackurl'];  //页面跳转返回地址
        $mch_secret = $this->config['Md5key'];   //密钥
        $pathType = $this->config['pathType'];    //银行编码
        $native = array(
            "coopId" => $coopId,
          	"outOrderNo" => $outOrderNo,
            "subject" => $subject,
          	"money" => $money,
            "notifyUrl" => $notifyUrl,
            "pathType" => $pathType,
            
          	
            
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = substr($md5str,0,strlen($md5str)-1);
        $sign =md5($md5str . $mch_secret);
        $native["sign"] = $sign;
        //$native['pay_attach'] = "";
        //$native['pay_productname'] =$title;
      	//var_dump($sign);
      	//die();

        //exit(ToolReq::createForm($this->config['apiUrl'], $native));
      	$data = "";
       	$res = ToolReq::post2($this->config['apiUrl'], $native);
      	//var_dump($res);
      	//die();
      	//echo($res);
      	$res = json_decode($res,true);
      	if(empty($res) || $res['code'] != 0)
       {
          
          var_dump($res);
  			exit;
		}
		
		header("Location:" . $res['payurl']);
      	//echo($res);
      /*if(!empty($res) && $res['code'] == 100)
       {
  		$res = json_encode($res['data'],true);
        echo($res);
		}else{
      	var_dump($res);
  		exit;
      	}*/
		

    }


 /* public function submit_b($out_trade_no="0",$amount="0",$title='',$key=0)
    {

       try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v7/submit');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
      
        //$out_trade_no = $this->request->request("out_trade_no");
        //$amount = $this->request->request('amount');
        //$title = $this->request->request("title");
		//$key = $this->request->request("key");
        if(empty($key) || $key != '123')
        {
         $this->error('支付通道不可用');
        }
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        $paytype = 'alipay_wap_v5';
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

      	$data = "";
       	$res = ToolReq::post2($this->config['apiUrl'], $native);

      	echo($res);

    }
  
    /**
     * 支付成功回调
     */
    public function notifyx()
    {
		 try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v7/notifyx');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
       
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'alipay_wap_v5')->find();
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
        $payData['code'] = $this->request->param('code','');
        $payData['orderNo'] = $this->request->param('orderNo','');
        $payData['outOrderNo'] = $this->request->param('outOrderNo','');
        $payData['subject'] = $this->request->param('subject','');
        $payData['money'] = $this->request->param('money','');
        $payData['orderStatus'] = $this->request->param('orderStatus','');
      	$payData['pathType'] = $this->request->param('pathType','');
      	$payData['payTime'] = $this->request->param('payTime','');
      	//$payData['msg'] = $this->request->param('msg','');
      	
        /*if(empty($payData['datetime']))
        {
        	die(500);
        }*/

        $Md5key = $this->config['Md5key'];
        ksort($payData);
        //reset($payData);
        $md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = substr($md5str,0,strlen($md5str)-1);
        $signtemp =md5($md5str . $Md5key);
      	    //$logM->addLog($md5str,'api_v4/notifyx');  	
        //$signtemp = strtoupper(md5($md5str . "key=" . $Md5key));
      
      	//$logM->addLog($payData['sign'],'api_v3/notifyx');
        $sign = $this->request->param('sign','');
      	$money = $payData['money']/100;
        if ($sign===$signtemp)//
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['outOrderNo'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['orderNo'];
                  	
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = date("Y-m-d H:i:s",$payData['payTime']);
                  	//$logM->addLog($payData['time_end'],'api_v6/notifyx');
                    $myOrder['realprice'] = $money;
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
                  	exit("success");
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
            $payTypeInfo = $PayTypeM->where('type', 'alipay_wap_v5')->find();
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
