<?php
/**
 * Created by PhpStorm.
 * User: M
 * Date: 2019/8/19 0019
 * Time: 16:38
 */

namespace app\index\controller;

use app\Tools\AllinPay\Sdk;
class Index
{

    /**
     * 订单回调
     */
    public function orderNotify()
    {
        $allinPay = new Sdk();
        $notifyData = file_get_contents('php://input');
        $data = $allinPay->notifyData(json_decode($notifyData, true));
        if ($data['code'] == 200 && $data['data']['status'] == 'PAYED') {
            var_dump('支付成功');
        }
        echo 'SUC';
    }


    /**
     * 支付接口
     */
    public function payment()
    {
        $allinPay = new Sdk();
        $orderId = '111111'; //订单号
        $amount = '100'; //支付金额
        $pay_channel = 13; //支付渠道
        $notifyUrl = 'http://www.baidu.com'; //通知地址
        $redirectUrl = 'http://www.baidu.com'; //跳转地址
        $res = $allinPay->payment($orderId, $amount, $pay_channel, $notifyUrl, $redirectUrl);
        var_dump($res);
    }


    /**
     * 支付结果查询
     */
    public function queryOrderInfo()
    {
        $allinPay = new Sdk();
        $orderId = '111111'; //订单号
        $res = $allinPay->queryOrderInfo($orderId);
        var_dump($res);
    }


}
