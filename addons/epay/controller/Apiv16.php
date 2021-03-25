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
use tool\Rsa as ToolRsa;



class Apiv16 extends Controller
{

    protected $layout = 'default';

    protected $config = [];

    protected $paytype = 'alipay_v19';//支付标识

    protected $token = null;

    public function _initialize()
    {
        parent::_initialize();


    }
  	public function en($content, $key)
{
openssl_public_encrypt($content, $en, $key);

return base64_encode($en);
}

    	public function de($content, $key)
{
openssl_public_decrypt($content, $de, $key);

return base64_encode($de);
}


    public function submit()
    {
       try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'alipay_v19/submit');
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


        $userId = $this->config['userId'];
        $id = $out_trade_no;    //订单号
        $payAmount = $amount;    //交易金额
        //$pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $notifyUrl =  $this->config['notifyUrl'];   //服务端返回地址
        $returnUrl = $this->config['returnUrl'];  //页面跳转返回地址
        $Md5key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDS5bUpdms53qvTEXNydn5in3xhgllWp2+hB3nE
n2JFsPQwloJAgSqMCxk0ivGMVVNQKag5yzQk74RmbyWqOIJX3r1SG1gC7DA5MeaxjDZZHg6MqIjk
iVfhDRCnPGMCITvm4Ui6N0DDTn7p3w4oWRNRkGyeZ2L7h+aOTM75ITll4QIDAQAB
-----END PUBLIC KEY-----';   //密钥
        $payType = $this->config['payType'];    //银行编码
      	
      	$id = $this->en($id , $Md5key);
        $payAmount = $this->en($payAmount , $Md5key);
        $notifyUrl = $this->en($notifyUrl , $Md5key);
        $returnUrl = $this->en($returnUrl , $Md5key);
        $payType = $this->en($payType , $Md5key);
          
        $native = array(
            "userId" => $userId,
            "id" => $id,
            "payType" => $payType,
            "notifyUrl" => $notifyUrl,
            "returnUrl" => $returnUrl,
            "payAmount" => $payAmount,
            
        );
        /*ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "";
        $native['pay_productname'] =$title;*/

        exit(ToolReq::createForm($this->config['apiUrl'], $native));

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {
		 try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
           	if($ipaddr != '47.244.150.5')
            {
            	die(500);
            }
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'alipay_v19/notifyx');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }



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

        $payData = array();
        $payData['orderId'] = $this->request->request('orderId','');
        $payData['payAmount'] = $this->request->request('payAmount','');
        $payData['poundage'] = $this->request->request('poundage','');
        $payData['userId'] = $this->request->request('userId','');
        $payData['payTime'] = $this->request->request('payTime','');
        $payData['payStatus'] = $this->request->request('payStatus','');
      	//$payData['orderId'] = 'E202004021227231463';
        //$payData['payAmount'] = 'Jb+QaSxI159r75648tiT2lSHXdArJdRRTLGAlC85uWvwEDiD6Inv\/oGeaTwybdSZwKg976w667xsxZlAhJ6lBgHs38QWJ\/AjT4ium5g9QSveDgqKasbVssHCJHRhXxS5vVwxmhIfdAq3Yjl4nnLxGgL5djB6GBx9W47QHdCYhq8=';
        //$payData['poundage'] = 'LnGf5KcRZypDw33nwNyCxCsyhRNRQWMaeWowMoJgY5FYFJgQPtUkOFu9G3JYeNs4w60BstSElIZ2ifrjLe9TyAw+uSKFJRUzAcVjBTYUObfGptTNFWkh6jscjfaYa1qt+WNAkWwYqiTTGyapeUEEAjTsoQWD6H79dpF8ZvQiTiY=';
        //$payData['userId'] = '2003281853585109851';
        //$payData['payTime'] = 'fZ33yCRRRShkpZi1chpsZ9w1HIeykxnbF5D\/uP6bb3TCRy7jN1cbotvQ0VNlp73ikptzX3olYeANkB6uS7zK2PJZtf2UUGPZsvEn6e31ePvfgnJn23o5KweJVr\/u4QWB1rJuJGLjIymo8HZzqgnPH7CXJ6RS+RnefkyU5i\/PZEY=';
        //$payData['payStatus'] = 'CcBtPEMiAA8+IO0lMs9kzS9oxtUG43PvSFsTxRoLB1QeazXy1dKWrQNgPZEt91WJ5\/WNqHRlNTsSASHG2GtSQLqAf1HUVuPE9WLm+\/+VgRecBBg1LgKQDBhk84H36vAb5RNHYKqw883wmM+RmQc+dngvBqvOnWaZIWVIT7UwU7o=';
      	
        $Md5key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDS5bUpdms53qvTEXNydn5in3xhgllWp2+hB3nE
n2JFsPQwloJAgSqMCxk0ivGMVVNQKag5yzQk74RmbyWqOIJX3r1SG1gC7DA5MeaxjDZZHg6MqIjk
iVfhDRCnPGMCITvm4Ui6N0DDTn7p3w4oWRNRkGyeZ2L7h+aOTM75ITll4QIDAQAB
-----END PUBLIC KEY-----';   //密钥
      	
      	$payData['payAmount'] = ToolRsa::public_decrypt($payData['payAmount'] , $Md5key);
      	$payData['poundage'] = ToolRsa::public_decrypt($payData['poundage'] , $Md5key);
      	$payData['payTime'] = ToolRsa::public_decrypt($payData['payTime'] , $Md5key);
      	$payData['payStatus'] = ToolRsa::public_decrypt($payData['payStatus'] , $Md5key);
      	//var_dump($payData['payAmount']);
      	//var_dump($payData['poundage']);
      	//var_dump($payData['payTime']);
      	//var_dump($payData['payStatus']);
      	//die();



       /* ksort($payData);
        reset($payData);
        $md5str = "";
        foreach ($payData as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $payData['sign'] = strtoupper(md5($md5str . "key=" . $Md5key));
        $sign = $this->request->request('sign','');*/
        if ($payData['payStatus'] == '1')//
        {
            if (1)
            {
                try {

                    $logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['orderId'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['orderId'];
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = $payData['payTime'];
                    $myOrder['realprice'] = $payData['payAmount'];
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
