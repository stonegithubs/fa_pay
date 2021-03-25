<?php

namespace app\common\model;

use think\Model;

/**
 * 配置模型
 */
class Log extends Model
{

    // 表名,不含前缀
    protected $name = 'log';


    public function addLog($val='',$t_type='')
    {
        $data=array();
        $data['t_val'] = $val;
        $data['t_type'] = $t_type;
        $data['t_time'] = time();
        $data['t_date'] = date('Y-m-d H:i:m');
        $this->insert($data);
    }

}
