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

    public function submit()
    {

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


        $uid = $this->config['uid'];
        $outTradeNo = $out_trade_no;    //订单号
        $money = number_format($amount,2,".","");    //交易金额
        $timestamp = time() * 1000;  //订单时间
        $notifyUrl =  $this->config['sys_notifyurl'];   //服务端返回地址
        $returnUrl = $this->config['sys_returnurl'];  //页面跳转返回地址
        $Md5key = $this->config['Md5key'];   //密钥
//        $apiUrl = $this->config['apiUrl'];   //提交地址
        $channelType = $this->config['channelType'];    //银行编码
      	$channel = $this->config['channel'];
        $native = array(
            "uid" => $uid,
            "outTradeNo" => $outTradeNo,
            "money" => $money,
            "timestamp" => $timestamp,
            "channelType" => $channelType,
            "notifyUrl" => $notifyUrl,
            "returnUrl" => $returnUrl,
          	"channel" => $channel,
          	"token" => $Md5key
        );
 
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5(substr($md5str,0,strlen($md5str)-1)));
        $native["sign"] = $sign;
        //$nativel['outUserId'] = "";
        //$nativel['goodsName'] =$title;
		//var_dump($native);
      	//die();
        //exit(ToolReq::creatForm($this->config['apiUrl'], $native));
      
       	$res = ToolReq::post($this->config['apiUrl'], $native);
      	if(empty($res) || $res['code'] != 0)
       {
  		var_dump($data);
  		exit;
		}

	header("Location:" . $res['data']['payUrl']);

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {

        $logM = new Log();
//        $logM->addLog(json_encode($this->request->param()),'api_v3/notifyx');
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
        //$payData['channel'] = $_REQUEST["outTradeNo"];
        $payData['orderid'] = $_REQUEST["outTradeNo"];
        $payData['transaction_id'] = $_REQUEST["tradeNo"];
        $payData['money'] = $_REQUEST["money"];
        $payData['amount'] =  $_REQUEST["realMoney"];
      	$payData['datetime'] = date('Y-m-d H:i:s');
       // $payData['uid'] = $this->config['uid'];


        /*$payData['token'] = $this->config['Md5key'];
      
      	$nativel = array(
          	"uid" => $payData['uid'],
            "channel" => $payData['channel'],
            "outTradeNo" => $payData['orderid'],
            "money" => $payData['money'],
            "tradeNo" => $payData['transaction_id'],
            "realMoney" =>  $payData['amount'],
          	"token" => $payData['token']
        );
        ksort($nativel);
        reset($nativel);
        $md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $nativel['sign'] = strtoupper(md5(substr($md5str,0,strlen($md5str)-1)));
        $sign = $this->request->request('sign','');*/
      	
      	$data = $_POST;
      	$data['token'] = $this->config['Md5key'];
      	unset($data['sign']);
      	ksort($data);
      	$md5str = "";
      	foreach ($data as $key => $val) {
          	$md5str = $md5str . $key . "=" . $val . "&";
        }
      	$sign = strtoupper(md5(substr($md5str,0,strlen($md5str)-1)));
        if ($sign===$_REQUEST['sign'])
        {
            if (1)
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['orderid'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['transaction_id'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = $payData['datetime'];
                    $myOrder['realprice'] = $payData['amount'];
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
            exit("SUCCESS");
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
