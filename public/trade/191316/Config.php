<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '20193970673021', // 商户ID
    'appsecret' => 'dWAc3mDy2YtPR55H2r8jHzfxystDhzmy', // 商户MD5签名密钥
    'gateway_url' => 'http://www.xhd9.com/api/pay/submit ', // 支付网关地址
    'default_paytype'=>'alipay_wap',//默认支付方式
    'shop_name'=>'myShop'//我的店铺名称
    ];

    //目前支持的支付方式
	$list_paycode = [
	    'alipay_wap' => '支付宝',
	];

    ?>