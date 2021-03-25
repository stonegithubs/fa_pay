<?php

namespace app\api\controller;

use app\admin\model\pay\PayRate;
use app\admin\model\pay\Type;
use app\common\controller\Api;
use app\common\model\Log;
use app\common\controller\Frontend;
use app\admin\model\Admin;
use app\common\model\PayOrder;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use tool\Request as ToolReq;
use think\Config;
use think\Exception;
/**
 * 商户接口
 */
class Trade extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function get_paytype()
    {
        $appid = $this->request->param('appid','');
        $sign  = $this->request->param( 'sign','');
        if (!empty($appid) && !empty($sign))
        {
            $AdminM = new Admin();
            $admininfo = $AdminM->field('appid,appsecret')->where('appid',$appid)->find();
            if ($admininfo)
            {
                $appsecret = $admininfo['appsecret'];
                if ($sign==md5($appid.$appsecret))
                {
                    $PayTypeM = new Type();
                    $type_list = $PayTypeM->field('type,name')->where('status',1)->select();
                    if(!$type_list)
                        $this->error(405);
                    $this->success($type_list);
                }else{
                    $this->error(4001);
                }
            }else{
                $this->error(4002);
            }
        }else{
            $this->error(4003);
        }
    }


}
