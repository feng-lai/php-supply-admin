<?php

namespace app\admin\controller\order;

use app\admin\model\OrderPayOffline;
use app\common\controller\Backend;
use app\admin\model\order\Pay as OrderPayModel;
use app\common\model\OrderPayLog as OrderPayLogModel;
use app\common\model\OrderPayDetail as OrderPayDetailModel;
use app\common\model\OrderPayOffline as OrderPayOfflineModel;
use app\common\model\OrderExcp as OrderExcpModel;
use app\admin\model\order\Order as OrderModel;
use think\Db;
use app\admin\model\User;


/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\order\Order
     */
    protected $model = null;
    protected $noNeedRight = ['pass','nopass','indexspec','excp_pass','excp_nopass','indexs'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\Order;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("needAcceptanceList", $this->model->getNeedAcceptanceList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 详情
     */
    // public function edit($ids = null)
    // {
    //     $row = $this->model->with(['user','specialist','requirement'])->where('order.id',$ids)->find();
    //     // dump($row);die;
    //     if (!$row) {
    //         $this->error(__('No Results were found'));
    //     }
    //     if ($this->request->isAjax()) {
    //         $this->success("Ajax请求成功", null, ['id' => $ids]);
    //     }
        
    //     $this->view->assign("row", $row->toArray());
    //     return $this->view->fetch();
    // }

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

            $param = $this->request->param();
            $filter = isset($param['filter'])?json_decode($param['filter'],true):[];
            $op = isset($param['op'])?json_decode($param['op'],true):[];
            $createtime = '';
            if(isset($filter['createtime'])){
                $createtime = $filter['createtime'];
                unset($filter['createtime']);
                unset($op['createtime']);
                $this->request->get(["filter"=>json_encode($filter),'op'=>json_encode($op)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams(null,true);

            $model = $this->model;

            $list = $model
                ->with([
                    'user',
                    'specialist'
                ])
                ->where($where)
                ->order($sort, $order);
            if(isset($filter['is_excp']) && count($filter)){
                $list = $list->where('is_stop',1);
            }
            if(!isset($filter['is_excp']) && !isset($filter['is_stop']) && count($filter)){
                $list = $list->where('is_excp',0);
            }
            if($createtime){
                $timeArr = explode(' - ', $createtime);
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $list = $list->whereTime('order.createtime','between',[$begin,$end]);
                }
            }
            //print_r($list->fetchSql(true)->select());exit;
            $list = $list->paginate($limit);
            $offline = new OrderPayOffline();
            foreach ($list as $k => $v) {
                $v->offline_id = $offline->where(['order_id' => $v->id,'status'=>0])->value("id");
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            // $this->token();
        }
        // $row = $this->model->get($ids);
        $row = $this->model->with(['user','specialist','requirement','rs'])->where('order.id',$ids)->find();
        $row['specialist_id'] = Db::name("specialist")->where(['user_id' => $row['specialist']['id']])->value("id");
        // $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //付款期次
        $pays = OrderPayModel::where('order_id',$ids)->order('idx','asc')->select();
        foreach ($pays as $k => &$v) {
            //
            $detail = OrderPayDetailModel::where('order_id',$ids)->where('idx',$v->idx)->select();
            foreach ($detail as $kk => &$vv) {
                $vv['log'] = OrderPayLogModel::where('order_pay_detail_id',$vv->id)->select();
            }
            $v['detail'] = $detail;
        }
        $pay_offline = OrderPayOfflineModel::where('order_id',$ids)->find();
        $excp = OrderExcpModel::whereIn('order_id',$ids)->order('id','desc')->select();
        foreach ($excp as $k => &$v) {
            //
            $user = null;
            if($v->cate == '1'){
                //需求方
                $v['user'] = User::alias('u')->join('order o','o.user_id = u.id')->where('o.id',$v->order_id)->field('u.*')->find();
            }else{
                //专家
                $v['user'] = User::alias('u')->join('order o','o.specialist_id = u.id')->where('o.id',$v->order_id)->field('u.*')->find();
            }
            
        }
        // dump($excp);die;
        $this->view->assign("row", $row->toArray());
        $this->view->assign("pays", $pays);
        $this->view->assign("pay_offline", $pay_offline);
        $this->view->assign("excp", $excp);
        $tab = $this->request->param("tab",1);
        $this->assignconfig("tab", $tab);
        // dump($row->toArray());die;
        return $this->view->fetch();
        // return parent::edit($ids);
    }

    /**
     * 付款审核-通过
     */
    public function pass($ids = null)
    {
        $info = $this->request->post('info',$_POST['info']);
        if($info == ''){
            $this->error("转账核对凭证不能为空");
        }

        $pay_offline = OrderPayOfflineModel::where('order_id',$ids)->where('status',0)->find();
        if (!$pay_offline) {
            $this->error("找不到付款信息");
        }
        $order = OrderModel::where('id',$pay_offline->order_id)->find();
        $orderPay = OrderPayModel::where('id',$pay_offline->order_pay_id)->where('idx',$order->now_point)->find();
        
        if ($this->request->isPost()) {
            //#审核状态
            $pay_offline->vertify_file = $info;
            $pay_offline->status = 1;
            //$pay_log
            $orderPay->is_pay = '2';
            //order
            $order->status = '2';



            // //插入节点状态
            //获取当前大节点
            $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
            if(!$points_detail){
                $this->error("找不到节点详细信息");
            }
            //节点明细小节点
            $orderPayLogModel = new OrderPayLogModel();
            $orderPayLogModel->order_id = $order->id;
            $orderPayLogModel->order_pay_id = $orderPay->id;
            $orderPayLogModel->tpye = $orderPay->is_pay;
            $orderPayLogModel->order_pay_detail_id = $points_detail->id;
            $orderPayLogModel->idx = $order->now_point;
            $orderPayLogModel->tip_1 = date('Y-m-d H:i')."管理员{$this->auth->nickname}审核通过";
            $orderPayLogModel->tip_2 = date('Y-m-d H:i')."管理员审核通过";
            $orderPayLogModel->tip_3 = date('Y-m-d H:i')."管理员审核通过";

            //插入下一个大节点-服务中
            $orderPayDetailModel = new OrderPayDetailModel();
            // $orderPayDetailModel = new OrderPayDetailModel();
            $status_1 = "服务中";//平台
            $status_2 = "服务中";//用户
            $status_3 = "服务中";//专家
            $tip_1 = "等待专家服务反馈及相关文件";
            $tip_2 = "请等待专家完成服务说明";
            $tip_3 = "专家可提交验收申请至需方";
            $orderPayDetailModel->order_id = $order->id;
            $orderPayDetailModel->idx = $order->now_point;
            $orderPayDetailModel->type = $orderPay->is_pay;
            $orderPayDetailModel->tip_1 = $tip_1;
            $orderPayDetailModel->tip_2 = $tip_2;
            $orderPayDetailModel->tip_3 = $tip_3;
            $orderPayDetailModel->status_1 = $status_1;
            $orderPayDetailModel->status_2 = $status_2;
            $orderPayDetailModel->status_3 = $status_3;


            Db::startTrans();
            try {
                $pay_offline->save();
                $order->save();
                $orderPay->save();
                $orderPayLogModel->save();
                $orderPayDetailModel->save();
                setPostMessage(2,$order->specialist_id,'您有一条新待服务的订单，点击查看详情','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error("操作失败",$e->getMessage());
            }
            
            
            $this->success("操作成功", null, ['id' => $ids]);
        }
    }

    /**
     * 付款审核-不通过（未确认收款）
     */
    public function nopass($ids = null)
    {
        $vertify_refuse_reason = $this->request->post('vertify_refuse_reason',$_POST['vertify_refuse_reason']);
//        if($info == ''){
//            $this->error("转账核对凭证不能为空");
//        }


        $pay_offline = OrderPayOfflineModel::where('order_id',$ids)->where('status',0)->find();
        if (!$pay_offline) {
            $this->error("找不到付款信息");
        }
        $orderPay = OrderPayModel::where('id',$pay_offline->order_pay_id)->find();
        $order = OrderModel::where('id',$pay_offline->order_id)->find();
        if ($this->request->isPost()) {
            //#审核状态
            $pay_offline->vertify_refuse_reason = $vertify_refuse_reason;
            $pay_offline->status = 2;
            //$pay_log
            $orderPay->is_pay = '7';
            //order
            $order->status = '7';
            //节点明细
             // //插入节点状态
            //获取当前大节点
            $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
            if(!$points_detail){
                $this->error("找不到节点详细信息");
            }
            //节点明细小节点
            $orderPayLogModel = new OrderPayLogModel();
            $orderPayLogModel->order_id = $order->id;
            $orderPayLogModel->order_pay_id = $orderPay->id;
            $orderPayLogModel->tpye = $orderPay->is_pay;
            $orderPayLogModel->order_pay_detail_id = $points_detail->id;
            $orderPayLogModel->idx = $order->now_point;
            $orderPayLogModel->tip_1 = "平台未确认收款单款项 未通过原因：".$vertify_refuse_reason;
            $orderPayLogModel->tip_2 = "平台未确认收款单款项 未通过原因：".$vertify_refuse_reason;
            $orderPayLogModel->tip_3 = date('Y-m-d H:i')."平台未确认收款单款项 未通过原因：".$vertify_refuse_reason;
            $orderPayLogModel->save();
            $pay_offline->save();
            $order->save();
            $orderPay->save();
            //通知
            setPostMessage(2,$order->user_id,'您提交的线下转账审核未通过，点击查看详情','/myOrder/detail?status='.$order->status.'&id='.$order->id);
            
            $this->success("操作成功", null, ['id' => $ids]);
        }
    }

    /**
     * 异常处理-正常进行
     */
    public function excp_pass($ids = null)
    {
        $info = $this->request->post('dealinfo','');
        if($info == ''){
            $this->error("处理情况不能为空");
        }
        $excp = OrderExcpModel::where('id',$ids)->find();
        if(!$excp){
            $this->error("找不到异常信息");
        }
        if($excp->status > 0 ){
            $this->error("异常信息已处理");
        }

        $excp->status = '1';
        $excp->deal_info = $info;

        $order = OrderModel::where('id',$excp->order_id)->find();
        $orderPay = OrderPayModel::where('id',$excp->order_pay_id)->find();
        $orderPay->is_excp = 0;
        $orderPay->is_stop = 1;
        // dump($orderPay);die;
        if ($this->request->isPost()) {
            //#审核状态
            // //插入节点状态
            //获取当前大节点
            $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
            if(!$points_detail){
                $this->error("找不到节点详细信息");
            }
            //节点明细小节点
            $orderPayLogModel = new OrderPayLogModel();
            $orderPayLogModel->order_id = $order->id;
            $orderPayLogModel->order_pay_id = $orderPay->id;
            $orderPayLogModel->tpye = $orderPay->is_pay;
            $orderPayLogModel->order_pay_detail_id = $points_detail->id;
            $orderPayLogModel->idx = $order->now_point;
            $orderPayLogModel->tip_1 = date('Y-m-d H:i')."管理员{$this->auth->nickname}设置为正常进行";
            $orderPayLogModel->tip_2 = date('Y-m-d H:i')."管理员设置为正常进行";
            $orderPayLogModel->tip_3 = date('Y-m-d H:i')."管理员设置为正常进行";

            Db::startTrans();
            try {
                setPostMessage(2,$order->specialist_id,'编号'.$order->sn.'订单经沟通仍正常推进，点击查看详情','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);
                setPostMessage(2,$order->user_id,'编号'.$order->sn.'订单经沟通仍正常推进，点击查看详情','/myOrder/detail?status='.$order->status.'&id='.$order->id);
                $excp->save();
                $is_true = OrderExcpModel::where(['status' => 0,'order_id'=>$excp->order_id])->find();
                if(!$is_true){
                    $order->is_excp = 0;
                    $order->is_stop = 1;
                    $order->save();
                }

                $orderPay->save();
                $orderPayLogModel->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                // dump($e->getMessage());die;
                $this->error("操作失败",$e->getMessage());
            }
            
            
            $this->success("操作成功", null, ['id' => $ids]);
        }
    }

    /**
     * 异常处理-正常进行
     */
    public function excp_nopass($ids = null)
    {
        $info = $this->request->post('dealinfo','');
        if($info == ''){
            $this->error("处理情况不能为空");
        }
        $is_debit_fee = $this->request->post('is_debit_fee','0');
        $debit_per = $this->request->post('debit_per','');
        $debit_explan = $this->request->post('debit_explan','');
        
        $excp = OrderExcpModel::where('id',$ids)->find();
        if(!$excp){
            $this->error("找不到异常信息");
        }
        if($excp->status > 0 ){
            $this->error("异常信息已处理");
        }

        if($is_debit_fee == '1'){
            if($debit_per == ''){
                $this->error("扣除比例不能为空");
            }
            if($debit_explan == ''){
                $this->error("扣除说明不能为空");
            }
            $excp->debit_per = $debit_per;
            $excp->debit_explan = $debit_explan;
        }


        $excp->status = '2';
        $excp->deal_info = $info;

        $order = OrderModel::where('id',$excp->order_id)->find();
        $orderPay = OrderPayModel::where('id',$excp->order_pay_id)->find();
        
        if ($this->request->isPost()) {
            //#审核状态
            // //插入节点状态
            //获取当前大节点
            $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
            if(!$points_detail){
                $this->error("找不到节点详细信息");
            }
            //节点明细小节点
            $orderPayLogModel = new OrderPayLogModel();
            $orderPayLogModel->order_id = $order->id;
            $orderPayLogModel->order_pay_id = $orderPay->id;
            $orderPayLogModel->tpye = $orderPay->is_pay;
            $orderPayLogModel->order_pay_detail_id = $points_detail->id;
            $orderPayLogModel->idx = $order->now_point;
            $orderPayLogModel->tip_1 = date('Y-m-d H:i')."管理员{$this->auth->nickname}设置为异常终止";
            $orderPayLogModel->tip_2 = date('Y-m-d H:i')."管理员设置为异常终止";
            $orderPayLogModel->tip_3 = date('Y-m-d H:i')."管理员设置为异常终止";

            $order->is_stop = 2;
            $orderPay->is_pay = 2;
            $orderPay->is_excp = 1;
            $orderPay->is_stop = 2;
            Db::startTrans();
            try {

                setPostMessage(2,$order->user_id,'编号'.$order->sn.'订单已中止，点击查看处理结果','/myOrder/detail?status='.$order->status.'&id='.$order->id);

                setPostMessage(2,$order->specialist_id,'编号'.$order->sn.'订单已中止，点击查看处理结果','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);
                $excp->save();
                $order->save();
                $orderPay->save();
                $orderPayLogModel->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error("操作失败",$e->getMessage());
            }
            
            
            $this->success("操作成功", null, ['id' => $ids]);
        }
    }

    /**
     * 查看-某用户
     */
    public function indexs()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $param = $this->request->param();
            $filter = isset($param['filter'])?json_decode($param['filter'],true):[];
            // dump($filter );die;
            $user_id = $param['uid'];
            $model = $this->model;
            if(isset($param['uid'])){
                $model = $model->where('user_id',$user_id);
            }
            $where = [];
            $model = $this->model;
            // $where['type'] = $param['type'];
            if(isset($filter['status'])){
                // $where[] = ['status','in',$filter['status']];
                $model = $model->whereIn('status',$filter['status']);
                
            }
            if(isset($filter['title'])){
                $model = $model->where('title|sn|id','like','%'.$filter['title'].'%');
            }
            if(isset($filter['createtime'])){
                $timeArr = explode(' - ', $filter['createtime']);
                // dump($timeArr);die;
                // $where[] = ['createtime','between',[$timeArr[0],$timeArr[1]]];
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $model = $model->whereTime('createtime','between',[$begin,$end]);
                }
            }
            $list = $model
                ->order($sort, $order)
                ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 查看-某专家
     */
    public function indexspec()
    {

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $param = $this->request->param();
            $filter = isset($param['filter'])?json_decode($param['filter'],true):[];
            $user_id = $param['uid'];
            $model = $this->model;
            if(isset($param['uid'])){
                $model = $model->where('specialist_id',$user_id);
            }
            $model = $this->model;
            if(isset($filter['status'])){
                if(!in_array(8,$filter['status']) && !in_array(9,$filter['status'])){
                    $model = $model->where('is_excp',0);
                    $model = $model->where('is_stop',1);
                }
                if(in_array(8,$filter['status']) && in_array(9,$filter['status'])){
                    $filter['status'] = array_diff($filter['status'],[8,9]);
                    $model = $model->where(function ($query) use($filter) {
                        if(count($filter['status'])){
                            $query->where('is_excp',1)->whereOr('is_stop',2)->whereOr('status', 'in', $filter['status']);
                        }else{
                            $query->where('is_excp',1)->whereOr('is_stop',2);
                        }

                    });
                }elseif(in_array(8,$filter['status']) && !in_array(9,$filter['status'])){
                    $filter['status'] = array_diff($filter['status'],[8]);
                    $model = $model->where(function ($query) use($filter) {
                        if(count($filter['status'])){
                            $query->where(['is_excp'=>1])->whereOr('status', 'in', $filter['status']);
                        }else{
                            $query->where(['is_excp'=>1]);
                        }
                    });
                    $model = $model->where('is_stop',1);
                }elseif(!in_array(8,$filter['status']) && in_array(9,$filter['status'])){
                    $filter['status'] = array_diff($filter['status'],[9]);
                    $model = $model->where(function ($query) use($filter) {
                        if(count($filter['status'])){
                            $query->where(['is_stop'=>2])->whereOr('status', 'in', $filter['status']);
                        }else{
                            $query->where(['is_stop'=>2]);
                        }
                    });
                    //$model = $model->where('is_excp',0);
                }else{
                    $model = $model->where('status', 'in', $filter['status']);
                }
            }
            if(isset($filter['specialist_source'])){
                $model = $model->whereIn('specialist_source',$filter['specialist_source']);

            }
            if(isset($filter['title'])){
                $model = $model->where('title|sn|id','like','%'.$filter['title'].'%');
            }
            if(isset($filter['createtime'])){
                $timeArr = explode(' - ', $filter['createtime']);
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $model = $model->whereTime('createtime','between',[$begin,$end]);
                }
            }
            //print_r($model->fetchSql(true)->select());exit;
            $list = $model
                ->order($sort, $order)
                ->paginate($limit);
            foreach($list as $v){
                if($v->is_stop == '2'){
                    $v->status = 9;
                }else{
                    if($v->is_excp == '1'){
                        $v->status = 8;
                    }
                }

            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
