<?php

namespace app\admin\controller;

use app\admin\model\order\Pay;
use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\model\OrderPay as OrderPayModel;
/**
 * 地区管理
 *
 * @icon fa fa-circle-o
 */
class Orderlog extends Backend
{

    protected $model = null;
    protected $searchFields = 'id,order.sn';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new OrderPayModel();
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
//            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $param = $this->request->param();
            $filter = json_decode($param['filter'],true);

            if(isset($filter['a.createtime'])){
                $timeArr = explode(' - ', $filter['a.createtime']);
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $this->model->whereTime('a.createtime','between',[$begin,$end]);
                }
            }
            if(isset($filter['a.pay_type'])){
                $this->model->whereIn('a.pay_type',$filter['a.pay_type']);
            }



            $list = $this->model
                ->alias("a")
                ->join(['fa_order' => 'b'],'a.order_id = b.id','left')
                ->field("a.*,b.sn,b.user_id")
                ->order("a.createtime desc")
                ->where('a.is_pay','<>',0)
                ->paginate(10);

            $user = new \app\admin\model\User;
            foreach ($list as $k => $v) {
                $v->nickname = Db::name("user")->where(['id' => $v->user_id])->value("nickname");
                $v->mobile = Db::name("user")->where(['id' => $v->user_id])->value("mobile");
//                $v->user->visible(['nickname']);
//                $v->user->visible(['mobile']);
                $v['createtime'] = date("Y/m/d H:i",$v['createtime']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
