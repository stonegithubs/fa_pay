<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '20212048471426', // 商户应用id
    'appsecret' => 'YyiHPpdaMZjckWEJ4j6TN2wHFJhNtw7T', // 商户MD5签名密钥
    'gateway_url' => 'http://www.fapay.com/api/pay/submit ', // 创建订单接口
    'paytype'=>'bipay'
    ];

    ?>