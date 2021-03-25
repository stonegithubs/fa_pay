<?php
require 'Config.php';
require 'Request.php';
if($_POST){

    $data = [
        'appid' => $config['appid'], // 商户ID
        'paytype' => isset($_POST['paytype']) ? $_POST['paytype'] : $config['default_paytype'], // 支付方式
        'createtime' => time(), // 请求时间戳
        'price' => isset($_POST['price']) ? $_POST['price'] : 1, // 订单金额，单位元
        'title' => isset($_POST['title']) ? $_POST['title'] : 'goods', // 商品名称
        'out_order_id' => isset($_POST['out_order_id']) ? $_POST['out_order_id'] : 'E' . date('YmdHis') . rand(1000, 9999), // 商户订单号
        'extend' => isset($_POST['extend']) ? $_POST['extend'] : '', // 商户自定义字段
        'returnurl' => isset($_POST['returnurl']) ? $_POST['returnurl'] : "{$http_type}{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/returnx.php", // 前端通知地址
        'notifyurl' => isset($_POST['notifyurl']) ? $_POST['notifyurl'] : "{$http_type}{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/notifyx.php", // 异步通知地址
    ];



    // md5 签名
    $sign_str = Request::build_sign_str($data). $config['appsecret'];
    $data['sign'] = md5($sign_str);
    exit(Request::createForm($config['gateway_url'], $data));

}



$data = [
    'paytype' => $config['default_paytype'], // 支付方式
    'price' => 0.01, // 订单金额，单位元
    'title' => 'goods', // 商品名称
    'out_order_id' => 'E' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'extend' => '', // 商户自定义字段
    'returnurl' => "{$http_type}{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/returnx.php", // 前端通知地址
    'notifyurl' => "{$http_type}{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/notifyx.php", // 异步通知地址
];


