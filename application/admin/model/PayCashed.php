<?php

namespace app\admin\model;

use think\Model;

class PayCashed extends Model
{
    // 表名
    protected $name = 'pay_cashed';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';


    public function withAdminInfo()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id'); //关联模型名，外键名，关联模型的主键
    }

    public static function theRecords($map)
    {
        return self::where($map)->count();
    }




}
