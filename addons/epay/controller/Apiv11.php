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
use tool\rsaa;
use tool\curlrequest;

class Rsa{
    //private_key 私钥;
    public $private_key = '-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAPnChWoVra6YUr+h
JjINLwNWFLgvg0eTBmIBsRyEHiupZkqZeiWq7n9vUQhohUCrz+PWuEIAe9EO4ezb
szqURTx8F9ByPthUohsgaczWr0eJNvj8lnoPOrWJyK428I5iGjJaTtvOzUeR2H73
wNo8ZD1C5Z6EYVuW/V4zU3Jtiu6DAgMBAAECgYEAnJbR0LYww3NrBgxCB0VuwVe5
+9SGKVzLtqy631cSF2vI32KkS3OEvk8Lbgsh6G8QExfvRCpLdsIu8bK5BzQox0ss
V2DjRESVIBwyXRBMakPN1Vo8TbrNgKa26vSsRGhFZICKdc0Yr0kA3SUJoG9ar/G8
4CWdC3pJj4VAENCLCKECQQD/Oq4mUHIfyk+Y3oe1UWYdOmwRhH6ljHZuSFGPcRJc
giingquTyE2p0qO8oLJQGxptfNeCsWtDNExrZPKqGggTAkEA+oOcymdmAHMCFKWM
U7SHdcSB2kgRdiI8EM55Z1+gxxklKygCc9ZjqA+WWThCImIfzyrl4ZyxTHiBcWgf
M4Ut0QJBALlCDLp+1ffBT7l0fSjdZrN8fojQlWTw6d3u3FS0DFHdoEjGjmf8knLc
FEGMmyGOKsaiQYP56BOl2HpzkbhqoMUCQEfCbaZZChH039K0PUc4/liQyrWRUVcq
pVQXIRWogfCmVkxPcKxn7DIXDPVPtToOK5h3bFQ9Q1hpaILo1Y83hhECQQDZgifA
DfahURIokdVRc/s/8jiyj7ap3NIKmvTNJPuoT+nekRYrruoVmz1ePi0QZ/R98VwB
vhZlAOAGx1RsAO+D
-----END PRIVATE KEY-----';

    //public_key 公钥
    public $public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQD5woVqFa2umFK/oSYyDS8DVhS4
L4NHkwZiAbEchB4rqWZKmXolqu5/b1EIaIVAq8/j1rhCAHvRDuHs27M6lEU8fBfQ
cj7YVKIbIGnM1q9HiTb4/JZ6Dzq1iciuNvCOYhoyWk7bzs1Hkdh+98DaPGQ9QuWe
hGFblv1eM1NybYrugwIDAQAB
-----END PUBLIC KEY-----';

    public $pi_key;
    public $pu_key;

    //判断公钥和私钥是否可用
    public function __construct()
    {
        $this->pi_key =  openssl_pkey_get_private($this->private_key); //这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        $this->pu_key = openssl_pkey_get_public($this->public_key); //这个函数可用来判断公钥是否是可用的
    }

    //公钥加密
    public function PublicEncrypt($data){
        //openssl_public_encrypt($data,$encrypted,$this->pu_key);/ /公钥加密
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $this->pu_key);
            $crypto .= $encryptData;
        }
        $encrypted = $this->urlsafe_b64encode($crypto);
        return $encrypted;
    }

    //公钥解密  私钥加密的内容通过公钥解密
    public function PublicDecrypt($encrypted){
        //$encrypted = $this->urlsafe_b64decode($encrypted);
        $crypto = '';
        foreach (str_split($this->urlsafe_b64decode($encrypted), 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $this->pu_key);
            $crypto .= $decryptData;
        }
        //openssl_public_decrypt($encrypted,$decrypted,$this->pu_key); //私钥加密的内容通过公钥可用解密出来
        return $crypto;
    }

    //私钥加密
    public function PrivateEncrypt($data){
        //openssl_private_encrypt($data,$encrypted,$this->pi_key);
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $this->pi_key);
            $crypto .= $encryptData;
        }
        $encrypted = $this->urlsafe_b64encode($crypto); //加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    //私钥解密
    public function PrivateDecrypt($encrypted){
        $crypto = '';
        foreach (str_split($this->urlsafe_b64decode($encrypted), 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $this->pi_key);
            $crypto .= $decryptData;
        }
        //$encrypted = $this->urlsafe_b64decode($encrypted);
        //openssl_private_decrypt($encrypted,$decrypted,$this->pi_key);
        return $crypto;
    }

    //加密码时把特殊符号替换成URL可以带的内容
    function urlsafe_b64encode($string) {
        $data = base64_encode($string);
        //$data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

    //解密码时把转换后的符号替换特殊符号
    function urlsafe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}
//http://post.lutong2000.com/Home_Index_UserPass.html
//路通2000

class Apiv11 extends Controller
{

    protected $layout = 'default';

    protected $config = [];

    protected $paytype = 'alipay_v11';//支付标识

