<?php

namespace app\common\model;

use think\Model;

/**
 * 模型
 */
class PayOrder extends Model
{

    // 表名,不含前缀
    protected $name = 'pay_order';


    public static function getTypeList()
    {
        $typeList = [
            'alipay_wap'   => '支付宝原生H5',
            'alipay_pc'     => '支付宝原生PC',
        ];
        return $typeList;
    }


}
