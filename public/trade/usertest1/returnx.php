<?php
require 'Config.php';
require 'Request.php';


$params = [
    'appid'         => isset($_GET['appid']) ? $_GET['appid'] : '',
    'paytype'       => isset($_GET['paytype']) ? $_GET['paytype'] : '',
    'title'         => isset($_GET['title']) ? $_GET['title'] : '',
    'out_order_id'  => isset($_GET['out_order_id']) ? $_GET['out_order_id'] : '',
    'sys_order_id'  => isset($_GET['sys_order_id']) ? $_GET['sys_order_id'] : '',
    'realprice'     => isset($_GET['realprice']) ? $_GET['realprice'] : '',
    'paytime'       => isset($_GET['paytime']) ? $_GET['paytime'] : '',
    'paydate'       => isset($_GET['paydate']) ? $_GET['paydate'] : '',
    'extend'        => isset($_GET['extend']) ? $_GET['extend'] : '',
];

$sign = isset($_GET['sign']) ? $_GET['sign'] : '';
$statusText='error';
$statusMsg = '签名错误';
$sign_str = Request::build_sign_str($params). $config['appsecret'];

if ($sign===md5($sign_str))
{
    $statusText='success';
    $statusMsg = '支付成功';

}





?>


<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>收银台</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style type="text/css">
        *{box-sizing:border-box;margin:0;padding:0;font-family:Lantinghei SC,Open Sans,Arial,Hiragino Sans GB,Microsoft YaHei,"微软雅黑",STHeiti,WenQuanYi Micro Hei,SimSun,sans-serif;-webkit-font-smoothing:antialiased}
        body{padding:70px 0;background:#edf1f4;font-weight:400;font-size:1pc;-webkit-text-size-adjust:none;color:#333}
        a{outline:0;color:#3498db;text-decoration:none;cursor:pointer}
        .system-message{margin:20px 5%;padding:40px 20px;background:#fff;box-shadow:1px 1px 1px hsla(0,0%,39%,.1);text-align:center}
        .system-message h1{margin:0;margin-bottom:9pt;color:#444;font-weight:400;font-size:40px}
        .system-message .jump,.system-message .image{margin:20px 0;padding:0;padding:10px 0;font-weight:400}
        .system-message .jump{font-size:14px}
        .system-message .jump a{color:#333}
        .system-message p{font-size:9pt;line-height:20px}
        .system-message .btn{display:inline-block;margin-right:10px;width:138px;height:2pc;border:1px solid #44a0e8;border-radius:30px;color:#44a0e8;text-align:center;font-size:1pc;line-height:2pc;margin-bottom:5px;}
        .success .btn{border-color:#69bf4e;color:#69bf4e}
        .error .btn{border-color:#ff8992;color:#ff8992}
        .info .btn{border-color:#3498db;color:#3498db}
        .copyright p{width:100%;color:#919191;text-align:center;font-size:10px}
        .system-message .btn-grey{border-color:#bbb;color:#bbb}
        .clearfix:after{clear:both;display:block;visibility:hidden;height:0;content:"."}
        @media (max-width:768px){body {padding:20px 0;}}
        @media (max-width:480px){.system-message h1{font-size:30px;}}
    </style>
</head>
<body>

<div class="system-message <?=$statusText?>">
    <div class="image">
        <img src="static/<?=$statusText?>.svg" alt="" width="150" />
    </div>

    <?php
    if ($statusText=='success')
    {
        echo '<h4>金额：￥'.$params['realprice'].'</h4>';
        echo '<h4>订单号：'.$params['out_order_id'].'</h4>';
    }
    ?>

    <h1><?=$statusMsg?></h1>

</div>
<div class="copyright">

</div>

</body>
</html>
