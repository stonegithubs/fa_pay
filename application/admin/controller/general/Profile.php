<?php

namespace app\admin\controller\general;

use app\admin\model\Admin;
use app\common\controller\Backend;
use fast\Random;
use think\Session;

/**
 * 个人配置
 *
 * @icon fa fa-user
 */
class Profile extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            $model = model('AdminLog');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $model
                    ->where($where)
                    ->where('admin_id', $this->auth->id)
                    ->order($sort, $order)
                    ->count();

            $list = $model
                    ->where($where)
                    ->where('admin_id', $this->auth->id)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 更新个人信息
     */
    public function update()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            $params = array_filter(array_intersect_key($params, array_flip(array('email', 'nickname', 'password', 'avatar','appsecret','cash_pwd','new_cash_pwd'))));
            unset($v);
            if (isset($params['password']))
            {
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
            }

            if (isset($params['cash_pwd']) || isset($params['new_cash_pwd']))
            {
                if (!isset($params['cash_pwd']) || empty($params['cash_pwd']))
                    $this->error('请输入原提现密码');
                if (!isset($params['new_cash_pwd']) || empty($params['new_cash_pwd']))
                    $this->error('请输入新提现密码');

                $cash_pwd = $params['cash_pwd'];
                $new_cash_pwd = $params['new_cash_pwd'];
                unset($params['new_cash_pwd']);
                $adminInfo = Admin::get($this->auth->id);
                if (md5($cash_pwd.'kelly') != $adminInfo['cash_pwd'])
                    $this->error('原提现密码错误，若忘记密码请联系管理员');
                $params['cash_pwd'] = md5($new_cash_pwd.'kelly');

            }

            if ($params)
            {
                $admin = Admin::get($this->auth->id);
                $admin->save($params);
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
                Session::set("admin", $admin->toArray());
                $this->success();
            }
            $this->error();
        }
        return;
    }

}
