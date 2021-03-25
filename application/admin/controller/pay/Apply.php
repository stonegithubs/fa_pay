<?php

namespace app\admin\controller\pay;

use app\admin\library\Service;
use app\admin\model\Admin;
use app\admin\model\PayBill;
use app\admin\model\PayCashed;
use app\admin\model\PayNotify;
use app\admin\model\PayOrder;
use app\common\controller\Backend;
use think\Db;
use think\Session;
use think\Config;
use think\Exception;


/**
 * 订单管理
 *
 * @icon fa fa-first-order
 * @remark 未支付或过期的订单可以手动设为已收款，已支付的订单可以再次补发通知。
 */



class Apply extends Backend
{

    /**
     * PayOrder模型对象
     * @var \app\admin\model\PayOrder
     */
    protected $model = null;
    protected $admin = null;
    protected $admin_id = null;
    protected $paymentConfig = null;

    protected $searchFields = 'id,out_order_id,extend,title,realprice';

    public function _initialize()
    {
        parent::_initialize();
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'admin_id';
        $this->paymentConfig = Config::get("payment");
        $this->model = model('PayCashed');
        $admin = Admin::get($this->auth->id);
        $this->admin = $admin;
        $this->admin_id =$admin['id'];
        $this->view->assign("statusList", [0 => '待审核', 1 => '已结算', -1 => '驳回']);

    }


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
                ->where($where)
                ->with('withAdminInfo')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach ($list as $key=> $item)
            {
                if ($item['status'] ===0)
                {
                    $list[$key]['updatetime'] = '';
                }
              //if(empty($item['appid']))
              //{
               // $save['appid'] = $item['with_admin_info']['appid'];;
                //$this->model->where('id',$item['id'])->update($save);
             // }
              
                $list[$key]['appid'] = $item['with_admin_info']['appid'];
                unset($list[$key]['with_admin_info']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($row['status']!==0)
            $this->error('已审核的申请无法修改');
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $row->startTrans();
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        if ($row['status'] ==1)
                        {
                            $BillM = new PayBill();
                            $real_amount = $row['real_amount'] * (-1);
                            $amount = $row['amount'] * (-1);
                            $res1 = $BillM->addBill($row['admin_id'],$real_amount,3,'提现记录ID:'.$row['id']);
                            $res2 = $BillM->addBill($row['admin_id'],$amount-$real_amount,4,'提现记录ID:'.$row['id']);

                            if (!$res1 ||  !$res2)
                            {
                                $row->rollback();
                                $this->error('系统异常'.$res1.$res2);
                            }
                        }
                        $row->commit();
                        $this->success();
                    } else {
                        $row->rollback();
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();

    }

    protected function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'admin_id';
        return parent::selectpage();
    }
  
  
   public function object_to_array($obj)
  {
   $obj = (array)$obj;
   foreach ($obj as $k => $v)
   {
    if (gettype($v) == 'resource')
    {
     return;
    }
    if (gettype($v) == 'object' || gettype($v) == 'array')
    {
     $obj[$k] = (array)object_to_array($v);
    }
   }

   return $obj;
  }

    /*protected function dealApply($row)
    {
        $BillM = new PayBill();
        $BillM->startTrans();
        try {
            $BillM->addBill($row['admin_id'],'0','0',$row['real_amount']*(-1),3);
            $BillM->addBill($row['admin_id'],'0','0',$row['real_amount'] - $row['amount'],4);

        } catch (Exception $e) {
            $BillM->rollback();
            return false;
        }
        $BillM->commit();
        return true;
    }*/

}
