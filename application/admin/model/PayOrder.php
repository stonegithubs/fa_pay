<?php

namespace app\admin\model;

use app\admin\model\pay\Type;
use think\Model;

class PayOrder extends Model
{
    // 表名
    protected $name = 'pay_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'paytime_text',
        'status_text',
        'paytype_text',
    ];


    public function getStatusList()
    {

        return [0 => __('Inprogress'), 1 => __('Expired'), 2 => __('Paid'), 3 => __('Unsettled'), 4 => __('Settled')];
    }

    public function getPaytypeTextAttr($value, $data)
    {

        $allPayType = Type::getPaytypeList();
        $value = isset($allPayType[$data['paytype']]['name']) ? $allPayType[$data['paytype']]['name'] : $data['paytype'];
        return $value;
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['createtime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPaytimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['paytime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPaytimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : ($value ? $value : null);
    }

    //手续费
    /*public function getCostAmountAttr($value, $data)
    {
        $order_id = $data['id'];
        $billInfo  = PayBill::getBill($order_id);
        $amount = 0;
        if ($billInfo)
        {
            $amount = $billInfo['amount'];
            if ($amount < 0)
                $amount = $amount * (-1);
        }
        return $amount;
    }*/

    //支付类型








}
