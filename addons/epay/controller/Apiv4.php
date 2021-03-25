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
 * Class Apiv4
 * @package addons\epay\controller
 */
class Apiv4 extends Controller
{

    protected $layout = 'default';


    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }
  
  public function postt($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'accept-encoding: gzip, deflate, br',
        'accept-language: zh-CN,zh;q=0.9,en;q=0.8',
        'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
    ]);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $data = curl_exec($ch);
    $errdata = curl_error($ch);
    curl_close($ch);
    return $data;
}

    public function submit()
    {

        $out_trade_no = $this->request->request("out_trade_no");
        $amount = $this->request->request('amount');
        $title = $this->request->request("title");

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        $paytype = 'wx_wap_v1';
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


        $pay_memberid = $this->config['pay_memberid'];
        $pay_orderid = $out_trade_no;    //订单号
        $pay_amount = $amount;    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl =  $this->config['sys_notifyurl'];   //服务端返回地址
        $pay_callbackurl = $this->config['sys_returnurl'];  //页面跳转返回地址
        $Md5key = $this->config['Md5key'];   //密钥
//        $apiUrl = $this->config['apiUrl'];   //提交地址
        $pay_bankcode = $this->config['pay_bankcode'];    //银行编码
      	$oauth_url = "http://chinapayx.com/Pay_WechatMp_oauth.html";// 微信授权地址
      	$pay_page_url = 'http://wx.jiechenghuanyu.com/mpay.php';//拉取微信支付地址
      	$redirect_url = $this->config['gateway_url']; // 授权成功回调地址 

      	if(empty($pay_memberid) || empty($Md5key)) {
    		exit("请先配置参数");
		}
      
      	// 1. 前往获取openid
		if (empty($_GET['openid'])) {
    		$param = array(
        	"pay_memberid" => $pay_memberid,
        	"pay_orderid" => $pay_orderid,
        	"pay_bankcode" => $pay_bankcode,
        	"pay_redirecturl" => $redirect_url,
    		);
    	ksort($param);
    	$md5str = "";
    	foreach ($param as $key => $val) {
        	$md5str = $md5str . $key . "=" . $val . "&";
    	}
    		//echo ($md5str . "key=" . $Md5key) . "<br/>";
    		$sign = strtoupper(md5($md5str . "key=" . $Md5key));
    		$param['sign'] = $sign;
    		$oauth_url = $oauth_url . '?' . http_build_query($param);
    		//echo $oauth_url;
    		header("Location: $oauth_url");
    		exit(0);
		}
      // 获取openid后
		if (isset($_GET['openid'])) {

    		$tjurl = $tjurl . '?openid=' . $_GET['openid'];

    		// 拿到openid调取支付
    		$openid = $_GET['openid'];

    		$native = array(
        		"pay_memberid" => $pay_memberid,
        		"pay_orderid" => $pay_orderid,
        		"pay_amount" => $pay_amount,
        		"pay_applydate" => $pay_applydate,
        		"pay_bankcode" => $pay_bankcode,
        		"pay_notifyurl" => $pay_notifyurl,
        		"pay_callbackurl" => $pay_callbackurl,
    		);
    		ksort($native);
    		$md5str = "";
    		foreach ($native as $key => $val) {
        		$md5str = $md5str . $key . "=" . $val . "&";
    		}
    		//echo($md5str . "key=" . $Md5key);
    		$sign = strtoupper(md5($md5str . "key=" . $Md5key));
    		$native["pay_md5sign"] = $sign;
    		$native['pay_attach'] = "1234|456";
    		$native['pay_productname'] = '团购商品';
    		$native['pay_openid'] = $openid; // 这里填写openid
          	$native['pay_mode'] = '1';

    		$result = post($tjurl, $native);
    		$response = json_decode($result, true);
    		$pay_config = json_decode($response['data'], true);

    		$pay_config['callback_url'] = $pay_callbackurl;
    		//var_dump($response,$pay_config);exit;
		
    		// 获取支付信息后前往调取支付

    		$pay_page_url = $pay_page_url . '?data=' . bin2hex(json_encode($pay_config));
    		header("Location: $pay_page_url");
			}
        /*$native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "";
        $native['pay_productname'] =$title;

        exit(ToolReq::createForm($this->config['apiUrl'], $native));*/

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {

        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),'api_v4/notifyx');
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'wx_wap_v1')->find();
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
        $payData['memberid'] = $this->request->request('memberid','');
        $payData['orderid'] = $this->request->request('orderid','');
        $payData['transaction_id'] = $this->request->request('transaction_id','');
        $payData['amount'] = $this->request->request('amount','');
        $payData['datetime'] = $this->request->request('datetime','');
        $payData['returncode'] = $this->request->request('returncode','');


        $Md5key = $this->config['Md5key'];
        ksort($payData);
        reset($payData);
        $md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $payData['sign'] = strtoupper(md5($md5str . "key=" . $Md5key));
        $sign = $this->request->request('sign','');
        if ($sign===$payData['sign'])
        {
            if (1)
            {
                try {

                    $logM->addLog('ok','api_v4/notifyx');
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
        $logM->addLog(json_encode($this->request->param()),'api_v4/returnx');

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
            $payTypeInfo = $PayTypeM->where('type', 'wx_wap_v1')->find();
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
