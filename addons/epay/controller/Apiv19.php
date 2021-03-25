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
class Apiv19 extends Controller
{

    protected $layout = 'default';


    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }
  /**
                   * 移除空值的key
                   * @param $para
                   * @return array
                   * @author helei
                   */
                 public function removeEmpty($para) {
                      $paraFilter = [];
                      foreach ($para as $key => $val) {
                          if ($val === '' || $val === null) {
                              continue;
                          } else {
                              if (!is_array($val)) {
                                  $para[$key] = is_bool($val) ? $val : trim($val);
                              }
                              $paraFilter[$key] = $val;
                          }
                      }
                      return $paraFilter;
                  }

    public function submit()
    {

       try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v2/submit');
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
        $paytype = 'alipay_wap_v2';
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


        $merchantid = $this->config['merchantid'];
        $orderid = $out_trade_no;    //订单号
        $amount = number_format($amount, 2);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $notify_url =  $this->config['pay_notifyurl'];   //服务端返回地址
        $return_url = $this->config['pay_callbackurl'];  //页面跳转返回地址
        $merchantKey = $this->config['Md5key'];   //密钥
        $client_ip = $_SERVER['REMOTE_ADDR'];   //商户IP
        $paytype = $this->config['paytype'];    //银行编码
        $native = array(
            "merchantid" => $merchantid,
            "orderid" => $orderid,
            "amount" => $amount,
            "paytype" => $paytype,
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "client_ip" => $client_ip,
          	//"merchantKey" => $merchantKey,
        );
      	$native = $this->removeEmpty($native);
        ksort($native);
        $md5str = "";
        foreach ($native as $k => $v) {
    		if ($md5str) {
        	$md5str = $md5str . $k . $v;//"$md5str&{$k}={$v}";
    		} else {
       			 $md5str = $k . $v;//"{$k}={$v}";
    				}
		}
      	
        $sign = md5($md5str . $merchantKey);
      	//var_dump($sign);
      	//die();
        $native["sign"] = $sign;
        //$native['ext'] = "1234|456";
        //$native['productname'] =$title;
		//var_dump($native);
    	//die();
        //exit(ToolReq::createForm($this->config['apiUrl'], $native));
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
		
		header("Location:" . $res['data']['qrcode']);

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {
		 try {
             $logM = new Log();
             $ipaddr = ToolReq::getIp();
        	 $logM->addLog($ipaddr."|".json_encode($this->request->param()),'api_v2/notifyx');
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
       
        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', 'alipay_wap_v2')->find();
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
        //$payData['merchantid'] = $this->request->request('merchantid','');
        $payData['orderid'] = $this->request->request('orderid','');
        $payData['pay_type'] = $this->request->request('pay_type','');
        $payData['money'] = $this->request->request('money','');
      	$payData['rndstr'] = $this->request->request('rndstr','');
        $payData['out_order_id'] = $this->request->request('out_order_id','');
        $Md5key = $this->config['Md5key'];

        /*if(empty($payData['datetime']))
        {
        	die(500);
        }*/

        
        ksort($payData);
        reset($payData);
        $md5str = '';
        foreach ($payData as $k => $v) {
    		if ($md5str) {
        	$md5str = $md5str . $k . $v;//"$md5str&{$k}={$v}";
    		} else {
       			 $md5str = $k . $v;//"{$k}={$v}";
    				}
		}
        $payData['sign'] = md5($md5str . $Md5key);
        $sign = $this->request->request('sign','');
        if ($sign===$payData['sign'])//
        {
            if (1)//$payData['status'] =="2"
            {
                try {

                    //$logM->addLog('ok','api_v3/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['out_order_id'];
                    $myOrder = array();
                    $myOrder['sys_order_id'] = $payData['orderid'];;
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paydate'] = date("Y-m-d H:i:s");
                    $myOrder['realprice'] = $payData['income'];
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
            $payTypeInfo = $PayTypeM->where('type', 'alipay_wap_v2')->find();
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
