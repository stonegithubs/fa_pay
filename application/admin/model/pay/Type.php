<?php

namespace app\admin\model\pay;

use think\Model;

class Type extends Model
{
    // 表名
    protected $name = 'pay_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];


    public static function getPaytypeList()
    {
        $all = self::field('id,type,name,rate,status')->select();
        if ($all)
        {
            $temp = array();
            foreach ($all as $item)
            {
                $temp[$item['type']] = $item;
            }
            $all = $temp;
        }else{
            $all = array();
        }
        return $all;
    }
    

    







}
