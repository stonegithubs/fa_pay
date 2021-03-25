<?php

	/*
		测试发起支付
		uid  : 431761815291035648
		token: b75b99c6dd194be391dac4150251560d
	*/

	//$url = "http://tpay.tcwx.net/api/v1/getway/";
	$url = "http://cofip8.iee2004.com:8188/F/Pay/backTransReq.ashx";
	$postData = [
		'merNo' 		=> 	'10060066',
		'merOrderNo'  		=> 	'E202002072326528650',
		'notifyUrl' 		=> 	'http://www.xhd9.com/addons/epay/apiv20/notifyx',
		'payType'		=> 	'A57',
		'requestNo'		=>	'E202002072326528650',
		'serviceType'	=>	'qrcodeReceipt',
		'signType'	=>	'md5',
		'tradeAmt'	=>	1,
		'version' => 'v1.0',
		'signature'			=>	'172a763b6afa53ee1f1a1425a2a54a6d'
	];

	
	$result = httpPost($url,json_encode($postData),'POST');
	var_dump($result);

	function httpPost($url,$postData,$type){
		 //初使化init方法
	   $ch = curl_init();
	   //指定URL
	   curl_setopt($ch, CURLOPT_URL, $url);
	   //设定请求后返回结果
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   //声明使用POST方式来进行发送
	   curl_setopt($ch, CURLOPT_POST, 1);
	   //发送什么数据呢
	   curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	   //忽略证书
	   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	   //忽略header头信息
	   curl_setopt($ch, CURLOPT_HEADER, 0);
	   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	            'Content-Type: application/json; charset=utf-8'
	        )
	    );
	   //设置超时时间
	   curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	   //发送请求
	   $output = curl_exec($ch);
	   //关闭curl
	   curl_close($ch);
	   //返回数据
	   return $output;
	}
