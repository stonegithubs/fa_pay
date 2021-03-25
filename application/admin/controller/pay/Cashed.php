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


/**
 * 订单管理
 *
 * @icon fa fa-first-order
 * @remark 未支付或过期的订单可以手动设为已收款，已支付的订单可以再次补发通知。
 */
class Cashed extends Backend
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
        $this->dataLimit = 'personal';
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
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    public function add()
    {

        //个人资金信息
        $BillM = new PayBill();
        $moneyInfo = $BillM->getMoneyInfo($this->auth->id);

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {

                    //验证
                    if ($moneyInfo['now_amount'] < $params['amount'])
                        $this->error('可提现金额不足');
                    if ($this->paymentConfig['min_cash'] > $params['amount'])
                        $this->error('最低提现金额为'.$this->paymentConfig['min_cash']);
                    $adminInfo = Admin::get($this->auth->id);
                    if (!isset($params['cash_pwd']) || $adminInfo['cash_pwd']!=md5($params['cash_pwd'].'kelly'))
                        $this->error('提现密码错误，若忘记密码请联系管理员');
                    //计算提现实际到账金额
                    $params['real_amount'] = $this->dealCost($params['amount']);
                    $params['appid'] = $adminInfo['appid'];
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign("moneyInfo",  $moneyInfo);
        $this->assign("cost_amount", $this->paymentConfig['cash_cost']);
        $this->assign("min_cashed_amount", $this->paymentConfig['min_cash']);
        $BankM = new \app\admin\model\pay\Bank();
        $bankList = $BankM->where(array('status'=>1,'admin_id'=>$this->auth->id))->select();
        if (!$bankList)
            $bankList = array();
        $this->assign("bankList",  $bankList);
        return $this->view->fetch();
    }

    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            $this->model->startTrans();
            foreach ($list as $k => $v) {
                if ($v['status']!==0)
                {
                    $this->model->rollback();
                    $this->error('只能删除未审核的申请');
                    break;
                }
                $count += $v->delete();
            }
            $this->model->commit();
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    //计算提现实际到账金额
    protected function dealCost($amount)
    {
        return $amount-$this->paymentConfig['cash_cost'];
    }

    protected function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'admin_id';
        return parent::selectpage();
    }

}