    public function _initialize()
    {
        parent::_initialize();
    }

//RSA签名排列，按键值字母排序
public function encryptRsaStr($param)
{
    //参数排序
    ksort($param);
    unset($param['notify_url']);
    unset($param['async_notify_url']);
    return urldecode(http_build_query($param));
}

//AES加密排列，按键值字母排序
public function encryptAesStr($param)
{
    //参数排序
    ksort($param);
    return json_encode($param,true);
}

//AES-128-ECB加密
public function aes_encrypt($data, $key) {
    $data =  openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    return base64_encode($data);
}

//AES-128-ECB解密
public function aes_decrypt($data, $key) {
    $encrypted = base64_decode($data);
    return openssl_decrypt($encrypted, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
}
  
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


        $mno = $this->config['mno'];
        $orderno = $out_trade_no;    //订单号
        $amount = $amount;    //交易金额
        $time = date("Y-m-d H:i:s");  //订单时间
      	$client_ip = $_SERVER['REMOTE_ADDR'];
      	$pt_id = $this->config['pt_id'];
        $notify_url =  $this->config['sys_returnurl'];   //服务端返回地址
        $async_notify_url = $this->config['sys_notifyurl'];  //页面跳转返回地址
        //$Md5key = $this->config['Md5key'];   //密钥
        $device = $this->config['device'];    //银行编码
      	
        $native = array(
            "mno" => $mno,
            "orderno" => $orderno,
            "amount" => $amount,
            "pt_id" => $pt_id,
            "device" => $device,
            "notify_url" => $notify_url,
            "async_notify_url" => $async_notify_url,
          	"time" => $time,
          	
        );
		
        $encryptRsaStr = $this->encryptRsaStr($native);
		$Rsa= new Rsa();
      	$encrypted = "";
		$decrypted = "";
        $encrypted = $Rsa->PrivateEncrypt($encryptRsaStr);
      	
        $native["sign"] = $encrypted;
      	$aes_key = "vhZlAOAGx1RsAO+D";
      	$encryptAesStr = $this->encryptAesStr($native);
      	$content = $this->aes_encrypt($encryptAesStr,$aes_key);
      	
      	$param = [
   		 'mno' => $mno,
   		 'content' => $content
		];
      	
        //$native['pay_attach'] = "";
        //$native['pay_productname'] =$title;
      	//var_dump($encryptRsaStr);
      	//die();

        //exit(ToolReq::createForm($this->config['apiUrl'], $param));
      	$data = "";
       	$res = ToolReq::post($this->config['apiUrl'], $param);
      	if(empty($res) || $res['code'] != "success")
       {
  		var_dump($data);
  		exit;
		}

	header("Location:" . $res['data']['payurl']);

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {

        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),$this->paytype.'/notifyx');
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

        
        //$mno = $this->request->request('mno','');
        //$content = $this->request->request('content','');
      	$mno = "A200102203443968";
        $content = "zLRlRPK9QXDLZ7F0NHaZy7QD7OxFTqIHP624JRYM1BpqkS1/96jsDowDWVkKYUeCHkS5uYex4SnvrMCPglAZg1j6xo2YvshViuGhyVyqCfmcfMCpU+gCJkn8VRzmF7M995/dqjppQ9t9cao3rjb5A3Dad99l/ZdnjuuCpodkYHrCRJBwkxgMjSh8T+Aw+71IsT0HWyi5L+wA44WPOtE96x5oz8ie4KG1lXT16WPonOw+eqb89VWVRoE7A6Eq4U6Hm+lFEfnXP84+lY68O5vkAfFVGSiGm5zKcqsEbpiRDHH7P8xnNhMq7vesPh23LcnqanNXZWPW5mvJD94yTNEpjDN9PEaazlaDQovSocqzZLNyzBuGj7GPh7L8iYn51zomin3+k9u3VRnedsq4E4Tuig==";
      	if(array_key_exists('mno',$_POST))$mno = $_POST['mno'];
      	if(array_key_exists('content',$_POST))$content = $_POST['content'];
		//$content = trim($_POST['content']);
      	//var_dump($mno);
      	//die();
		$aes_key = "vhZlAOAGx1RsAO+D";
      	$content = $this->aes_decrypt($content,$aes_key);
      	
		$param = json_decode($content,true);
      	if(empty($param)){
    		die("请求参数解析失败");
				}
      
      	if(array_key_exists('s_orderno',$_POST))$s_orderno = $_POST['s_orderno'];
      	if(array_key_exists('orderno',$_POST))$orderno = $_POST['orderno'];
      	if(array_key_exists('amount',$_POST))$amount = $_POST['amount'];
      	if(array_key_exists('status',$_POST))$status = $_POST['status'];
      	if(array_key_exists('paytime',$_POST))$paytime = $_POST['paytime'];
      	if(array_key_exists('sign',$_POST))$sign = $_POST['sign'];
      	/*$s_orderno = trim($param['s_orderno']);
      	$orderno = trim($param['orderno']);
      	$amount = trim($param['amount']);
      	$status = trim($param['status']);
      	$paytime = trim($param['paytime']);
      	$sign = trim($param['sign']);*/
      	
      	var_dump($sign);
      	die();
      	//待加密源数据
		$encryptRsaStr = $this->encryptRsaStr($_POST);
      	$Rsa= new Rsa();
      	$encrypted = $Rsa->PrivateEncrypt($encryptRsaStr); //根据加密源数据用私钥进行加密
      	$decrypted = $Rsa->PublicDecrypt($sign); //根据$sign用公钥进行解密
      	
      	
        if($sign == $encrypted && $encryptRsaStr == $decrypted)
        {
            if ($decrypted['status']==1)
            {
                try {

                    $logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $orderno;
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $s_orderno;
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = $paytime;
                    $myOrder['realprice'] = $amount;
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
        $logM->addLog(json_encode($this->request->param()),$this->paytype.'/returnx');

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
            $payTypeInfo = $PayTypeM->where('type', $this->paytype)->find();
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
