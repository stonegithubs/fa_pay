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
  
  public function getSign($array = array(), $key)
	{
    ksort($array);
    foreach ($array as $k => $v) {
        if ($array[$k] == '' || $k == 'sign' || $k == 'sign_type' || $k == 'key') {
            unset($array[$k]);//去除多余参数
        }
    }
    return strtolower(md5($this->createLinkString($array) . "&key=" . $key));
	}
  
  public function createLinkString($para)
	{
    $arg = "";
    foreach ($para as $key => $value) {
        $arg .= $key . "=" . $value . "&";
    }
    //去掉最后一个&字符
    $arg = substr($arg, 0, count($arg) - 2);
    //如果存在转义字符，那么去掉转义
    if (get_magic_quotes_gpc()) {
        $arg = stripslashes($arg);
    }
    return $arg;
	}
  	
  	public function curlPost($url, $data = array())
	{
    $curl = curl_init();//初始化
    curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array( //改为用JSON格式来提交
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ));
    $result = curl_exec($curl);//执行命令
    curl_close($curl);//关闭URL请求
    return $result;
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


        $mchid = $this->config['mchid'];
      	//$mch_id = 1000395;
        $out_trade_no = $out_trade_no;    //订单号
        $total_fee = $amount;    //交易金额
        $timestamp = time();  //订单时间
        $notify_url =  $this->config['pay_notifyurl'];   //服务端返回地址
        $return_url =  $this->config['pay_callbackurl'];  //页面跳转返回地址
      	$goodsname = "测试支付";
      	$remark = "wumingpay";
      	$requestip = ToolReq::getIp();
        $mch_secret = $this->config['Md5key'];   //密钥
//        $apiUrl = $this->config['apiUrl'];   //提交地址
        $paytype = $this->config['paytype'];    //银行编码
      	$strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm"; //随机数基本字符串
        $native = array(
            "paytype" => $paytype,
          	"return_url" => $return_url,
            "out_trade_no" => $out_trade_no,
          	"remark" => $remark,
            "total_fee" => $total_fee,
            "notify_url" => $notify_url,
            "goodsname" => $goodsname,
          	"requestip" => $requestip,
            
        );
      	/*$nativeSign = $native;
      	$nativeSign['mch_secret'] = $mch_secret;
        ksort($nativeSign);
        $md5str = "";
        foreach ($nativeSign as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = substr($md5str,0,strlen($md5str)-1);
        $sign =strtoupper(md5($md5str));
        $native["sign"] = $sign;*/
      	//加入商户ID及算签名
		$post1['mchid'] = $mchid; //商户ID，请自行调整
		$post1['timestamp'] = $timestamp; //时间戳
		$post1['nonce'] = substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), 10);
		$post1['sign'] = $this->getSign(array_merge($native, $post1), $mch_secret);//商户密匙，请自行调整
		//$sign = $post1['sign'];
		$post1['data'] = $native; //合并真正提交的参数JSON
        //$native['pay_attach'] = "";
        //$native['pay_productname'] =$title;
      	//var_dump($post1);
      	//die();

        //exit(ToolReq::createForm($this->config['apiUrl'], $native));
      	$data = "";
       	$res = $this->curlPost($this->config['apiUrl'], $post1);
      	//var_dump($res);
      	//die();
      	$res = json_decode($res,true);
      	if(empty($res) || $res['error'] != 0)
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
        $payData['trade_no'] = $this->request->param('trade_no','');
        $payData['out_trade_no'] = $this->request->param('out_trade_no','');
        $payData['tradingfee'] = $this->request->param('tradingfee','');
        $payData['total_fee'] = $this->request->param('total_fee','');
        $payData['paysucessdate'] = $this->request->param('paysucessdate','');
        $logM->addLog($payData['out_trade_no'],'api_v17/notifyx');
        //$payData['sign'] = $this->request->request('sign','');

        /*if(empty($payData['datetime']))
        {
        	die(500);
        }*/
      	//先用$GLOBALS['HTTP_RAW_POST_DATA']来接收JSON
		/*$msg = $GLOBALS['HTTP_RAW_POST_DATA'];
      	//如果不行的话，再尝试用php://input接收JSON参数
      	if (!$msg) $msg = file_get_contents("php://input");
      	if ($msg) {    //将接收到的数据转成数组
        	$payData = json_decode($msg, true);
          }
      	if (!isset($payData)) {    //不存在提交的JSON就用正常的表单办法GET或POST来接受参数
          $post = array_merge($_GET, $_POST);//无论GET还是POST
          }*/
      	

        $Md5key = $this->config['Md5key'];
        /*ksort($payData);
        reset($payData);
        $md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = substr($md5str,0,strlen($md5str)-1);
      	
        $payData['sign'] = strtoupper(md5($md5str));*/
      	$getsign = $this->getSign($payData,$Md5key );
        $sign = $this->request->param('sign','');
        if ($sign===$getsign)//$sign===$getsign
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();
					
                    //修改订单状态
                    $out_trade_no = $payData['out_trade_no'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['trade_no'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = $payData['paysucessdate'];//date('Y-m-d H:i:s',$payData['pay_time']);
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
                  
                  exit("success");
                } catch (Exception $e) {

                }

            }
            exit("error");
        }else{
          exit("sign error");
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
