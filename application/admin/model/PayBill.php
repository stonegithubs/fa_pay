<?php

namespace app\admin\model;

use app\common\model\Log;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Model;

class PayBill extends Model
{
    // 表名
    protected $name = 'pay_bill';


    /**
     * @param $admin_id
     * @param $money
     * @param $type
     * @param $marks
     * @return bool
     */
    public  function addBill($admin_id,$money,$type,$marks)
    {
        return true;
        $this->startTrans();
        try {
            $data = array();
            $data['admin_id'] = $admin_id;
            $data['marks'] = $marks;
            $data['type'] = $type;//1订单支付，2支付手续费，3提现，4提现费用
            $isReap = $this->where($data)->count();
            if ($isReap)
                return false;
            $data['money'] = $money;
            $lastBill = $this->where('admin_id', $admin_id)
                ->order('id desc')->lock(true)->find();
            $times=60;
            while (!$lastBill && $times>0) {
                sleep(1);
                $lastBill = $this->where('admin_id', $admin_id)
                    ->order('id desc')->lock(true)->find();
                $times--;
            }

            if ($lastBill) {
                $before = $lastBill['after'];
            } else {
                $before = 0;
            }
            $after = $before + $money;
            $data['before'] = $before;
            $data['after'] = $after;
            $data['create_time'] = time();
            $res = $this->insert($data);
            if ($res)
            {
                $this->commit();
                return true;
            }else{
                $this->rollback();
                return false;
            }
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        } catch (Exception $e) {
        }

        $this->rollback();
        return false;
    }

    /**
     * 获取用户当前资金情况
     * @param $admin_id
     * @return array
     */
    public function getMoneyInfo($admin_id)
    {
        $moneyInfo = array();
        $moneyInfo['all_amount'] = 0;//所有资金
        $moneyInfo['now_amount'] = 0;//可提现资金
        $moneyInfo['cashing'] = 0;//提现中的资金
        $moneyInfo['freezed_amount'] = 0;//提现中的资金
        try {
            /*$BillM = new PayBill();
            $CashM = new PayCashed();
            $lastBill = $BillM->where('admin_id',$admin_id)->order('id desc')->find();
            if ($lastBill) {
                $moneyInfo['all_amount'] = $lastBill['after'];
            }
            $cashingArr = $CashM->field('amount')->where('admin_id', $admin_id)
                ->where('status', 0)->select();
            if ($cashingArr) {
                foreach ($cashingArr as $item) {
                    $moneyInfo['cashing'] += $item['amount'];
                }
            }
            $moneyInfo['now_amount'] = $moneyInfo['all_amount'] - $moneyInfo['cashing'];*/


            $whereOrder = array();
            $whereOrder['status'] = array('gt',1);
            $whereOrder['admin_id'] = $admin_id;
            $OrderM = new PayOrder();
            $moneyInfo['all_amount'] = $OrderM->where($whereOrder)->sum('realprice');
            $order_cost = $OrderM->where($whereOrder)->sum('cost');
            $moneyInfo['all_amount'] = $moneyInfo['all_amount'] - $order_cost;//扣手续费

            $CashM = new PayCashed();
            $moneyInfo['cashing'] = $CashM->where('admin_id',$admin_id)
                ->where('status',0)->sum('amount');
            $cash_ready = $CashM->where('admin_id',$admin_id)
                ->where('status',1)->sum('amount');
            $moneyInfo['all_amount'] = $moneyInfo['all_amount'] - $cash_ready;//扣除已经提现

            $moneyInfo['now_amount'] = $moneyInfo['all_amount'] - $moneyInfo['cashing'];//扣除正在提现
          
            $moneyInfo['cashing'] = intval($moneyInfo['cashing']);
            $moneyInfo['all_amount'] = intval($moneyInfo['all_amount']);
            $moneyInfo['now_amount'] = intval($moneyInfo['now_amount']);

            //冻结的金额
            $whereOrder = array();
            $whereOrder['status'] = array('gt',1);
            $whereOrder['admin_id'] = $admin_id;
            $whereOrder['freezed'] = 1;
            $moneyInfo['freezed_amount'] = $OrderM->where($whereOrder)->sum('freezed_amount');
            $moneyInfo['freezed_amount'] = intval($moneyInfo['freezed_amount']);
            $moneyInfo['now_amount'] = $moneyInfo['now_amount'] - $moneyInfo['freezed_amount'];//扣除冻结金额


        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        return $moneyInfo;
    }

    public function adminId()
    {
        return $this->belongsTo('admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
