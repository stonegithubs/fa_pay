<?php
require 'Config.php';
require 'Request.php';
if($_POST){

    $params = [
        'appid'         => isset($_POST['appid']) ? $_POST['appid'] : '',
        'paytype'       => isset($_POST['paytype']) ? $_POST['paytype'] : '',
        'title'         => isset($_POST['title']) ? $_POST['title'] : '',
        'out_order_id'  => isset($_POST['out_order_id']) ? $_POST['out_order_id'] : '',
        'sys_order_id'  => isset($_POST['sys_order_id']) ? $_POST['sys_order_id'] : '',
        'realprice'     => isset($_POST['realprice']) ? $_POST['realprice'] : '',
        'paytime'       => isset($_POST['paytime']) ? $_POST['paytime'] : '',
        'paydate'       => isset($_POST['paydate']) ? $_POST['paydate'] : '',
        'extend'        => isset($_POST['extend']) ? $_POST['extend'] : '',
    ];


    $sign = isset($_POST['sign']) ? $_POST['sign'] : '';
    if (empty($sign))
        return 'sign error';
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

}
?>
