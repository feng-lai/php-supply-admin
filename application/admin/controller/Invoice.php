<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\InvoiceLog as InvoiceLogModel;

/**
 * 发票管理
 *
 * @icon fa fa-circle-o
 */
class Invoice extends Backend
{

    /**
     * Invoice模型对象
     * @var \app\admin\model\Invoice
     */
    protected $model = null;

    protected $noNeedRight = ['indexs','index1','index2'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\InvoiceLog;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index($type=null)
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);


            foreach ($list as $row) {
                $user = \app\admin\model\User::where('id',$row['user_id'])->find();
                if($user){
                    $row['username'] = $user->id_no_name?$user->id_no_name:$user->nickname;
                }else{
                    $row['username'] = '';
                }

                $row['order_price'] = \app\admin\model\order\Order::where('id',$row['order_id'])->value("total");
                // $row->visible(['id','order_id','type','title','company_number','amount','apply_user_id','rate','status']);
                
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $this->view->assign("type", $type);
        return $this->view->fetch('index');
    }



    public function indexs($type=null)
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $param = $this->request->param();
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $user_id = $param['uid'];
            if(isset($param['uid'])){
                $this->model->where('user_id',$user_id);
            }


            $list = $this->model

                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $user = \app\admin\model\User::where('id',$row['user_id'])->find();
                $row['username'] = $user->id_no_name?$user->id_no_name:$user->nickname;

            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $this->view->assign("type", $type);
        return $this->view->fetch('index');
    }

    /**
     * 查看
     */
    public function index1()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','order_id','type','title','company_number','amount','apply_user_id','rate','status']);
                
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch('index');
    }
    /**
     * 查看
     */
    public function index2()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','order_id','type','title','company_number','amount','apply_user_id','rate','status']);
                
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch('index');
    }

}
