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
if (empty($sign))
{
    echo 'sign error';
    exit(0);
}
$sign_str = Request::build_sign_str($params). $config['appsecret'];

if ($sign!==md5($sign_str))
{
    echo 'sign error';
    exit(0);
}
else
{
    //处理逻辑，修改订单状态等


    echo 'success';//验证成功后，请返回'success'
    exit(0);
}

?>
