<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Log;
/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     * 
     */
    public function index()
    {
        $data = $this->request->request();
        $LogM = new Log();
        $json = json_encode($data);
        $LogM->addLog($json,'api/index/index');
        echo $json;

    }

}
