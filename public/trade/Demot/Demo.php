<?php
include 'Sign.php';
use tool\Request as ToolReq;
//组装要签名的数组，sign_type不参与签名
$array = array(
    // 商户ID
    'm_id'=>'zhuanbei123', 
    // 固定值
    'methond'=>'submitorder',
    // 商户订单号
    'm_orderid'=>'E2019111922215855250', //商户订单号
    // 下单金额
    'hopeAmount' =>100,
    // 支付通道 alipay= 支付宝 wepay = 微信
    'paymentmode'=>'alipay',//支付通道
    // 自定义回调地址
    "notifyurl"=>'http://154.204.42.54:8001/Pay_NewZfbH5_notifyurl.html'
);

//实例化sgin类，并且传入apikey
$md5_Sign = new Sign('23fe97effdfbd57da636ad8c132dc47b');

//对key => value 进行 A-Z 排序，并返回 拼接字符串
$paramFilter = $md5_Sign->argSort($array);

//开始进行签名，
$md5Hash = $md5_Sign->md5Sign($paramFilter);

// 接最终请求的数据结构，可用于post 及 get
// 原始数组 + sign_type + sign 签名后的MD5值
$requestParam = http_build_query($array).'&sign_type=MD5&sign='.$md5Hash;
//$res = ToolReq::post2('http://www.xhd9.com/trade/Demot/Demo.php?',$requestParam);
//var_dump($res);
//die();
echo $paramFilter;
echo '<br/>';
echo htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].'/gateway/?'.$requestParam);
