<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '20191394959278', // 商户应用id
    'appsecret' => '2ehdzGMajPeKpsiM7wBjrwdWzaQMN5Dm', // 商户MD5签名密钥
    'gateway_url' => 'http://www.xhd9.com/api/pay/submit ', // 创建订单接口
    'paytype'=>'alipay_wap'
    ];

    ?>