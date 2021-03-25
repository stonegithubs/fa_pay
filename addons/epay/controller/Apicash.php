<?php

namespace addons\epay\controller;

use think\addons\Controller;
use panshi\CurlHttp;
use panshi\ParamCheckTool;



class Apicash extends Controller
{

    protected $layout = 'default';

    protected $config = [];

    protected $paytype = 'alipay_v18';//支付标识

    protected $token = null;

    public static $encoding = "UTF-8";

    public static $mer_private_key = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAJZwiZr+51cvXuBBZKe4lbMSANzlGQiLHugpF8aqJkqImdSZlJ9MTxGJr2qHKZAIIjYL4ExOEqP33veBCjZMCPieMYl76mReGO6CZAwqmyqLVg0KAga5ZRmu72NnmeXvY1+D0tU8l5ISF+O8/hvel2oYckLTsej7aJEsPr+Ql59FAgMBAAECgYA9ZlkDRZ4t20uhw47NVWzj1Sk8/tQkxIMsxfjKQI+4Q+BlAPnDumVbBxLtK5UvD+LGpDo7anH5MiVyZtxAJPBTyCKuGgV8UVp7YjY9SpDc9+5UIlW0Lp4X+83amdAjyuD5rxhcZdgQBr2zLKDA8lHiql5UkEukPuAOL7U868jWUQJBAMhlkCt83yRinVOiFFzlobnpiO/9eSw2lEczgJeeEwJIJHMZesxKinHBCBAa5biOTec2p7XTPzbUPykX1tDtdFMCQQDALnSKi7QYYQPYB9by5lmbc4VvaPvrQphMiQXYQ9G/sfJJyOav9iWwq3IbLqcXzi7LqGrHMc8lgnhf11nAQqsHAkEAq1UqTgQVRCaMHFUW09YAz9K7IXS1hPelDrsZ1odv+SN1BnNiagfRFjDTk/FrNr90G5q/CNXz1gzhc2DOaRKbwQJAekCTDldUl3WPlmtWR2pVclgIeBjWuI/Chl1cBHkQAtvV/y334dO5oitOCRCiZrhYeGGIm3KKDZhzrrQ1eeJvHQJAZyB6tVHLIHvaJFSIh3KqIS0AZeqXJhltMjklBcW8ozpEJ9EkhUYwP/tCvBfcwJq/VRrhEMivwyAYH8sM5bmFHA==";

    public static $mer_public_key = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCWcIma/udXL17gQWSnuJWzEgDc5RkIix7oKRfGqiZKiJnUmZSfTE8Ria9qhymQCCI2C+BMThKj9973gQo2TAj4njGJe+pkXhjugmQMKpsqi1YNCgIGuWUZru9jZ5nl72Nfg9LVPJeSEhfjvP4b3pdqGHJC07Ho+2iRLD6/kJefRQIDAQAB";

    public static $org_public_key = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCkPjQLZqXTjxqa9BlnL0N7u/+1yYqAmh+quvpRPjuSfI2Aro6lHlI/2HMHZj5Kc59x7t5B0ksJBbcrUUCwtg0E7QOsmhTTdM6HSzLbt6CyF5CqIz8V4XiFCeb4SJeIX2BiB2XRwjNa6VWHFzAi0ggqz7TbWkBFMKMDKF+lK5RTzwIDAQAB";


    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        exit;
    }


    public function docash()
    {
        $map = array();

        $map['payeeAcc'] = '6228480018088256070';//银行卡号
        $map['payeeName'] = '叶磊';//姓名
        $map['payeeBankName'] = '农业银行';//银行名称
        $map['txnamt'] = '16584';//金额-单位：分
        $map['orderId'] = 'E1907051895';//订单号-随机填-不重复就行
      
        $map['partnerId'] = '201951';
        $map['tranCod'] = '0500';
        $map['tranType'] = '120000';
        $map['mercid'] = '878110148126018';
        $map['spbill_ip'] = '127.0.0.1';//默认
        $map['txnTime'] = date('YmdHis');
        $map['payeeBranchName'] = '广东省分行';//默认
        $map['payeeSubBranchName'] = '东山分行';//默认
        $map['province'] = '北京市';//默认
        $map['city'] = '北京市';//默认


        $map['payeePhone'] = '13826071234';//默认
        $map['payeeIdNum'] = '411325199912201234';//默认
        $map['privateFlag'] = 'S';//对公对私    G：对公    S：对私
        $map['currency'] = 'CNY';//默认
        $map['chargeType'] = '1';//默认
        $map['notifyUrl'] = 'http://127.0.0.1/posm/wft_notify.tran';
        $map['remark'] = '1';
        $map['lbnkNo'] = '120000000012';//默认
        $map['lbnkNm'] = '中国银行';//默认
        $req_url = "http://pay.gdpanshi.com/posm/newpay_withdraw.tran";
        $signkey = "659652D073865E55B2F1F78B1859D8B0";

        $sendData = ParamCheckTool::sendPack($map, $signkey, self::$mer_private_key, self::$org_public_key);

        $res = CurlHttp::quickRequest([
            'url'=>$req_url . "?" . $sendData
        ]);

        $resStr = $res['response'];
        $paramData = ParamCheckTool::checkRes($resStr, $signkey, self::$mer_private_key, self::$org_public_key);
        echo $paramData;

    }
}


call_user_func(function () {
    spl_autoload_register(function($class_name){
        $namespace = 'panshi\\pay\\';
        $ms_length = strlen($namespace);

        if (substr($class_name, 0, $ms_length) != $namespace) {
            return false;
        }

        $class = substr($class_name, $ms_length);
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $dir = __DIR__ .'/../';
        $file = $dir .  DIRECTORY_SEPARATOR . $path . '.php';

        if (file_exists($file)) {
            require_once $file;
        }

        return class_exists($class_name, false);
    });
});
