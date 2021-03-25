<?php
/**
 * Created by PhpStorm.
 * User: M
 * Date: 2019/8/19 0019
 * Time: 16:41
 */

namespace app\Tools\AllinPay;


class Sdk
{
    const PAY_HOST = 'http://www.bbw.ph';
    const USERNAME = 'user01';
    //md5秘钥
    private $key = 'XXXXXXX';

    private $params = [];


    /**
     * 支付
     * @param $orderId
     * @param $amount
     * @param $pay_channel
     * @param $notifyUrl
     * @param string $redirectUrl
     * @param string $currency
     * @return mixed
     */
    public function payment($orderId, $amount, $pay_channel, $notifyUrl, $redirectUrl = '', $currency = 'CNY')
    {
        $this->params['userName'] = self::USERNAME;
        $this->params['orderNumber'] = $orderId;
        $this->params['amount'] = $amount;
        $this->params['channelType'] = $pay_channel;
        $this->params['notifyUrl'] = $notifyUrl;
        $this->params["sign"] = AppUtil::SignArray($this->params, $this->key);//签名
        $this->params['currency'] = $currency;
        $this->params['redirectUrl'] = $redirectUrl;
        $url = self::PAY_HOST . '/bbPay/orderInfoMd5';
        $paramsStr = http_build_query($this->params);
        $rsp = $this->request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        return $rspArray;
    }


    /**
     * 订单查询
     * @param $orderId
     * @return mixed
     */
    public function queryOrderInfo($orderId)
    {
        $this->params['userName'] = self::USERNAME;
        $this->params['orderNumber'] = $orderId;
        $this->params["sign"] = AppUtil::SignArray($this->params, $this->key);//签名
        $url = self::PAY_HOST . '/bbPay/orderdDetailsMd5';
        $paramsStr = http_build_query($this->params);
        $rsp = $this->request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        return $rspArray;
    }

    /**
     * 通知回调验签
     * @param $notifyData
     * @return array
     */
    public function notifyData($notifyData)
    {
        $params = $this->_optionData($notifyData);
        if (count($params) < 1) {//如果参数为空,则不进行处理
            return ['code' => 401, 'data' => '', 'msg' => '参数错误'];
        }
        if (AppUtil::ValidSign($params, $this->key)) {
            return ['code' => 200, 'data' => $notifyData];
        } else {
            return ['code' => 401, 'data' => '', 'msg' => '验签失败'];
        }
    }

    /**
     * 组装参数
     * @param $notifyData
     * @return array
     */
    private function _optionData($notifyData)
    {
        $params = [];
        $params['userName'] = $notifyData['userName'];
        $params['orderNumber'] = $notifyData['orderNumber'];
        $params['amount'] = $notifyData['amount'];
        $params['channelType'] = $notifyData['channelType'];
        $params['tradeOrderNo'] = $notifyData['tradeOrderNo'];
        return $params;
    }

    //发送请求操作仅供参考,不为最佳实践
    private function request($url, $params, $timeout = 50)
    {
        $reqid = time() . '_' . mt_rand(100, 999);
        //log('pay/sdk', "reqid={$reqid};params={$params}");
        $ch = curl_init();
        $this_header = array("Content-Type:application/x-www-form-urlencoded;");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//如果不加验证,就设false,商户自行处理
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // for in IPv4
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        //log('pay/sdk', "reqid={$reqid};params={$params}");
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}