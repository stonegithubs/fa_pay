<?php

namespace app\api\controller;

use app\admin\controller\pay\Order;
use app\admin\model\pay\PayRate;
use app\admin\model\pay\Type;
use app\common\controller\Api;
use app\common\model\Log;
use app\common\controller\Frontend;
use app\admin\model\Admin;
use app\common\model\PayOrder;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use tool\Request as ToolReq;
use think\Config;
use think\Db;
use think\Exception;
use app\admin\library\Service;

/**
 * 支付接口
 */
class Pay extends Frontend
{


    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function submit()
    {
        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),'api/pay/submit');

        $appid = $this->request->post("appid",'');
        $sign = $this->request->post("sign",'');
        if (empty($appid)||empty($sign))
            $this->error();
        $adminM = new Admin();
        $adminInfo = $adminM->where('appid',$appid)->find();
        if (!$adminInfo)
            $this->error();

        $paytype = $this->request->post('paytype','');
        $data = [
            'appid' => $appid, // 商户应用id
            'paytype' => $paytype,
            'createtime' => $this->request->post('createtime',''),
            'price' => $this->request->post('price',0),
            'title' => $this->request->post('title',''),
            'out_order_id' => $this->request->post('out_order_id',''),
            'from_address' => $this->request->post('from_address',''),
//            'to_address' => $this->request->post('to_address',''),
            'extend' => $this->request->post('extend',''),
            'returnurl' => $this->request->post('returnurl',''),
            'notifyurl' => $this->request->post('notifyurl',''),
        ];

        $sign_str = $this->build_sign_str($data). $adminInfo['appsecret'];
//        $logM->addLog($sign_str,'api/pay/submit/sign_str');

        if ($sign!==md5($sign_str))
            $this->error('签名错误');

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', $paytype)->where('status',1)->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
        {
            $this->error('支付通道不可用(5001)');
        }

        $rate =100;
        $PayRateM = new PayRate();
        $payRateInfo = $PayRateM->where('admin_id',$adminInfo['id'])->where('pay_type',$paytype)->find();
        if (!$payRateInfo)
        {
            if ($payTypeInfo['access']==1)
            {
                $rate = $payTypeInfo['rate'];
            }else{
                $this->error('支付通道不可用(5002)');
            }
        }else{
            $rate = $payRateInfo['rate'];
        }

        $payTypeInfo['config'] = json_decode($payTypeInfo['config'],true);
        //扩展数据
        $data['status']=0;
        $data['timestamp']=$data['createtime'];
        $data['realprice']=$data['price'];
        $data['admin_id']=$adminInfo['id'];
        $data['rate']=$rate;

        $data['admin_id']=$adminInfo['id'];

        $payConfig = Config::get("payment");
        //订单手续费
        $rate_cost = $data['realprice'] * $rate*0.01;
        //单笔最低手续费（元）
        if (isset($payConfig['min_rate_sost']) && $payConfig['min_rate_sost'] >=$rate_cost)
            $rate_cost = $payConfig['min_rate_sost'];

        $data['cost']=$rate_cost;

        //最小金额
        if (isset($payTypeInfo['config']['min_price']) && $payTypeInfo['config']['min_price'] >$data['realprice'])
            $this->error("支付金额必须大于".$payTypeInfo['config']['min_price']);

        //最大金额
        if (isset($payTypeInfo['config']['max_price']) && $payTypeInfo['config']['max_price'] <$data['realprice'])
            $this->error("支付金额不能大于".$payTypeInfo['config']['max_price']);

//        if($paytype == 'bipay' && $data['from_address'] == $data['to_address']){
//            $this->error('转出地址和转入地址不能是同一个');
//        }

        //汇率
        $data['exchange_rate']=$adminInfo['exchange_rate'];

        //保存订单
        $orderM = new PayOrder();
        try {
            $res = $orderM->insert($data);
            if (!$res)
            {
                $this->error('系统繁忙，请刷新后再试');
            }

        } catch (Exception $e){
//            echo $e->getMessage();
            $this->error('系统繁忙，请刷新后再试');
        }

        //单位换算
        if (isset($payTypeInfo['config']['price_decimal']))
        {
            $data['realprice'] = $data['realprice'] * $payTypeInfo['config']['price_decimal'];
            $data['price'] = $data['price'] * $payTypeInfo['config']['price_decimal'];
        }



        //发起支付
