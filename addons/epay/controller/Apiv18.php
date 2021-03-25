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
class Apiv18 extends Controller
{

    protected $layout = 'default';


    protected $config = [];
  	
  	public function curlGet($url,$data = '',$timeout=0){
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	if($timeout>0){
        	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    					}
    	curl_setopt($ch, CURLOPT_FAILONERROR, false);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_POST, true);
    	if(is_array($data)){
        	$data=http_build_query($data);
    						}
    	curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    	$headers = array('content-type: application/x-www-form-urlencoded;');
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	$reponse = curl_exec($ch);
    	curl_close($ch);
    	return $reponse;
		}
  	
  	public function getsign($data){
	$key = $this->config['Md5key'];  //密钥
	
	return md5(json_encode($data).$key);
	}
  	//回调验签
	public function unsign($return){
	$key=$this->config['Md5key'];  //密钥	
	$return["data"]=base64_decode(urldecode($return["data"]));
	if(md5($return["data"].$key)==$return["sign"]){
		return true;
	}
	return false;
}

    public function _initialize()
    {
        parent::_initialize();
    }

    public function submit()
    {
      
       try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v18/submit');
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


        $merchantCode = $this->config['merchantCode'];//商户号
      	$version = $this->config['version'];//版本号
        $orderId = $out_trade_no;    //订单号
        $amount = $amount;    //交易金额
        //$pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $notifyUrl =  $this->config['notifyUrl'];   //服务端返回地址
        //$pay_callbackurl = $this->config['sys_returnurl'];  //页面跳转返回地址
        $Md5key = $this->config['Md5key'];   //密钥
//        $apiUrl = $this->config['apiUrl'];   //提交地址
        $way = $this->config['way'];    //银行编码
        $native = array(
            "orderId" => $orderId,
            "amount" => $amount,
            "version" => $version,
            "merchantCode" => $merchantCode,
            "body" => $title,
            "notifyUrl" => $notifyUrl,
            
        );
        	$params=array(
			"data"=>urlencode(base64_encode(json_encode($native))),
			"sign"=>$this->getsign($native),
						);
      		$return = $this->curlGet($this->config['apiUrl'],$params);
      		$return = json_decode($return,true);
      		if($return["success"]==true){
              $return["data"]=json_decode(base64_decode(urldecode($return["data"])),true);
              $tranId = $return["data"]['tranId'];
              //var_dump($tranId);
      			//die();
            
      		$native1 = array(
            "version" => $version,
            "merchantCode" => $merchantCode,
            "way" => $way,
            "tranId" => $tranId,
        	);
              //var_dump($native1);
      		//die();
      		$params1=array(
              "data"=>urlencode(base64_encode(json_encode($native1))),
              "sign"=>$this->getsign($native1),
              );
              //var_dump($params1);
              //die();
      		$res = $this->curlGet($this->config['apiUrl1'],$params1);
              //var_dump($res);
              //die();
      		$res = json_decode($res,true);
      		if($res["success"]==true){
              $res["data"]=json_decode(base64_decode(urldecode($res["data"])),true);
              $res1 = json_decode($res['data']['payParams'],true);
              
              	//var_dump($res1['codeUrl']);
      			//die();
              header("Location:" . $res1['codeUrl']);
            }}

		
        

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {

        $logM = new Log();
      	$ipaddr = ToolReq::getIp();
        $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v18/notifyx');
        //$logM->addLog(json_encode($this->request->param()),'api_v18/notifyx');
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
        $payData['sign'] = $this->request->request('sign','');
        $payData['data'] = $this->request->request('data','');
       


        $Md5key = $this->config['Md5key'];
      	$payData['data']=base64_decode(urldecode($payData['data']));
      	$sign = md5($payData['data'].$Md5key);
      	//$payData1 = array();
      	$payData1 = json_decode($payData['data'],true);
      	
		//$logM->addLog($sign ,'sign');
      	//$logM->addLog($payData1['orderId'] ,'payData1');
        if ($sign===$payData['sign'])//
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v18/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData1['orderId'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData1['tranId'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = date("Y-m-d H:i:s");
                    $myOrder['realprice'] = $payData1['amount']/100;
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
                  	$arr = array ('success'=>true);
                  	exit (json_encode($arr));
                } catch (Exception $e) {

                }

            }
          	
            exit('ok');
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
        $logM->addLog(json_encode($this->request->param()),'api_v18/returnx');

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
