<?php

    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

    $config = [

    'appid' => '20193970673021', // 商户ID
    'appsecret' => 'dWAc3mDy2YtPR55H2r8jHzfxystDhzmy', // 商户MD5签名密钥
    'gateway_url' => 'http://www.xhd9.com/api/pay/submit', // 支付网关地址
    'default_paytype'=>'',//默认支付方式
    'shop_name'=>'测试商户'//我的店铺名称
    ];

    require 'Request.php';
    $getTypeUrl = 'http://dt.yelei.org/api/trade/get_paytype';//获取可用支付通道
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


    //目前支持的支付方式
/*	$list_paycode = [
//        'wx_wap_v1' => '微信wap_v1',
       'wx_sm_v2' => '微信支付',
       'alipay_v11' => '支付宝H5',
       // 'alipay_wap_v3' => '支付宝wap_v3',
        //'alipay_wap_v4' => '支付宝wap_v4',
        'alipay_wap_v5' => '支付宝扫码',
       // 'alipay_wap_v6' => '支付宝原生',
	    'alipay_wap' => '支付宝官方',
        //'alipay_wap_v2' => '支付宝wap_v2',
        'alipay_wap_v9' => '支付宝原生',
	];*/

    ?>