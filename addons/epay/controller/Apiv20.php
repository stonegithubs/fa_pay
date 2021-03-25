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
class Apiv20 extends Controller
{

    protected $layout = 'default';


    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }
  
    public function httpGETT($url,$data) {
        $url .= '?p1='.$data['p1'].'&timestamp='.$data['timestamp'];
        //echo 'url:'.$url."</br>";    //请求地址和参数：http://merchant-api.globalfastpayments.com/api/recharge/check/v2?p1=100&p2=11082429&p3=1553155910&timestamp=1553155910

        $str = $this->mkSign($data);
        //echo 'str:'.$str."</br>";   //加密前字符串：100&11082429&1553155910&1553155910
      	//echo json_encode($data) . "</br>";
        if(empty($str)){
            return false;
        }
        $sign = strtoupper($this->getSignature($str,$this->config['app_key']));  //加密后(大写)：5F51F8065B325EC3491526612CB2A47B84E5E10B
        //echo 'sign:'.$sign."</br>";
        $headers = array(
            'content-type:application/json',
            'access_key:'.$sign,
            'app_id:'.$this->config['app_id']
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
    }
  
    public function rmbPrice(){
       $param = array(
           'p1' => $this->config['mch_id'],
           'timestamp' => time()
       );
        $rlt = $this->httpGETT($this->config['rmb_price_url'],$param);
      	$rlt = json_decode($rlt,true);
		if(empty($rlt) || $rlt['code'] !== 200)
       {
  		var_dump($rlt);
  		exit;
		}
        return($rlt['data']['price']);

    }
  
   public function mkSign($data){
       if(!is_array($data)){
           return false;
       }
       $str = '';
       $flag = false;
       foreach ($data as $v){
           if(empty($v)){
               continue;
           }
           $str.=$v.'&';	
       }
       $str = substr($str,0,strlen($str)-1);
       return $str;
   }
    public function getSignature($str, $key) {
        $signature = "";
        if (function_exists('hash_hmac')) {
            $signature = bin2hex(hash_hmac("sha1", $str, $key, true));
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack(
                'H*', $hashfunc(
                    ($key ^ $opad) . pack(
                        'H*', $hashfunc(
                            ($key ^ $ipad) . $str
                        )
                    )
                )
            );
            $signature = bin2hex($hmac);
        }
        return $signature;
    }

    public function httpGET($url,$data) {
        $url .= '?p1='.$data['p1'].'&p2='.$data['p2'].'&p3='.$data['p3'].'&timestamp='.$data['timestamp'];
        //echo 'url:'.$url."</br>";    //请求地址和参数：http://merchant-api.globalfastpayments.com/api/recharge/check/v2?p1=100&p2=11082429&p3=1553155910&timestamp=1553155910

        $str = $this->mkSign($data);
        //echo 'str:'.$str."</br>";   //加密前字符串：100&11082429&1553155910&1553155910
      	//echo json_encode($data) . "</br>";
        if(empty($str)){
            return false;
        }
        $sign = strtoupper($this->getSignature($str,$this->config['app_key']));  //加密后(大写)：5F51F8065B325EC3491526612CB2A47B84E5E10B
        //echo 'sign:'.$sign."</br>";
        $headers = array(
            'content-type:application/json',
            'access_key:'.$sign,
            'app_id:'.$this->config['app_id']
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
    }
  
    public function submit()
    {

       try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v19/submit');
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
        $paytype = 'bank_v01';
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


        $p2 = $this->config['mch_id'];
      	//$version = 'v1.0';
      	//$signType = 'md5';
        $p3 = $out_trade_no;    //订单号
      	//$requestNo = $out_trade_no;
      	$rice = $this->rmbPrice();
        $p1 = $amount/$rice;    //交易金额
        $timestamp = time();  //订单时间
        //$notifyUrl =  $this->config['pay_notifyurl'];   //服务端返回地址
        //$pay_callbackurl = $this->config['pay_callbackurl'];  //页面跳转返回地址
        $app_key = $this->config['app_key'];   //密钥
        $into_url = $this->config['into_url'];   //提交地址
        //$payType = $this->config['payType'];    //银行编码
      	//$serviceType = $this->config['serviceType'];    //
        $native = array(
            "p1" => $p1,
            "p2" => $p2,
            "p3" => $p3,
            "timestamp" => $timestamp
            
        );
       
      	
       	$res = $this->httpGET($into_url, $native);
//      	var_dump($res);
//      	var_dump($rice);
//      	die();
      	$res = json_decode($res,true);
      	
      	//var_dump($res);
      	//die();
      	if(empty($res) || $res['code'] !== 200)
       {
  		var_dump($res);
  		exit;
		}
//	$res['payurl'] = isset($res['payurl'])?$res['payurl']:"1";
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
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v19/notifyx');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
       
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'bank_v01')->find();
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
        $payData['amount'] = $this->request->param('amount','');
        $payData['exchangeRate'] = $this->request->param('exchangeRate','');
        $payData['poundage'] = $this->request->param('poundage','');
        $payData['merchantNo'] = $this->request->param('merchantNo','');
        $payData['merchantOrderNo'] = $this->request->param('merchantOrderNo','');
        $payData['orderNo'] = $this->request->param('orderNo','');
      	$payData['timestamp'] = $this->request->param('timestamp','');
      	$rice = $this->rmbPrice();
      	
      	

        /*if(empty($payData['datetime']))
        {
        	die(500);
        }*/

        $app_key = $this->config['app_key'];
        //$logM->addLog($Md5key,'api_v19/notifyx_Md5key');
        $str = $this->mkSign($payData);
        $payData['sign'] = strtoupper($this->getSignature($str,$app_key));
        $logM->addLog($payData['sign'],'api_v19/notifyx_sign');
        $payData['signature'] = $this->request->param('sign','');
        $logM->addLog($payData['signature'],'api_v19/notifyx_signature');
        if ($payData['sign']===$payData['signature'])//
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['merchantOrderNo'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['orderNo'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = date('Y-m-d H:i:s',$payData['timestamp']);
                    $myOrder['realprice'] = $payData['amount']*$rice;
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
                  exit("code:200");
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
            $payTypeInfo = $PayTypeM->where('type', 'bank_v01')->find();
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
