<?php
require 'Config.php';
require 'Request.php';
if($_POST){

    $data = [
        'appid' => $config['appid'], // 商户应用id
        'paytype' => $config['paytype'], // 支付方式
        'createtime' => time(), // 请求时间戳
        'price' => isset($_POST['price']) ? $_POST['price'] : 1, // 订单金额，单位元
        'title' => isset($_POST['title']) ? $_POST['title'] : 'goods', // 商品名称
        'out_order_id' => isset($_POST['out_order_id']) ? $_POST['out_order_id'] : 'E' . date('YmdHis') . rand(1000, 9999), // 商户订单号
        'extend' => isset($_POST['extend']) ? $_POST['extend'] : '', // 商户自定义字段
        'returnurl' => isset($_POST['returnurl']) ? $_POST['returnurl'] : '', // 前端通知地址
        'notifyurl' => isset($_POST['notifyurl']) ? $_POST['notifyurl'] : '', // 异步通知地址
    ];

// 参数检查

    // md5 签名
    $sign_str = Request::build_sign_str($data). $config['appsecret'];
    $data['sign'] = md5($sign_str);
    exit(Request::createForm($config['gateway_url'], $data));

}



$data = [
    'price' => 0.01, // 订单金额，单位元
    'title' => 'goods', // 商品名称
    'out_order_id' => 'E' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'extend' => '', // 商户自定义字段
    'returnurl' => "{$http_type}{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/returnx.php", // 前端通知地址
    'notifyurl' => "{$http_type}{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/notifyx.php", // 异步通知地址
];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>demo</title>

    <link href="static/css/bootstrap.min.css?v=3.3.7" rel="stylesheet">
    <link href="static/css/font-awesome.min.css?v=4.7.0" rel="stylesheet">
    <link href="static/css/base.css" rel="stylesheet">
    <link href="static/css/bootstrap-select.css" rel="stylesheet">
    <?php
        if(Request::isMobile())
            echo '<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;" name="viewport"/>';
        ?>

</head>
<body>
<!-- Start Page Header -->
<div class="page-header">
    <h1 class="title">支付demo</h1>
</div>
<!-- End Page Header -->

<!-- START CONTAINER -->
<div class="container-widget">

    <!-- Start Row -->
    <div class="row col-md-3"></div>
    <div class="row col-md-6">

        <div class="col-md-12 col-lg-12">
            <div class="panel panel-default">

                <form action="index.php" method="post" autocomplete="off">
                    <div class="panel-title">
                        订单信息
                    </div>
                    <div class="panel-body">
                        <div class="form-group col-sm-12">
                            <label class="form-label control-label">订单号：</label>
                            <label class="form-label control-label"><?=$data['out_order_id']?></label>
                        </div>

                        <div class="form-group col-sm-12">
                            <label for="input3" class="form-label control-label">订单金额（单位元）</label>
                            <input type="text" name="price" value="<?=$data['price']?>" class="form-control" id="input3" placeholder="请输入订单金额..." required>
                        </div>
                        <div class="form-group col-sm-12">
                            <label for="input4" class="form-label control-label">商品名称</label>
                            <input type="text" name="title" value="<?=$data['title']?>" class="form-control" id="input4" placeholder="请输入商品金额..." required>
                        </div>
                        <div class="form-group col-sm-12">
                            <label for="input5" class="form-label control-label">自定义字段</label>
                            <input type="text" name="extend" value="<?=$data['extend']?>" class="form-control" id="input5" placeholder="请输入自定义字段...">
                        </div>


                        <div class="form-group col-sm-12">
                            <label for="input8" class="form-label control-label">前端通知地址</label>
                            <input type="text" name="returnurl" value="<?=$data['returnurl']?>" class="form-control" id="input8" placeholder="请输入前端通知地址...">
                        </div>
                        <div class="form-group col-sm-12">
                            <label for="input9" class="form-label control-label">异步通知地址</label>
                            <input type="text" name="notifyurl" value="<?=$data['notifyurl']?>" class="form-control" id="input9" placeholder="请输入异步通知地址...">
                        </div>
                        <div class="form-group col-sm-12">
                            <input type="hidden" name="out_order_id" value="<?=$data['out_order_id']?>">
                            <button type="submit" class="btn btn-default">提交</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

    </div>
    <div class="row col-md-3"></div>
    <!-- End Row -->
</div>
<!-- END CONTAINER -->
<!-- //////////////////////////////////////////////////////////////////////////// -->

<script src="static/js/jquery-3.3.1.min.js"></script>
<script src="static/js/bootstrap.min.js"></script>
<script src="static/js/bootstrap-select.js"></script>

<script>
    $(function () {
        $('select[name=paycode]').change(function () {
            var paycode = $(this).val();
            if(0 === paycode.indexOf('unionpay')){
                if($('#card_number').hasClass('hide')){
                    $('#card_number').removeClass('hide');
                }
            }else {
                if(!$('#card_number').hasClass('hide')){
                    $('#card_number').addClass('hide');
                }
            }
        });
    });
</script>

</body>
</html>
