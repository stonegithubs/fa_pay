<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use app\common\model\PayOrder;
use app\admin\model\Admin;
use fast\Http;
use app\admin\library\Service;
use addons\epay\controller\Api;
use Exception;

class Payment extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function _initialize()
    {
//        parent::_initialize();
    }

    public function index()
    {
        $id = $this->request->request('id');

        //查询订单信息
        $orderM = new PayOrder();
        $orderInfo = $orderM->where(['out_order_id'=>$id])->find();

        if(empty($orderInfo)){
            $this->error('订单不存在');
        }

        if(!empty($orderInfo['txid'])){
            $this->error('此订单已提交');
        }

        $this->view->assign('id', $id);
        $this->view->assign('order', $orderInfo);

        return $this->view->fetch();
    }

    public function exchange()
    {
        $id = $this->request->request('id');
        $username = $this->request->request('username');
        $password = $this->request->request('password');

        if(empty($username)){
            $this->error('username不能为空');
        }

        if(empty($password)){
            $this->error('password不能为空');
        }

        //查询订单信息
        $orderM = new PayOrder();
        $orderInfo = $orderM->where(['out_order_id'=>$id])->find();

        if(empty($orderInfo)){
            $this->error('订单不存在');
        }

        if(!empty($orderInfo['txid'])){
            $this->error('此订单已提交txid');
        }

        //交易所登录
        $login_url ='http://api.otc9xyz.com/uc/loginForFaPay';
        $res = json_decode(Http::post($login_url,[
            'username' => $username,
            'password' => $password,
        ]),true);

        //判断
        if($res['code'] != 0){
            $this->error('登录失败');
        }
        if($res['message'] < $orderInfo['realprice']){
            $this->error('交易所余额不足');
        }

        //扣除交易所余额
        $pay_url ='http://api.otc9xyz.com/uc/exchangeForFaPay';
        $res = json_decode(Http::post($pay_url,[
            'username' => $username,
            'password' => $password,
            'amount' => $orderInfo['realprice'],
        ]),true);

        if($res['code'] == 0){
            //保存支付方式
            $orderM->where(['out_order_id'=>$id])->update(['extend'=>json_encode(['bipay_type'=>2])]);
            //回调商户
            Service::handleOrder($orderInfo['id']);
            //跳转商户同步地址
            header("Location:" . $orderInfo['returnurl']);
        }else{
            $this->error('提交失败');
        }
    }

    public function chain()
    {
        $id = $this->request->request('id');

        //查询订单信息
        $orderM = new PayOrder();
        $orderInfo = $orderM->where(['out_order_id'=>$id])->find();

        if(empty($orderInfo)){
            $this->error('订单不存在');
        }

        if(!empty($orderInfo['txid'])){
            $this->error('此订单已提交');
        }

        //获取商户充值地址
        $adminM  = new Admin();
        $adminInfo = $adminM->where('appid', $orderInfo['appid'])->find();

        if(empty($adminInfo['usdt_address'])){
            $this->error('支付通道不可用');
        }

        $this->view->assign('address', $adminInfo['usdt_address']);
        $this->view->assign('order', $orderInfo);

        return $this->view->fetch();
    }

    public function submit()
    {
        $id = $this->request->request('id');
        $txid = $this->request->request('txid');

        if(empty($txid)){
            $this->error('txid不能为空');
        }

        //查询订单信息
        $orderM = new PayOrder();
        $orderInfo = $orderM->where(['out_order_id'=>$id])->find();

        if(empty($orderInfo)){
            $this->error('订单不存在');
        }

        //查询txid是否已存在
        if(!empty($orderM->where(['txid'=>$txid])->find())){
            $this->error('此txid已被使用');
        }

        //获取商户充值地址
        $adminM  = new Admin();
        $adminInfo = $adminM->where('appid', $orderInfo['appid'])->find();

        //保存txid
        if($orderM->where(['out_order_id'=>$id])->update(['to_address'=>$adminInfo['usdt_address'],'txid'=>$txid,'extend'=>json_encode(['bipay_type'=>1])])){
            header("Location:" . $orderInfo['returnurl']);
        }else{
            $this->error('提交失败');
        }
    }

    public function getAccount()
    {
        //交易所注册
        $register_url ='http://api.biki51.cc/uc/register/registerBySJ';
        $res = json_decode(Http::post($register_url),true);

        //判断
        if($res['code'] != 0){
            $this->error('注册失败');
        }

        $username = substr($res['message'],9);

        $this->success($username);
    }

    public function registerAccount()
    {
        $username = $this->request->request('username');
        $password = $this->request->request('password');
        $orderNo = $this->request->request('orderNo');
        $code = $this->request->request('code');

        //交易所注册
        $register_url ='http://api.biki51.cc/uc/register/registerBySd';
        $res = json_decode(Http::post($register_url,[
            'mobile' => $username,
            'password' => $password,
            'orderNo' => $orderNo,
            'code' => $code,
        ]),true);

        //判断
        if($res['code'] != 0){
            $this->error('login has failed');
        }else{
            $this->success();
        }
    }

    public function sendMail()
    {
        $username = $this->request->request('username');


        if(is_numeric($username)){//如果传来的是数字则判断是否是手机
            if(preg_match('/^1[34578]{1}\d{9}/',$username)){
                //手机验证码
                $url = 'http://api.biki51.cc/uc/mobile/code';

                $register_url ='http://api.biki51.cc/uc/register/registerBySd';
                $res = json_decode(Http::post($url,[
                    'phone' => $username,
                    'country' => '中国',
                ]),true);

                //判断
                if($res['code'] != 0){
                    $this->error('operation failed');
                }else{
                    $this->success();
                }
            }
        }else{//否则判断是否是邮箱
            if(filter_var($username,FILTER_VALIDATE_EMAIL)){
                //邮箱验证码
            }
        }

        $this->error('Please enter the correct mobile phone number or email');
    }


    /**
     * 交易所回调
     */
    public function notifyx()
    {
        header('Content-Type: application/json');
//        $params = $this->request->request();

        $content = file_get_contents('php://input');
        $params    = (array)json_decode($content, true);

        if(empty($params['amount']) || empty($params['orderNo'])){
            exit(json_encode(['code'=>-1,'msg'=>'参数有误']));
        }

        $amount = $params['amount'];
        $orderNo = $params['orderNo'];

        try {
            //$logM->addLog('ok','api_v3/notifyx');
            $OrderM = new PayOrder();

            //订单详情
            $where = array();
            $where['out_order_id'] = $orderNo;
            $orderInfo = $OrderM->where($where)->find();
            if(!$orderInfo){
                exit(json_encode(['code'=>-1,'msg'=>'订单不存在']));
            }

            if (!in_array($orderInfo['status'], [0,1])) {
                exit(json_encode(['code'=>-1,'msg'=>'订单已支付']));
            }

            //修改订单状态
            $myOrder = array();
            $myOrder['status'] = 2;//已经支付
            $myOrder['paydate'] = date('Y-m-d H:i:s',time());
            $myOrder['realprice'] = $amount;
            $myOrder['paytime'] = time();
            $where = array();
            $where['out_order_id'] = $orderNo;
            $where['status'] = array('in','0,1');
            $OrderM->where($where)->update($myOrder);

            //下发商户通知
            $result = \app\admin\library\Service::notify($orderInfo['id']);

            $APiC = new Api();
            //扣除费率及记账
            $APiC->dealServiceCharge($orderInfo);



            //你可以在此编写订单逻辑
            exit(json_encode(['code'=>1,'msg'=>'success']));
        } catch (Exception $e) {

        }
    }
}
