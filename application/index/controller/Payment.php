<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use app\common\model\PayOrder;
use app\admin\model\Admin;

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
        if($orderM->where(['out_order_id'=>$id])->update(['to_address'=>$adminInfo['usdt_address'],'txid'=>$txid])){
            header("Location:" . $orderInfo['returnurl']);
        }else{
            $this->error('提交失败');
        }
    }
}
