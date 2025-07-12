<?php

namespace app\admin\controller;

use app\admin\model\order\Pay;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Order;
use app\common\model\OrderPayDetail as OrderPayDetailModel;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\controller\order\Order as OrderController;

/**
 * 地区管理
 *
 * @icon fa fa-circle-o
 */
class Offline extends Backend
{

    protected $model = null;
    protected $searchFields = 'id,order.sn';

    protected $noNeedRight = ['status'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderPayOffline;
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->relationSearch = true;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $order_id = $this->request->param("order_id");
            if($order_id){
                $this->model->where('order.id',$order_id);
            }
            $list = $this->model
                ->with([
                    'order',
                    'orderPay'
                ])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $user = new \app\admin\model\User;
            foreach ($list as $k => $v) {

                $v['idx'] = '节点'.$v['idx'];
                $v['user'] = $user->where('id',$v->user_id)->find();
                //$v->user->visible(['nickname']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    public function detail($ids = null){

        if ($this->request->isPost()) {
            $order = new Order();
            $row = $this->request->post('row/a');
            if(!isset($row['status'])){
                return [
                    'code' => 0,
                    'msg' => "请选择付款状态"
                ];
            }
            if($row['status'] == 1 && !$row['vertify_file']){
                return [
                    'code' => 0,
                    'msg' => "请上传转账核对凭证"
                ];
            }

            if($row['is_excp'] == '1' and $row['status'] == '1'){
                return [
                    'code' => 0,
                    'msg' => "金额确认无误，才能审核通过"
                ];
            }

            $order_id = $this->model->where('id',$ids)->value('order_id');

            if(!$order_id){
                return [
                    'code' => 0,
                    'msg' => "订单不存在"
                ];
            }
            $pass = new OrderController();
            if($row['status'] == 1){
                $_POST['info'] = $row['vertify_file'];
                $pass->pass($order_id);
            }else{
                if(!$row['reason']){
                    return [
                        'code' => 0,
                        'msg' => "请填写不通过原因"
                    ];
                }
                $_POST['vertify_refuse_reason'] = $row['reason'];
                $pass->nopass($order_id);
            }


            /**
            $pay = Pay::where('id',$row['order_pay_id'])->find();
            $pay->is_excp = $row['is_excp'];
            $pay->is_stop = $row['status'];



            $offline = $this->model->where('id',$ids)->find();
            $orderSave = $order->where('id',$offline->order_id)->find();

            if($row['status'] == 2){
                $orderPayDetailModel = new OrderPayDetailModel();
                $status_1 = "对公审核不通过";//平台
                $status_2 = "对公审核不通过";//用户
                $status_3 = "对公审核不通过";//专家
                $tip_1 = "对公审核不通过，重新支付";
                $tip_2 = "对公审核不通过，重新支付";
                $tip_3 = "对公审核不通过，重新支付";
                $orderPayDetailModel->order_id = $orderSave->id;
                $orderPayDetailModel->idx = $orderSave->now_point;
                $orderPayDetailModel->type = $pay->is_pay;
                $orderPayDetailModel->tip_1 = $tip_1;
                $orderPayDetailModel->tip_2 = $tip_2;
                $orderPayDetailModel->tip_3 = $tip_3;
                $orderPayDetailModel->status_1 = $status_1;
                $orderPayDetailModel->status_2 = $status_2;
                $orderPayDetailModel->status_3 = $status_3;
                $orderPayDetailModel->save();
            }

            if($row['status'] == '2'){
                if(!$row['reason']){
                    return [
                        'code' => 0,
                        'msg' => "请填写不通过原因"
                    ];
                }
                $offline->status = 2;
                $offline->vertify_refuse_reason = $row['reason'];
                $orderSave->status = '7';
                $pay->is_pay = '7';

            }
            if($row['status'] == '1'){
                $offline->status = 1;
                $orderSave->status = 2;

                //插入下一个大节点-服务中

                $orderPayDetailModel = new OrderPayDetailModel();
                // $orderPayDetailModel = new OrderPayDetailModel();
                $status_1 = "服务中";//平台
                $status_2 = "服务中";//用户
                $status_3 = "服务中";//专家
                $tip_1 = "等待专家发起完成服务";
                $tip_2 = "请等待专家完成服务说明";
                $tip_3 = "专家可提交验收申请至需方";
                $orderPayDetailModel->order_id = $orderSave->id;
                $orderPayDetailModel->idx = $orderSave->now_point;
                $orderPayDetailModel->type = $pay->is_pay;
                $orderPayDetailModel->tip_1 = $tip_1;
                $orderPayDetailModel->tip_2 = $tip_2;
                $orderPayDetailModel->tip_3 = $tip_3;
                $orderPayDetailModel->status_1 = $status_1;
                $orderPayDetailModel->status_2 = $status_2;
                $orderPayDetailModel->status_3 = $status_3;
                $orderPayDetailModel->save();
            }

            Db::startTrans();
            try {
                $offline->save();
                $orderSave->save();
                $pay->save();
                Db::commit();
            } catch (ValidateException|PDOException|Exception $e) {
                Db::rollback();
                // $this->error($e->getMessage());
                return [
                    'code' => 0,
                    'msg' => $e->getMessage()
                ];
            }
            return [
                'code' => 1,
                'msg' => '操作成功'
            ];
             * **/

        }
        $row = $this->model->with(['order','orderPay'])->where('order_pay_offline.id',$ids)->find();
        $row['idx'] = '节点'.$row['idx'];
        $user = $row->user->visible(['nickname']);
        $row['nickname'] = $user['nickname'];
        $row['name'] = $user['typedata'] == '1' ? $user['id_no_name'] : $user['company_name'];
        $row['bank_name'] = $user['typedata'] == '1' ? $user['id_no_bank_name'] : $user['company_bank_name'];
        $row['bank_id'] = $user['typedata'] == '1' ? $user['id_no_bank_id'] : $user['company_bank_id'];


        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }

    public function status($ids = null){

        if ($this->request->isPost()) {
            $order = new Order();
            $row = $this->request->post('row/a');
            $vertify_refuse_reason = $this->request->post('vertify_refuse_reason');
            $offline = $this->model->where('id',$ids)->find();
            $orderSave = $order->where('id',$offline->order_id)->find();

            if(!empty($vertify_refuse_reason)){
                setPostMessage(1,$orderSave->user_id,'您提交的线下转账付款未通过，点击查看详情','/myOrder/detail?status='.$orderSave->status.'&id='.$orderSave->id);
                $offline->vertify_refuse_reason = $vertify_refuse_reason;
                $offline->status = 2;
            }
            if(isset($row['vertify_file'])){
                setPostMessage(1,$orderSave->user_id,'您提交的线下转账付款已通过，点击查看详情','/myOrder/detail?status='.$orderSave->status.'&id='.$orderSave->id);
                $offline->vertify_file = $row['vertify_file'];
                $offline->status = 1;
                $orderSave->status = 2;
            }
            Db::startTrans();
            try {
                $offline->save();
                if(isset($row['vertify_file'])){
                    $orderSave->save();
                }
                Db::commit();
            } catch (ValidateException|PDOException|Exception $e) {
                Db::rollback();
                // $this->error($e->getMessage());
                return [
                    'code' => 0,
                    'msg' => $e->getMessage()
                ];
            }
            return [
                'code' => 1,
                'msg' => '操作成功'
            ];

        }

        $row = $this->model->with(['order','orderPay'])->where('order_pay_offline.id',$ids)->find();
        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }


}
