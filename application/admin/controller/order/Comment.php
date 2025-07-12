<?php

namespace app\admin\controller\order;

use app\admin\model\OrderPayOffline;
use app\common\controller\Backend;
use app\admin\model\order\Pay as OrderPayModel;
use app\common\model\OrderComment;
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
class Comment extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\order\Order
     */
    protected $model = null;

    protected $noNeedRight = ['indexs'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new OrderComment;
    }

    public function indexs()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
//            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $param = $this->request->param();
            $user_id = $param['uid'];
            $filter = isset($param['filter'])?json_decode($param['filter'],true):[];
            if(isset($filter['comment_type'])){
                if($filter['comment_type'] === '被评价'){
                    $this->model->where('a.to_user_id',$user_id);
                }else{
                    $this->model->where('a.user_id',$user_id);
                }
            }else{
                $this->model->where('a.to_user_id|a.user_id',$user_id);
            }

            if(isset($filter['createtime'])){
                $timeArr = explode(' - ', $filter['createtime']);
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $this->model->where('a.createtime','between time',[$begin,$end]);
                }
            }
            if(isset($filter['comment'])){
                $point = [];
                foreach($filter['comment'] as $v){
                    if($v == '差评'){
                        $point[] = 1;
                        $point[] = 2;
                    }
                    if($v == '中评'){
                        $point[] = 3;
                    }
                    if($v == '好评'){
                        $point[] = 4;
                        $point[] = 5;
                    }
                }
                $this->model->where("points",'in',$point);
            }
            /**
            if(isset($filter['comment']) and ['comment'] === '差评'){
                $this->model->where("points",'in','1,2');
            }
            if(isset($filter['comment']) and ['comment'] === '中评'){
                $this->model->where("points",'in','3');
            }
            if(isset($filter['comment']) and ['comment'] === '好评'){
                $this->model->where("points",'in','4,5');
            }
            **/

            $list = $this->model
                ->alias("a")
                ->join('order b','a.order_id = b.id','left')
                ->join('user u','a.to_user_id = u.id','left')
                ->join('user c','a.user_id = u.id','left')
                ->field("a.*,b.sn,u.nickname,c.nickname as user_name,b.id as id")
                ->order("a.id desc")
                ->paginate(15);
            $data = $list->items();
            foreach($data as $v){
                $v->createtime = date('Y-m-d H:i:s',$v->createtime);
            }
            $result = array("total" => $list->total(), "rows" => $data);

            return json($result);
        }
        return $this->view->fetch();
    }

}
