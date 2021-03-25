<?php

namespace app\admin\controller\pay;

use app\admin\model\pay\PayRate;
use app\common\controller\Backend;
use app\common\model\PayConfig as ConfigModel;
use think\Exception;
use app\admin\model\Admin;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Type extends Backend
{
    
    /**
     * Type模型对象
     * @var \app\admin\model\pay\Type
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\pay\Type;

    }


    public function add()
    {
        $arr = array (
            'backend' => 'zh-cn',
            'frontend' => 'zh-cn',
        );
        $this->assign('arr',\GuzzleHttp\json_encode($arr));
        return parent::add();
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function pay_rate()
    {
        $admin_id = intval($this->request->param('ids'));
        if (!($admin_id>0))
            $this->error('参数错误');
        $siteList = [];
        $groupList =
            array (
                'pay_rate' => '费率配置',
            );

        foreach ($groupList as $k => $v) {
            $siteList[$k]['name'] = $k;
            $siteList[$k]['title'] = $v;
            $siteList[$k]['list'] = [];
        }

        $PayRateM = new PayRate();
        $pay_rate_list = $PayRateM->where('admin_id',$admin_id)->select();
        foreach ($pay_rate_list as $k => $v) {
            if (!isset($siteList[$v['group']])) {
                continue;
            }
            $value = $v->toArray();
            if (in_array($value['type'], ['select', 'selects', 'checkbox', 'radio'])) {
                $value['rate'] = explode(',', $value['rate']);
            }
            $siteList[$v['group']]['list'][] = $value;
        }
        $index = 0;
        foreach ($siteList as $k => &$v) {
            $v['active'] = !$index ? true : false;
            $index++;
        }
        $this->view->assign('siteList', $siteList);
        $this->view->assign('typeList', $this->model->getPaytypeList());
        $this->view->assign('groupList', $groupList);
        $this->view->assign('admin_id', $admin_id);
//        var_dump($this->model->getPaytypeList());
        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add_pay_rate()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    $params['admin_id']=intval($params['admin_id']);
                    if (!isset($params['admin_id']) || !($params['admin_id']>0))
                        $this->error('参数错误');

                    $PayRateM = new PayRate();
                    if (isset($params['edit']))
                    {

                        $admin_id = $params['admin_id'];
                        unset($params['admin_id']);
                        unset($params['edit']);
                        foreach ($params as $key=> $item)
                        {
                            $update = array();
                            $update['rate'] = doubleval($item);
                            $PayRateM->where('id',$key)->update($update);
                        }
                        $this->success();
                    }

                    $params['rate']=doubleval($params['rate']);
                    $content =  $params['content'];
                    unset($params['content']);
                    $content=json_decode($content,true);
                    $params['pay_type'] = $content['type'];
                    $params['pay_type_id'] = $content['id'];
                    $params['pay_type_name'] = $content['name'];
                    $result = $PayRateM->create($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }


    public function pay_rate_del()
    {
        $ids = $this->request->param("ids");
        if ($ids) {
            $PayRateM = new PayRate();
            $pk = $PayRateM->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $PayRateM->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $PayRateM->where($pk, 'in', $ids)->select();
            $count = 0;
            foreach ($list as $k => $v) {
                $count += $v->delete();
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