?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>收银台</title>
    <style type="text/css">
        *{margin: 0;padding: 0;}
        html{font-size:20px}
        @media only screen and (min-width:320px){html{font-size:16.46px!important}}
        @media only screen and (min-width:360px){html{font-size:19.06px!important}}
        @media only screen and (min-width:375px){html{font-size:20.00px!important}}
        @media only screen and (min-width:400px){html{font-size:21.33px!important}}
        @media only screen and (min-width:414px){html{font-size:22.28px!important}}
        @media only screen and (min-width:480px){html{font-size:25.60px!important}}
        body,h1,h2,h3,h4,h5,h6,hr,p,dl,dt,dd,ul,ol,li,pre,form,fieldset,legend,button,input,textarea,th,td{margin:0;padding:0;font-family: Microsoft YaHei;}
        html,body{height: 100%;overflow-x:hidden ;}
        body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,form,input,select,option,p,th,td{-webkit-tap-highlight-color:rgba(0,0,0,0)}
        body{font:12px/1.5 Avenir,Heiti-SC,Monaco,tahoma, arial, \5b8b\4f53, sans-serif;-webkit-text-size-adjust:none; -webkit-user-select: none;}
        i,b,em{font-style:normal; font-weight:normal}
        img{border:0;vertical-align: middle;}
        table{border-collapse:collapse;border-spacing:0}
        li,ol,ul{list-style-type:none;}
        h1,h2,h3,h4,h5,h6{font-weight:normal;font-size:100%;}
        input,select,textarea{font-size:14px;}
        .content{background-color: #f5f5f5;font-size: 1.2rem;height:70%;}
        .cont1{overflow: hidden;padding: 2.0rem 0 0.5rem 0;color: #555;text-align: center;}
        .center{margin: auto;}
        .cont1_L{float: left;width: 2.5rem;height: 2.0rem;}
        .cont2_L{float: left;width: 4rem;height: 2.0rem;font-size: 1rem;}
        .cont1_L img{width: 80%;height: 80%;}
        .cont1_R{height: 2.0rem;}


        .cont2_R{height: 2.0rem;font-size: 1rem;}
        .cont1_text1{font-size: 1.1rem;height: 2.0rem;line-height: 2.0rem;color: #444;padding-left: 0.4rem;}
        .cont1_text2{height: 1rem;line-height: 1rem;color: #ababab;}
        .cont2{width: 85%;margin: auto;overflow: hidden;}
        .cont2_titL{float: left;font-size: 0.8rem;line-height: 2.0rem;color: rgb(153,153,153);}
        .cont2_titR{float: right;height: 2.0rem;line-height: 2.0rem;font-size: 1.4rem;}
        .cont2_titR span{font-size: 1.4rem;}
        .cont2_margin{margin-top: 2.0rem;}
        .desc{height: 2.0rem;line-height: 2.0rem;border: 0px;padding: 0px;margin: 0px;margin-top: 0px; width: 80%;margin-left: 1.0rem;}
        .placeholder{font-size: 1rem !important;color: #999;float: right;border-left: 1px solid #0a7fd9;padding-left: 0.2rem;height: 1.6rem;margin-top: 0.2rem;line-height: 1.6rem;}
        .cont2_tit{padding:0.8rem 0.5rem;border: 1px solid rgb(160,160,160);border-radius: 10px;overflow: hidden;background-color: white;}
        .cont3{font-size: 0.75rem;color: #888888;text-align: center;position: absolute;bottom: 13rem;width: 100%;}
        .btn {border: none;background-color:#f5f5f5;font-size: 0.75rem;color: #888888;border-bottom: 1px solid #888;}
        .key1 {float: left;width: 100%;overflow: hidden;text-align: center;position: fixed;bottom: 0;border-top: 1px solid #acabab;background-color: #FFFFFF;border-left: none;border-right: none;}
        tr{height: 3rem;}
        td{width: 25%;border: 1px solid #e0e0e0;}
        .key6{font-size: 0.9rem;}
        .immediate {color: #000000;border: 1px solid #e0e0e0;}
        .key7 img{width: auto;height: 0.5em;max-width: 100%;max-height: 100%;}
        .top{background-color: #21A6A1;color: #fff;border: 1px solid #21A6A1;opacity: 1.0;}

        /*正在加载中*/
        .onload{width: 100%;height: 100%;position: absolute;top: 0;z-index: 555;display: none;}
        .onload img{position: relative;top: 45%;left: 46%;z-index: 556;}

        #pay,#immediately{display: none;}

        .showInfo{color: #999;font-size: 0.85rem;margin-left: 1rem;margin-top: 0.5rem;}
        /*分期类别弹框*/
        #installmentTypeDiv {width: 100%;height: 100%;display: block;z-index: 10;position: fixed; display: none;}
        .bg {width: 100%;height: 100%;position: fixed;background-color:rgba(0, 0, 0, 0.3);}
        .bgwrapper {position:fixed;left:50%;top:50%;}
        .bgwrapper1 {background-color: white;position: absolute;left: -8.5rem;top: -6rem; width: 17rem; }
        .checkstandHd {width: 100%;}
        .selectTitle {color: #333333;font-size: 1.1rem;margin: 1rem 1.5rem 0.5rem 1.5rem;}
        .radio_each {height: 1.8rem;line-height: 1.8rem;font-size: 0.9rem;}
        .radio_each input {width: 0.7rem;height: 0.7rem;margin-right: 0.5rem;margin-left: 1rem;}
        .cancelBtn {float: right;margin-right: 1.5rem;font-size: 1rem;margin-bottom: 1rem;margin-top: 1rem;}
        .cancelBtn input {font-size: 0.95rem;border: none;outline: none;background-color: white;}
        #btnNo {color: #666666;margin-right: 2rem;}
        #btnOk {color: #0AA296;}

        /*分期金额要求弹框*/


        #select_type{position: absolute;right:7.5%;padding-right: 0.5rem;height:44px;width: 200px}
        .form-control{height:40px!important;font-size:16px!important;}
    </style>
</head>
<body>


<div class="page content">
    <div class="cont1">
        <div class="center">
            <img src="static/default/shop_logo2.png" /><span id="merName" class="cont1_text1"><?=$config['shop_name']?></span>
        </div>

    </div>
    <div class="cont2">
        <div class="cont2_tit">
            <div class="cont2_titL">
                金额
            </div>
            <div class="cont2_titR">
                &yen;&thinsp;<span id="amount"></span><span id="placeholder" class="placeholder"></span>
            </div>
        </div>
    </div>

    <div class="cont2 cont2_margin">
        <div class="cont2_tit">
            <div class="cont2_titL">
                方式
            </div>
            <div id="select_type">
                <select class="form-control" id="payType">
                    <?php

                    foreach ($list_paycode as $key=> $item):
                        ?>
                        <option value ="<?=$key?>"><?=$item?></option>

                    <?php endforeach;?>

                </select>
            </div>
        </div>
    </div>
    <div id="payDiv">
        <table border="1" cellspacing="" cellpadding="" class="key1">
            <tr>
                <td class="key7 enter">1</td>
                <td class="key7 enter">2</td>
                <td class="key7 enter">3</td>
                <td class="key7 enter" id="can_r"><img src="static/default/icon-delete2.png"/></td>
            </tr>
            <tr>
                <td class="key7 enter">4</td>
                <td class="key7 enter">5</td>
                <td class="key7 enter">6</td>
                <td class="key6 top" rowspan="3" style="opacity: 0.5;" ontouchend="nowPay()">立即支付</td>
            </tr>
            <tr>
                <td class="key7 enter">7</td>
                <td class="key7 enter">8</td>
                <td class="key7 enter">9</td>
            </tr>
            <tr>
                <td class="key7 enter" colspan="2">0</td>
                <td class="key7 enter" id="point">.</td>
            </tr>
        </table>
    </div>
</div>

<form action="index.php" method="post" autocomplete="off" id="payForm">
    <input type="hidden" name="price" id="post_price">
    <input type="hidden" name="paytype" id="post_payType">
</form>

<div id="loadingDiv" class="onload">
    <img src="static/default/loading.gif"/>
</div>
</body>
<script src="static/default/ajax.js" type="text/javascript" charset="utf-8"></script>
<script src="static/default/commonPay_b.js" type="text/javascript" charset="utf-8"></script>
<script src="static/default/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="static/default/clipboard.min.js" type="text/javascript" charset="utf-8"></script>

<link href="static/select/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="static/select/css/bootstrap-select.min.css">
<script src="static/select/js/bootstrap.min.js"></script>
<script src="static/select/js/bootstrap-select.min.js"></script>

<script type="text/javascript">
    function nowPay() {
        amount = document.getElementById("amount").innerText;
        payType = document.getElementById("payType").value;
        if (null == amount || amount === "" || isNaN(parseFloat(amount))) {
            alert("请输入付款金额");
            return;
        }
        if (amount <= 0) {
            alert("付款金额必须大于0");
            return;
        }
        $("#post_price").val(amount);
        $("#post_payType").val(payType);


        $("#payForm").submit();

    }
    check();
</script>
</html>
