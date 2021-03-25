<?php

namespace app\admin\controller\pay;

use app\admin\library\Service;
use app\admin\model\PayBill;
use app\admin\model\PayCashed;
use app\admin\model\PayNotify;
use app\admin\model\PayOrder;
use app\common\controller\Backend;
use think\Db;
use think\Session;


/**
 * 订单管理
 *
 * @icon fa fa-first-order
 * @remark 未支付或过期的订单可以手动设为已收款，已支付的订单可以再次补发通知。
 */
class Order extends Backend
{

    /**
     * PayOrder模型对象
     * @var \app\admin\model\PayOrder
     */
    protected $model = null;
    protected $admin = null;
    protected $admin_id = null;

    protected $searchFields = 'id,out_order_id,extend,title,realprice';

    public function _initialize()
    {
        parent::_initialize();
        $this->dataLimit = 'personal';
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'admin_id';

        $all_money = 0;
        $cash_money = 0;
        $this->view->assign("all_money", $all_money);
        $this->view->assign("cash_money", $cash_money);

        $this->model = model('PayOrder');
        $admin = Session::get('admin');
        $this->admin = $admin;
        $this->admin_id =$admin['id'];
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("statusList2", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    /*public function index()
    {
        //查找过期状态,置为过期
        $expired = PayOrder::where('status', 'inprogress')->where('expiretime', '<', time())->find();
        if ($expired) {
            PayOrder::where('status', 'inprogress')->where('expiretime', '<', time())->update(['status' => 'expired']);
        }
        return parent::index();
    }*/

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

            $fi = $this->request->get("filter", []);
            $fi = json_decode($fi,true);
            if(isset($fi['status'])){
                $whereIndex = array();
            }else{
//                $whereIndex['status'] = array('gt',1);
                $whereIndex = array();
            }

            $total = $this->model
                ->where($where)
                ->where($whereIndex)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($whereIndex)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            //$list = collection($list)->toArray();
            
            $all_money = 0;
            $cash_money = 0;
            /*$all_list = $this->model
                ->field('realprice,status')
                ->where($where)
                ->order($sort, $order)
                ->select();*/

            /*foreach ($all_list as $key => $item) {
                if ($item['status'] >1) {
                    $all_money+=$item['realprice'];
                }
            }*/
            $all_money = $this->model
                ->field('realprice,status')
                ->where($where)
                ->where('status','gt',1)
                ->order($sort, $order)
                ->sum('realprice');

            $all_money = number_format($all_money, 2);
            $result = array("total" => $total, "rows" => $list,"all_money"=>$all_money,'cash_money'=>$cash_money);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 重发通知
     * @param null $ids
     */
    public function notify($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $result = Service::notify($row);

            if ($result) {
                $this->success('重发通知成功');
            } else {
                $this->error('重发通知失败');
            }
        }
        return;
    }

    /**
     * 回调信息
     * @param null $ids
     */
    public function notifyinfo($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $result = PayNotify::where('order_id', $row['id'])->order('id', 'desc')->find();
            if ($result) {
                $notify = $result->toArray();
                $notify['createtime_text'] = datetime($notify['createtime']);
                $notify['updatetime_text'] = datetime($notify['updatetime']);
                $this->success('', null, ['notify' => $notify]);
            } else {
                $this->error('');
            }
        }
        return;
    }

    /**
     * 重发通知
     * @param null $ids
     */
    public function paid($ids = null)
    {
        
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
       
        if ($this->request->isPost()) {
            $result = Service::handleOrder($row['id']);
            if ($result) {
                $this->success('收款成功');
            } else {
                $this->error('收款失败');
            }
            return;
        }
        
    }

    /**
     * 冻结订单
     * @param null $ids
     */
    public function freezed($ids = null)
    {

        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $type = $this->request->param('type','');
        $amount = floatval($this->request->param('amount',''));


        if ($type!=0 && $type!=1)
            $this->error('参数错误');

        if ($type==1)
        {
            if (!($row['status']>1))
                $this->error('订单未支付，无法冻结');
            if (!($amount>=1))
                $this->error('冻结金额倍数最小为1');
            $row['freezed_amount'] = intval($row['realprice'] * $amount);
        }else{
            $row['freezed_amount'] = null;
        }

        $row['freezed'] = $type;


        if ($row->save())
            $this->success('操作成功');
        else
            $this->error('操作失败');
    }


    /**
     * 订单统计
     */
    public function statistics()
    {
        $BillM = new PayBill();
        $moneyInfo = $BillM->getMoneyInfo($this->auth->id);
        $this->view->assign("moneyInfo", $moneyInfo);

        $orderdata = $this->getOrderData(7);
        $this->view->assign("orderdata", $orderdata);
        $this->view->assign("nowData", $orderdata['nowData']);
        return $this->view->fetch();
    }

    /**
     * @param string $date
     * @return array
     */
    function getOrderData($days = 7)
    {
        $dataArr = array();
        $dataArr['orderData'] = array();
        $dataArr['amountData'] = array();
        $dataArr['dateTime'] = array();
        $ids = $this->auth->getChildrenAdminIds(true);
        if (!$ids)
            $ids = '-1';
        for($i = $days-1;$i>=0;$i--)
        {
            $dateTime = date("Y-m-d",strtotime("-$i day"));
            $ii = $i-1;
            $dateTimeArr = [date("Y-m-d",strtotime("-$i day")),date("Y-m-d",strtotime("-$ii day"))];
            $orderData = $this->model->whereTime('paydate', 'between',$dateTimeArr)
                        ->where('admin_id','in',$ids)
                        ->where('status','gt',1)
                        ->count();
            $amountData = $this->model->whereTime('paydate', 'between',$dateTimeArr)
                        ->where('admin_id','in',$ids)
                        ->where('status','gt',1)
                        ->sum('realprice');
            array_push($dataArr['dateTime'],$dateTime);
            array_push($dataArr['orderData'],$orderData);
            array_push($dataArr['amountData'],$amountData);
        }
        //今天
        $dataArr['nowData'] = array();
        $dataArr['nowData']['orderNumber'] = $orderData;
        $dataArr['nowData']['orderAmount'] = $amountData;
        //本周
        $orderData = $this->model->whereTime('paydate', 'week')
            ->where('admin_id','in',$ids)
            ->where('status','gt',1)
            ->count();
        $amountData = $this->model->whereTime('paydate', 'week')
            ->where('admin_id','in',$ids)
            ->where('status','gt',1)
            ->sum('realprice');
        $dataArr['weekData'] = array();
        $dataArr['weekData']['orderNumber'] = $orderData;
        $dataArr['weekData']['orderAmount'] = $amountData;
        //本月
        $orderData = $this->model->whereTime('paydate', 'month')
            ->where('admin_id','in',$ids)
            ->where('status','gt',1)
            ->count();
        $amountData = $this->model->whereTime('paydate', 'month')
            ->where('admin_id','in',$ids)
            ->where('status','gt',1)
            ->sum('realprice');
        $dataArr['monthData'] = array();
        $dataArr['monthData']['orderNumber'] = $orderData;
        $dataArr['monthData']['orderAmount'] = $amountData;

        return $dataArr;
    }

    protected function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'admin_id';
        return parent::selectpage();
    }

}
