<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Log;
use app\common\controller\Frontend;
use app\common\library\Token;
use app\common\model\PayOrder;
use app\admin\model\Admin;
use fast\Http;
use app\admin\library\Service;
use addons\epay\controller\Api as adApi;
use Exception;

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



    /**
     * 交易所回调
     */
    public function notifyx()
    {
        $params = $this->request->request();

        if(empty($params['amount']) || empty($params['orderNo'])){
            $this->error('参数有误');
        }

        $amount = $params['amount'];
        $orderNo = $params['orderNo'];

        try {
            //$logM->addLog('ok','api_v3/notifyx');
            $OrderM = new PayOrder();

            //修改订单状态
            $myOrder = array();
            $myOrder['status'] = 2;//已经支付
            $myOrder['paydate'] = date('Y-m-d H:i:s',time());
            $myOrder['realprice'] = $amount;
            $myOrder['paytime'] = time();
            $where = array();
            $where['out_order_id'] = $orderNo;
            $where['status'] = array('in','0,1');
            $OrderM->where($where)->update($myOrder);

            //订单详情
            $where = array();
            $where['out_order_id'] = $orderNo;
            $orderInfo = $OrderM->where($where)->find();

            //下发商户通知
            $result = \app\admin\library\Service::notify($orderInfo['id']);

            $APiC = new adApi();
            //扣除费率及记账
            $APiC->dealServiceCharge($orderInfo);



            //你可以在此编写订单逻辑
            exit(json_encode(['code'=>0,'msg'=>'success']));
        } catch (Exception $e) {

        }
    }
}
