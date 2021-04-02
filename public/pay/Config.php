<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '20219290923059', // 商户应用id
    'appsecret' => 'CZBTANR3DjB4eMjjSdjFY8ZeWTH47n5T', // 商户MD5签名密钥
    'gateway_url' => 'http://pay.biki51.cc/api/pay/submit ', // 创建订单接口
    'paytype'=>'bipay'
    ];

    ?>