//        $payConfig = Config::get("payment");
        $postData=array();
        //交易最低金额
        /*if (isset($payConfig['min_price']) && $payConfig['min_price'] >$data['realprice'])
            $this->error("支付金额必须大于".$payConfig['min_price']);*/

        if (!empty($paytype))
        {

//            $postData['appid'] = $data['appid'];
//            $postData['out_order_id'] = $data['out_order_id'];
//            $postData['title'] = $data['title'];
//            $postData['from_address'] = $data['from_address'];
//            $postData['to_address'] = $data['to_address'];
//            $postData['amount'] = $data['realprice'];
//            $postData['type'] = 'alipay';
//            $postData['method'] = 'wap';
//            $postData['notifyurl'] = $data['notifyurl'];
//            $postData['returnurl'] = $data['returnurl'];
//			$postData['key'] = '123';
//
//            exit(ToolReq::createForm($payTypeInfo['config']['gateway_url'], $postData));

            try {
                $logM = new Log();
                $ipaddr = ToolReq::getIp();
                $logM->addLog($ipaddr."|".json_encode($this->request->param()),'bipay/submit');
            } catch (DataNotFoundException $e) {
            } catch (ModelNotFoundException $e) {
            } catch (DbException $e) {
            }

            header("Location:" . 'http://'.$_SERVER['HTTP_HOST'].'/index/payment?id='.$data['out_order_id']);

//            $this->success('操作成功','http://'.$_SERVER['HTTP_HOST'].'/index/payment?id='.$data['out_order_id']);
        }
        $this->error('支付通道异常');

    }



  /*public function submit_v17()
    {

        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),'api/pay/submit_v17');

        $appid = $this->request->post("appid",'');
        $sign = $this->request->post("sign",'');
        if (empty($appid)||empty($sign))
            $this->error('appid error');
        $adminM = new Admin();
        $adminInfo = $adminM->where('appid',$appid)->find();
        if (!$adminInfo)
            $this->error('appid error');

        $paytype = $this->request->post('paytype','');//支付类型
        $data = [
            'appid' => $appid, // 商户应用id
            'paytype' => $paytype,
            'createtime' => $this->request->post('createtime',''),
            'price' => $this->request->post('price',0),
            'title' => $this->request->post('title',''),
            'out_order_id' => $this->request->post('out_order_id',''),
            'extend' => $this->request->post('extend',''),
            'returnurl' => $this->request->post('returnurl',''),
            'notifyurl' => $this->request->post('notifyurl',''),
        ];

        $sign_str = $this->build_sign_str($data). $adminInfo['appsecret'];
//        $logM->addLog($sign_str,'api/pay/submit/sign_str');

        if ($sign!==md5($sign_str))
            $this->error('签名错误');

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', $paytype)->where('status',1)->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
        {
            $this->error('支付通道不可用(5001)');
        }

        $rate =100;
        $PayRateM = new PayRate();
        $payRateInfo = $PayRateM->where('admin_id',$adminInfo['id'])->where('pay_type',$paytype)->find();
        if (!$payRateInfo)
        {
            if ($payTypeInfo['access']==1)
            {
                $rate = $payTypeInfo['rate'];
            }else{
                $this->error('支付通道不可用(5002)');
            }
        }else{
            $rate = $payRateInfo['rate'];
        }

        $payTypeInfo['config'] = json_decode($payTypeInfo['config'],true);
        //扩展数据
        $data['status']=0;
        $data['timestamp']=$data['createtime'];
        $data['realprice']=$data['price'];
        $data['admin_id']=$adminInfo['id'];
        $data['rate']=$rate;

        $data['admin_id']=$adminInfo['id'];

        $payConfig = Config::get("payment");
        //订单手续费
        $rate_cost = $data['realprice'] * $rate*0.01;
        //单笔最低手续费（元）
        if (isset($payConfig['min_rate_sost']) && $payConfig['min_rate_sost'] >=$rate_cost)
            $rate_cost = $payConfig['min_rate_sost'];

        $data['cost']=$rate_cost;

        //最小金额
        if (isset($payTypeInfo['config']['min_price']) && $payTypeInfo['config']['min_price'] >$data['realprice'])
            $this->error("支付金额必须大于".$payTypeInfo['config']['min_price']);

        //最大金额
        if (isset($payTypeInfo['config']['max_price']) && $payTypeInfo['config']['max_price'] <$data['realprice'])
            $this->error("支付金额不能大于".$payTypeInfo['config']['max_price']);


        //保存订单
        $orderM = new PayOrder();
        try {
            $res = $orderM->insert($data);
            if (!$res)
            {
                $this->error('系统繁忙，请刷新后再试');
            }

        } catch (Exception $e){
//            echo $e->getMessage();
            $this->error('系统繁忙，请刷新后再试');
        }

        //单位换算
        if (isset($payTypeInfo['config']['price_decimal']))
        {
            $data['realprice'] = $data['realprice'] * $payTypeInfo['config']['price_decimal'];
            $data['price'] = $data['price'] * $payTypeInfo['config']['price_decimal'];
        }



        //发起支付
//        $payConfig = Config::get("payment");
        $postData=array();
        //交易最低金额
        /*if (isset($payConfig['min_price']) && $payConfig['min_price'] >$data['realprice'])
            $this->error("支付金额必须大于".$payConfig['min_price']);*/

       /* if (!empty($paytype))
        {

            $postData['out_trade_no'] = $data['out_order_id'];
            $postData['title'] = $data['title'];
            $postData['amount'] = $data['realprice'];
            $postData['type'] = 'alipay';
            $postData['method'] = 'wap';
            $postData['notifyurl'] = $data['notifyurl'];
            $postData['returnurl'] = $data['returnurl'];
            $postData['key'] = '123';

            $API = new \addons\epay\controller\Apiv17;
            $out_trade_no = $postData['out_trade_no'];
            $amount = $postData['amount'];
            $title = $postData['title'];
            $key = '123';
            echo $API->submit_b($out_trade_no,$amount,$title,$key);
            die();
            
        }
        $this->error('支付通道异常');
    }*/

  public function submit_v9()
    {

        $logM = new Log();
        $logM->addLog(json_encode($this->request->param()),'api/pay/submit_v9');

        $appid = $this->request->post("appid",'');
        $sign = $this->request->post("sign",'');
        if (empty($appid)||empty($sign))
            $this->error('appid error');
        $adminM = new Admin();
        $adminInfo = $adminM->where('appid',$appid)->find();
        if (!$adminInfo)
            $this->error('appid error');

        $paytype = $this->request->post('paytype','');
        $data = [
            'appid' => $appid, // 商户应用id
            'paytype' => $paytype,
            'createtime' => $this->request->post('createtime',''),
            'price' => $this->request->post('price',0),
            'title' => $this->request->post('title',''),
            'out_order_id' => $this->request->post('out_order_id',''),
            'extend' => $this->request->post('extend',''),
            'returnurl' => $this->request->post('returnurl',''),
            'notifyurl' => $this->request->post('notifyurl',''),
        ];

        $sign_str = $this->build_sign_str($data). $adminInfo['appsecret'];
//        $logM->addLog($sign_str,'api/pay/submit/sign_str');

        if ($sign!==md5($sign_str))
            $this->error('签名错误');

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', $paytype)->where('status',1)->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
        {
            $this->error('支付通道不可用(5001)');
        }

        $rate =100;
        $PayRateM = new PayRate();
        $payRateInfo = $PayRateM->where('admin_id',$adminInfo['id'])->where('pay_type',$paytype)->find();
        if (!$payRateInfo)
        {
            if ($payTypeInfo['access']==1)
            {
                $rate = $payTypeInfo['rate'];
            }else{
                $this->error('支付通道不可用(5002)');
            }
        }else{
            $rate = $payRateInfo['rate'];
        }

        $payTypeInfo['config'] = json_decode($payTypeInfo['config'],true);
        //扩展数据
        $data['status']=0;
        $data['timestamp']=$data['createtime'];
        $data['realprice']=$data['price'];
        $data['admin_id']=$adminInfo['id'];
        $data['rate']=$rate;

        $data['admin_id']=$adminInfo['id'];

        $payConfig = Config::get("payment");
        //订单手续费
        $rate_cost = $data['realprice'] * $rate*0.01;
        //单笔最低手续费（元）
        if (isset($payConfig['min_rate_sost']) && $payConfig['min_rate_sost'] >=$rate_cost)
            $rate_cost = $payConfig['min_rate_sost'];

        $data['cost']=$rate_cost;

        //最小金额
        if (isset($payTypeInfo['config']['min_price']) && $payTypeInfo['config']['min_price'] >$data['realprice'])
            $this->error("支付金额必须大于".$payTypeInfo['config']['min_price']);

        //最大金额
        if (isset($payTypeInfo['config']['max_price']) && $payTypeInfo['config']['max_price'] <$data['realprice'])
            $this->error("支付金额不能大于".$payTypeInfo['config']['max_price']);


        //保存订单
        $orderM = new PayOrder();
        try {
            $res = $orderM->insert($data);
            if (!$res)
            {
                $this->error('系统繁忙，请刷新后再试');
            }

        } catch (Exception $e){
//            echo $e->getMessage();
            $this->error('系统繁忙，请刷新后再试');
        }

        //单位换算
        if (isset($payTypeInfo['config']['price_decimal']))
        {
            $data['realprice'] = $data['realprice'] * $payTypeInfo['config']['price_decimal'];
            $data['price'] = $data['price'] * $payTypeInfo['config']['price_decimal'];
        }



        //发起支付
//        $payConfig = Config::get("payment");
        $postData=array();
        //交易最低金额
        /*if (isset($payConfig['min_price']) && $payConfig['min_price'] >$data['realprice'])
            $this->error("支付金额必须大于".$payConfig['min_price']);*/

        if (!empty($paytype))
        {

            $postData['out_trade_no'] = $data['out_order_id'];
            $postData['title'] = $data['title'];
            $postData['amount'] = $data['realprice'];
            $postData['type'] = 'alipay';
            $postData['method'] = 'wap';
            $postData['notifyurl'] = $data['notifyurl'];
            $postData['returnurl'] = $data['returnurl'];
            $postData['key'] = '123';

            $API = new \addons\epay\controller\Apiv8;
            $out_trade_no = $postData['out_trade_no'];
            $amount = $postData['amount'];
            $title = $postData['title'];
            $key = '123';
            echo $API->submit_b($out_trade_no,$amount,$title,$key);
            die();
            
        }
        $this->error('支付通道异常');
    }


    public function build_sign_str($data){
        $str='';
        if(is_array($data)){
            ksort($data);
            foreach ($data as $key=>$value){
                $str .= $value;
            }
        }
        return $str;
    }


    //定时检测订单是否支付
    public function check_paid(){
        $orderList = DB::table('fa_pay_order')->field('id,out_order_id,txid,from_address,to_address,price')->where(['status'=>0])->select();

        //循环处理
        foreach ($orderList as $k=>$v){
            if(!empty($v['txid'])){
                $payConfig = Config::get("payment");
                $url = 'https://services.tokenview.com/vipapi/tx/eth/'.$v['txid'].'?apikey='.$payConfig['sys_tokenview_key'];
                $eth_res = json_decode(file_get_contents($url),true);

                if($eth_res['code'] == 1 && !empty($eth_res['data']['tokenTransfer'])){
//                    if($eth_res['data']['tokenTransfer'][0]['from'] != $v['from_address']){
//                        echo "<br />订单号：".$v['out_order_id']."，实际转出地址与订单信息不一致";
//                    }else
                    if($eth_res['data']['tokenTransfer'][0]['to'] != $v['to_address']){
                        echo "<br />订单号：".$v['out_order_id']."，实际转入地址与订单信息不一致";
                    }else if($v['price'] != ($eth_res['data']['tokenTransfer'][0]['value'] / 1000000)){
                        echo "<br />订单号：".$v['out_order_id']."，实际转账金额与订单信息不一致";
                    }else{
                        //通知商户
                        $result = Service::handleOrder($v['id']);

                        if ($result) {
                            echo "<br />订单号：".$v['out_order_id']."，收款成功";
                        } else {
                            echo "<br />订单号：".$v['out_order_id']."，收款失败";
                        }
                    }

                }else{
                    echo "<br />订单号：".$v['out_order_id']."，未查询到链上交易信息";
                }
            }else{
                echo "<br />订单号：".$v['out_order_id']."，未填写txid";
            }
        }
    }

}
