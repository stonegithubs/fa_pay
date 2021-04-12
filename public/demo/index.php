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
//        'from_address' => isset($_POST['from_address']) ? $_POST['from_address'] : '', // 商品名称
//        'to_address' => isset($_POST['to_address']) ? $_POST['to_address'] : '', // 商品名称
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
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>基于amaze-ui的充值界面</title>
    <link rel="stylesheet" type="text/css" href="./static/css/amazeui.min.css" />
    <link rel="stylesheet" type="text/css" href="./static/css/main.css" />
</head>

<body>
<div class="pay">
    <!--主内容开始编辑-->
    <div class="tr_recharge">
        <div class="tr_rechtext">
            <p class="te_retit"><img src="./static/images/coin.png" alt="" />充值中心</p>
        </div>
        <form action="index.php" method="post" class="am-form" id="doc-vld-msg">
            <div class="tr_rechbox">
                <div class="tr_rechhead">
                    <img src="./static/images/ys_head2.jpg" />
                    <p>充值帐号：
                        <a>test</a>
                    </p>
                    <div class="tr_rechheadcion">
                        <img src="./static/images/coin.png" alt="" />
                        <span>当前余额：<span>0USDT</span></span>
                    </div>
                </div>
                <div class="tr_rechli am-form-group">
                    <ul class="ui-choose am-form-group" id="uc_01">
                        <li>
                            <label class="am-radio-inline">
                                <input type="radio"   name="price" value="10" required data-validation-message="请选择一项充值额度"> 10USDT
                            </label>
                        </li>
                        <li>
                            <label class="am-radio-inline">
                                <input type="radio" name="price" value="20"data-validation-message="请选择一项充值额度"> 20USDT
                            </label>
                        </li>

                        <li>
                            <label class="am-radio-inline">
                                <input type="radio" name="price" value="50" data-validation-message="请选择一项充值额度"> 50USDT
                            </label>
                        </li>

                        <li>
                            <label class="am-radio-inline">
                                <input type="radio" name="price" value="100" data-validation-message="请选择一项充值额度"> 100USDT
                            </label>
                        </li>

                        <li>
                            <label class="am-radio-inline">
                                <input type="radio" name="price" value="200" data-validation-message="请选择一项充值额度"> 200USDT
                            </label>
                        </li>

                        <li>
                            <label class="am-radio-inline">
                                <input type="radio" name="price" value="300" data-validation-message="请选择一项充值额度"> 300USDT
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="tr_paybox">
                <input type="submit" value="确认支付" class="tr_pay am-btn" />
            </div>
        </form>
    </div>
</div>

<script src="http://www.jq22.com/jquery/jquery-1.10.2.js"></script>
<script type="text/javascript" src="./static/js/amazeui.min.js"></script>
<script type="text/javascript" src="./static/js/ui-choose.js"></script>

<script type="text/javascript">
    // 将所有.ui-choose实例化
    $('.ui-choose').ui_choose();
    // uc_01 ul 单选
    var uc_01 = $('#uc_01').data('ui-choose'); // 取回已实例化的对象
    uc_01.click = function(index, item) {
        console.log('click', index, item.text())
    }
    uc_01.change = function(index, item) {
        console.log('change', index, item.text())
    }
    $(function() {
        $('#uc_01 li:eq(3)').click(function() {
            $('.tr_rechoth').show();
            $('.tr_rechoth').find("input").attr('required', 'true')
            $('.rechnum').text('10.00元');
        })
        $('#uc_01 li:eq(0)').click(function() {
            $('.tr_rechoth').hide();
            $('.rechnum').text('10.00元');
            $('.othbox').val('');
        })
        $('#uc_01 li:eq(1)').click(function() {
            $('.tr_rechoth').hide();
            $('.rechnum').text('20.00元');
            $('.othbox').val('');
        })
        $('#uc_01 li:eq(2)').click(function() {
            $('.tr_rechoth').hide();
            $('.rechnum').text('50.00元');
            $('.othbox').val('');
        })
        $(document).ready(function() {
            $('.othbox').on('input propertychange', function() {
                var num = $(this).val();
                $('.rechnum').html(num + ".00元");
            });
        });
    })

    $(function() {
        $('#doc-vld-msg').validator({
            onValid: function(validity) {
                $(validity.field).closest('.am-form-group').find('.am-alert').hide();
            },
            onInValid: function(validity) {
                var $field = $(validity.field);
                var $group = $field.closest('.am-form-group');
                var $alert = $group.find('.am-alert');
                // 使用自定义的提示信息 或 插件内置的提示信息
                var msg = $field.data('validationMessage') || this.getValidationMessage(validity);

                if(!$alert.length) {
                    $alert = $('<div class="am-alert am-alert-danger"></div>').hide().
                    appendTo($group);
                }
                $alert.html(msg).show();
            }
        });
    });
</script>
</body>

</html>