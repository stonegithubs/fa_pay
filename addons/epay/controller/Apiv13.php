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


//www.nyftz.cn
//云付通

class Apiv13 extends Controller
{

    protected $layout = 'default';

    protected $config = [];

    protected $paytype = 'wechat_v13';//支付标识

    protected $token = null;

    public function _initialize()
    {
        parent::_initialize();


    }


    /*public function index()
    {
        //http://pay.localhost/addons/epay/apiv13
        $customerid='11057';
        $userkey='e485896eea68807f6a1a48fd8763ba89135831ee';
        $apiUrl ='http://www.nyftz.cn/apisubmit';

        $version='1.0';
        $sdorderno= 'E'.date('YmdHis');
        $total_fee="100.00";
        $paytype = '920';
        $notifyurl='http://www.xhd9.com/api/index';
        $returnurl='http://www.xhd9.com/api/index';

        $postData = array();
        $postData['version'] = $version;
        $postData['customerid'] = $customerid;
        $postData['sdorderno'] = $sdorderno;
        $postData['total_fee'] = $total_fee;
        $postData['paytype'] = $paytype;
        $postData['notifyurl'] = $notifyurl;
        $postData['returnurl'] = $returnurl;
        $postData['remark'] = '';

        $sign=md5('version='.$version.'&customerid='.$customerid.
                    '&total_fee='.$total_fee.'&sdorderno='.$sdorderno.
                    '&notifyurl='.$notifyurl.'&returnurl='
                    .$returnurl.'&'.$userkey);
        $postData['sign'] = $sign;

        exit(ToolReq::createForm($apiUrl, $postData,'POST'));

    }*/

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


        $customerid=$this->config['customerid'];
        $userkey= $this->config['userkey'];
        $apiUrl = $this->config['apiUrl'];

        $version='1.0';
        $sdorderno= $out_trade_no;
        $total_fee=number_format($amount,2,'.','');;
        $paytype = '920';
        $notifyurl=$this->config['sys_notifyurl'];
        $returnurl=$this->config['sys_returnurl'];

        $postData = array();
        $postData['version'] = $version;
        $postData['customerid'] = $customerid;
        $postData['sdorderno'] = $sdorderno;
        $postData['total_fee'] = $total_fee;
        $postData['paytype'] = $paytype;
        $postData['notifyurl'] = $notifyurl;
        $postData['returnurl'] = $returnurl;
        $postData['remark'] = '';

        $sign=md5('version='.$version.'&customerid='.$customerid.
            '&total_fee='.$total_fee.'&sdorderno='.$sdorderno.
            '&notifyurl='.$notifyurl.'&returnurl='
            .$returnurl.'&'.$userkey);
        $postData['sign'] = $sign;

        exit(ToolReq::createForm($apiUrl, $postData,'POST'));

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


}
