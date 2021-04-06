<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '20212105808009', // 商户应用id
    'appsecret' => '2bfAW2n8MfMab6maRQkZw57Fh7eck8hK', // 商户MD5签名密钥
    'gateway_url' => 'http://'.$_SERVER['HTTP_HOST'].'/api/pay/submit ', // 创建订单接口
    'paytype'=>'bipay'
    ];

    ?>