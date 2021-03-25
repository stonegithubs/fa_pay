<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '', // 商户ID
    'appsecret' => '', // 商户MD5签名密钥
    'gateway_url' => 'http://www.xhd9.com/api/pay/submit ', // 支付网关地址
    'default_paytype'=>'',//默认支付方式
    'shop_name'=>'测试商户'//我的店铺名称
    ];

    require 'Request.php';
    $getTypeUrl = 'http://www.xhd9.com/api/trade/get_paytype';//获取可用支付通道
    $getTypeData = array();
    $getTypeData['appid'] = $config['appid'];
    $getTypeData['sign'] = md5($config['appid'].$config['appsecret']);
    $list = Request::post($getTypeUrl,$getTypeData);
    $list_paycode = array();
    if ($list && isset($list['code']) && $list['code']==1)
    {
        $list = $list['msg'];
    }
    $config['default_paytype'] = $list[0]['type'];
    foreach ($list as $key=> $item)
    {
        if (is_array($item))
            $list_paycode[$item['type']] = $item['name'];
    }





    ?>