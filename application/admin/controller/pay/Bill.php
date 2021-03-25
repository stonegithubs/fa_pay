<?php

namespace app\admin\controller\pay;

use app\admin\model\pay\PayRate;
use app\admin\model\PayBill;
use app\common\controller\Backend;
use app\common\model\PayConfig as ConfigModel;
use think\Exception;
use app\admin\model\Admin;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Bill extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new PayBill();
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'admin_id';

    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('adminId')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key => &$item)
            {
                $item['nickname'] = $item['admin_id']['nickname'];
                $item['appid'] = $item['admin_id']['appid'];
                unset($item['admin_id']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function add()
    {
        $this->error();
    }
    public function del($ids = NULL)
    {
        $this->error();
    }
    public function edit($ids = NULL)
    {
        $this->error();
    }


}
