<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '20218276135119', // 商户应用id
    'appsecret' => 'dGzJSH5cbKBbsis8FZ3Zh8wyAk7pHX2b', // 商户MD5签名密钥
    'gateway_url' => 'http://'.$_SERVER['HTTP_HOST'].'/api/pay/submit ', // 创建订单接口
    'paytype'=>'bipay'
    ];

    ?>