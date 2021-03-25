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
use tool\Signn;

/**
 * Class Apiv2 for cpay
 * @package addons\epay\controller
 */
class Apiv2 extends Controller
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
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v6/submit');
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
        $paytype = 'alipay_wap_v4';
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


        $m_id = $this->config['m_id'];//商户用户名
      	$methond = $this->config['methond'];//提交订单的参数
        $m_orderid = $out_trade_no;    //订单号
        $hopeAmount = $amount;    //交易金额
        //$pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $notifyurl =  $this->config['notify_url'];   //服务端返回地址
        $sign_type = $this->config['sign_type'];  //签名方式
        $Md5key = $this->config['Md5key'];   //密钥
//        $apiUrl = $this->config['apiUrl'];   //提交地址
        $paymentmode = $this->config['paymentmode'];    //银行编码
      	$appraw = $this->config['appraw'];  
        $native = array(
            "m_id" => $m_id,
          	"methond" => $methond,
          	"m_orderid" => $m_orderid,
            "hopeAmount" => $hopeAmount,
            "paymentmode" => $paymentmode,
            "notifyurl" => $notifyurl,
          	"appraw" => $appraw,
            
            
        );
      	//$native = array_filter($native);
        //ksort($native);
        /*$md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = rtrim($md5str, "&");
      	$md5str .= $Md5key;
        $sign = md5($md5str);
        $native["sign"] = $sign;
        //$native['pay_attach'] = "";
        //$native['remark'] =$title;
      	//var_dump($sign);
      	//die();*/
      	$md5_Sign = new Signn($Md5key);//实例化sgin类，并且传入apikey
      	$paramFilter = $md5_Sign->argSort($native);//对key => value 进行 A-Z 排序，并返回 拼接字符串
      	$md5Hash = $md5_Sign->md5Sign($paramFilter);//开始进行签名，
      	//var_dump($paramFilter);
      	//die();
      	$requestParam = http_build_query($native).'&sign_type=MD5&sign='.$md5Hash;// 接最终请求的数据结构，可用于post 及 get
		//var_dump($requestParam);
      	//die();
        //exit(ToolReq::createForm($this->config['apiUrl'], $native));
      	$res = ToolReq::post($this->config['apiUrl'], $requestParam);
      	var_dump($res);
      	die();
      	//$res = json_decode($res,true);
      	if(empty($res))
       {
  		var_dump($res);
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
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v6/notifyx');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
       
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'alipay_wap_v4')->find();
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
        //$payData['version'] = $this->request->param('version','');
      	$payData['orderid'] = $this->request->param('orderid','');
      	$payData['m_orderid'] = $this->request->param('m_orderid','');
      	$payData['payamount'] = $this->request->param('payamount','');
      	
      
        /*$payData['out_trade_no'] = $this->request->param('out_trade_no','');
        $payData['trade_no'] = $this->request->param('trade_no','');
        $payData['total_fee'] = $this->request->param('total_fee','');
        $payData['pay_type'] = $this->request->param('pay_type','');
        $payData['remark'] = $this->request->param('remark','');
      	

        /*if(empty($payData['datetime']))
        {
        	die(500);
        }

        
      	$payData = array_filter($payData);
        ksort($payData);
        reset($payData);*/
        /*$md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
      	$md5str = rtrim($md5str, "&");*/
      	$Md5key= $this->config['Md5key'];
        $payData['sign'] = md5($payData['orderid'] . $payData['m_orderid'] . $payData['payamount'] . $Md5key);

        
      	$logM->addLog($payData['sign'],'api_v6/notifyx_sign');
        $sign = $this->request->param('sign','');
        if ($sign===$payData['sign'])//
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['m_orderid'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['orderid'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = date("Y-m-d H:i:s");
                    $myOrder['realprice'] = $payData['payamount'];
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
            $this->error('eerror');
        }

    }

    /**
     * 支付成功返回
     */
    public function returnx()
    {

        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),'api_v6/returnx');

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
            $payTypeInfo = $PayTypeM->where('type', 'alipay_wap_v4')->find();
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
