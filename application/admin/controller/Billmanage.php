<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\OrderPay;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 地区管理
 *
 * @icon fa fa-circle-o
 */
class Billmanage extends Backend
{

    protected $model = null;

    protected $noNeedRight = ['send','other','infos','otherinfo'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderBill;
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function index($type=null,$uid=null){

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $this->assignconfig('type', $type);
        $this->view->assign('type', $type);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->relationSearch = true;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where = [];
            $map = $this->request->param("filter");
            $map = json_decode($map,true);

            if(!empty($map['createtime'])){
                $time = explode(' - ',$map['createtime']);

                $start_time = strtotime($time[0]);
                $end_time = strtotime($time[1]);
                $where['a.createtime'] = ['between time', [$start_time, $end_time]];
            }


            if(!empty($map['sn'])){
                $where['a.sn'] = ['like', '%'.$map['sn'].'%'];
            }

            if($type == 1){
                $where['a.status'] = ['in', '1,2,3,4'];
            }

            if($type == 2){
                $where['a.send_status'] = ['=', '1'];
                $where['a.is_stop'] = ['=', '1'];
                $where['a.status'] = ['=', '5'];
            }

            if($type == 4){
                $where['a.send_status'] = ['=', '2'];
                $where['a.is_stop'] = ['=', '1'];
                $where['a.status'] = ['=', '5'];
                $where['e.status'] = ['=', '1'];
            }


            if(!empty($uid)){
                $where['a.specialist_id'] = ['=', $uid];
            }

            $list = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_user c','a.specialist_id = c.id','left')
                ->join('fa_specialist s','a.specialist_id = s.user_id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left')
                ->where($where)
                ->field('a.id,a.need_invoice,a.status,a.total,a.sn,a.status,b.nickname,c.nickname as specia_name,c.id_no_bank_name,c.id_no_bank_id,c.id_no_name as name,d.debit_per,d.	
debit_explan,d.deal_info,e.total as bill_total,e.real_total,a.specialist_id,a.createtime,s.id_no_bank_user')
                ->group("a.id")
                ->order('a.updatetime','desc');

            if($type == 5){
                //$where['a.is_stop'] = ['=', '2'];
                //$where['e.status'] = ['=', '1'];
                $list = $list->where(function($query){
                    $query->where(['a.send_status'=>'2','a.is_stop'=>'1','a.status'=>'6','e.status'=>'1'])->whereOr(['a.send_status'=>'2','a.is_stop'=>'2','d.status'=>'2','e.status'=>'1']);
                });
            }
            if($type == 3){
                $list = $list->where(['a.send_status'=>'1'])->where(function($query){
                    $query->where(['a.is_stop'=>'1','a.status'=>'6'])->whereOr(['a.is_stop'=>'2','d.status'=>'2']);

                });
                //$where = "(a.send_status = '1' and a.is_stop = '2' and d.status = '2' or (a.send_status = '1' and a.is_stop = '1' and a.status = '6'))";
            }

            $list = $list->paginate($limit)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['totals'] = $v['total'];
                $data[$k]['total'] = OrderPay::where('order_id',$v['id'])->where('is_pay','in',[2,3,4,5])->sum('total');
                $specialist = \app\admin\model\Specialist::where('user_id',$v['specialist_id'])->find();
                if($specialist){
                    if(!$v['id_no_bank_id']){
                        $data[$k]['id_no_bank_id'] = $specialist->id_no_bank_id;
                    }
                    if(!$v['id_no_bank_name']){
                        $data[$k]['id_no_bank_name'] = $specialist->id_no_bank_name;
                    }
                }
                $data[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }

//echo Db::name('order')->getLastSql();die;


            $result = array("total" => $list['total'], "rows" => $data);

            return json($result);
        }
        $total = 0;
        $tui_total = 0;
        $where = [];
        if($type == 1){
            $where['a.status'] = ['in', '1,2,3,4'];
            $data = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_specialist c','a.specialist_id = c.id','left')
                ->join('fa_specialist s','a.specialist_id = s.user_id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left')
                ->where($where)
                ->group("a.id")
                ->order('a.updatetime','desc')
                ->field("a.total")
                ->select();
            foreach ($data as $item) {
                $total += $item['total'];
            }
        }

        if($type == 2){
            $where['a.send_status'] = ['=', '1'];
            $where['a.is_stop'] = ['=', '1'];
            $where['a.status'] = ['=', '5'];
            $data = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_specialist c','a.specialist_id = c.id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left')
                ->where($where)
                ->group("a.id")
                ->order('a.updatetime','desc')
                ->field("a.total")
                ->select();
            foreach ($data as $item) {
                $total += $item['total'];
            }
        }

        if($type == 3){
            //$where = "(a.send_status = '1' and a.is_stop = '2' and d.status = '2' or (a.send_status = '1' and a.is_stop = '1' and a.status = '6'))";

            $data = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_specialist c','a.specialist_id = c.id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left');
            $data = $data->where(['a.send_status'=>'1'])->where(function($query){
                $query->where(['a.is_stop'=>'1','a.status'=>'6'])->whereOr(['a.is_stop'=>'2','d.status'=>'2']);

            });
            //$data = $data->where(['a.send_status'=>'1','a.is_stop'=>'2','d.status'=>'2'])->whereOr(function($query){
                //$query->where(['a.send_status'=>'1','a.is_stop'=>'1','a.status'=>'6']);
            //});

             $data = $data->field("a.total,d.debit_per,a.id")
                ->order('a.updatetime','desc')
                ->group("a.id")
                ->select();
            foreach ($data as $key=>$val){
                $val['total'] = OrderPay::where('order_id',$val['id'])->where('is_pay','in',[2,3,4,5])->sum('total');
                $val['debit_per'] = $val['debit_per'] ?:0;
                $price = $val['total'] - ($val['total'] * ($val['debit_per'] / 100));
                $total += $price;
                $tui_total += $val['total'] * ($val['debit_per'] / 100);

            }

        }
        if($type == 4){
            $where['a.send_status'] = ['=', '2'];
            $where['a.is_stop'] = ['=', '1'];
            $where['a.status'] = ['=', '5'];
            $where['e.status'] = ['=', '1'];

            $data = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_specialist c','a.specialist_id = c.id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left')
                ->where($where)
                ->group("a.id")
                ->order('a.updatetime','desc')
                ->field("e.real_total")
                ->select();
            foreach ($data as $item) {
                $total += $item['real_total'];
            }
        }

        if($type == 5){
            //$where['a.is_stop'] = ['=', '2'];
            //$where['e.status'] = ['=', '1'];

            $data = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_specialist c','a.specialist_id = c.id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left');
            $data = $data->where(['a.send_status'=>'2','a.is_stop'=>'2','d.status'=>'2','e.status'=>'1'])->whereOr(function($query){
                $query->where(['a.send_status'=>'2','a.is_stop'=>'1','a.status'=>'6','e.status'=>'1']);
            });
                //->where($where)
            $data = $data->group("a.id")
                ->order('a.updatetime','desc')
                ->field("e.real_total")
                ->select();

            $list = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_specialist c','a.specialist_id = c.id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left');
            $list = $list->where(['a.send_status'=>'2','a.is_stop'=>'2','d.status'=>'2','e.status'=>'1'])->whereOr(function($query){
                $query->where(['a.send_status'=>'2','a.is_stop'=>'1','a.status'=>'6','e.status'=>'1']);
            });
            $list = $list->order('a.updatetime','desc')
                ->group("a.id")
                ->field("e.total,e.real_total,d.debit_per")
                ->select();
            foreach ($list as $val) {
                $val['debit_per'] = $val['debit_per'] ?:0;

                $total += $val['total'] - ($val['total'] * ($val['debit_per'] / 100));
                $tui_total += $val['total'] * ($val['debit_per'] / 100);
            }
            $tui_total = number_format($tui_total, 2);
        }


        $this->view->assign("total", $total);
        $this->view->assign("tui_total", $tui_total);
        return $this->view->fetch();
    }

    public function send($ids = null){


        if ($this->request->isPost()) {
            $row = $this->request->post('row/a');
            $OrderBill = new \app\admin\model\OrderBill;
            $info = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')

                ->join('fa_user c','a.specialist_id = c.id','left')
                ->join('fa_specialist s','s.user_id = c.id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left')
                ->where(['a.id' => $row['id']])
                ->field('a.id,a.user_id,a.need_invoice,a.total,a.sn,a.status,b.nickname,c.nickname as specia_name,s.id_no_bank_name,s.id_no_bank_id,c.id_no_name as name,d.debit_per,d.	
debit_explan,d.deal_info,e.total as bill_total,e.real_total,s.id_no_bank_user')
                ->find();


            $data = [];
            $data['order_id'] = $info['id'];
            $data['total'] = $info['total'];
            $data['rate_fee'] = $row['rate_fee'];
            $data['rate_fee_per'] = $row['rate_fee_per'];
            $data['sys_fee'] = $row['sys_fee'];
            $data['sys_fee_per'] = isset($row['sys_fee_per'])?$row['sys_fee_per']:'0.5';
            $data['real_total'] = $row['real_total'];
            $data['bank_account'] = $info['id_no_bank_id'];
            $data['bank_username'] = $info['id_no_bank_user'];
            $data['status'] = 1;
            $data['createtime'] = time();
            $data['order_sn'] = $info['sn'];
            $data['user_id'] = $info['user_id'];
            $data['type'] = 1;
            $data['grant_voucher'] = $row['grant_voucher'];


            Db::startTrans();
            try {
                Db::name('order_bill')->insert($data);
                Db::name('order')->where(['id' => $info['id']])->update(['send_status' => 2]);
                $amount = Db::name('order')->where(['id' => $info['id']])->value("total");
                Db::name('invoice_log')->where(['order_id' => $info['id']])->update(['rate' => $row['rate_fee_per'],'amount' => $amount * $row['rate_fee_per'] * 0.01]);
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
        $row = Db::name('order')
            ->alias("a")
            ->join('fa_user b','a.user_id = b.id','left')
            ->join('fa_user c','a.specialist_id = c.id','left')
            ->join('fa_specialist s','s.user_id = a.specialist_id','left')
            ->join('fa_order_excp d','a.id = d.order_id','left')
            ->join('fa_order_bill e','a.id = e.order_id','left')
            ->where(['a.id' => $ids])
            ->field('a.id,a.need_invoice,a.total,a.sn,a.status,b.nickname,c.nickname as specia_name,c.id_no_bank_name,c.id_no_bank_id,c.id_no_name as name,d.debit_per,d.	
debit_explan,d.deal_info,e.total as bill_total,e.real_total,s.id_no_bank_name as user_id_no_bank_name,s.id_no_bank_id as user_id_no_bank_id,b.id_no_name,s.id_no_bank_user')
            ->find();

        $rate_exp_time = Db::name("config")->where(['name' => 'rate_exp_time'])->value("value");

        $rate_custom = Db::name("config")->where(['name' => 'rate_custom'])->value("value");
        $rate_system = Db::name("config")->where(['name' => 'rate_system'])->value("value");

        $times = explode(" - ", $rate_exp_time);
        $start_time = strtotime($times[0]);
        $end_time = strtotime($times[1]);
        $current_time = time();

        if ($current_time >= $start_time && $current_time <= $end_time) {
            //自定义
            $rate_rate = $rate_custom;
            $row['rate_rate'] = $rate_custom;
            $is_rate = 1;
        } else {
            $rate_rate = $rate_system;
            $row['rate_rate'] = $rate_system;
            $is_rate = 2;
        }
        $this->assignconfig('rate_rate', $rate_rate);
        $this->assignconfig('is_rate', $is_rate);
        $statusArray = [
            0 => '待收款',
            1 => '待审核',
            2 => '服务中',
            3 => '待验收',
            4 => '待跟进',
            5 => '已完成',
            6 => '已取消',
            7 => '未确认收款'
        ];
        $need_invoice_arr = [0 => '不需要' , 1=> '普通增值税专用发票'];
        $row['status'] = $statusArray[$row['status']];
        $row['need_invoice'] = $need_invoice_arr[$row['need_invoice']];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function other($ids = null){

        if ($this->request->isPost()) {
            $row = $this->request->post('row/a');
            $OrderBill = new \app\admin\model\OrderBill;
            $info = Db::name('order')
                ->alias("a")
                ->join('fa_user b','a.user_id = b.id','left')
                ->join('fa_user c','a.specialist_id = c.id','left')
                ->join('fa_order_excp d','a.id = d.order_id','left')
                ->join('fa_order_bill e','a.id = e.order_id','left')
                ->where(['a.id' => $row['id']])
                ->field('a.id,a.user_id,a.need_invoice,a.total,a.sn,a.status,b.nickname,c.nickname as specia_name,c.id_no_bank_name,c.id_no_bank_id,c.id_no_name as name,d.debit_per,d.	
debit_explan,d.deal_info,e.total as bill_total,e.real_total')
                ->find();




            $data = [];
            $data['order_id'] = $info['id'];
            $data['total'] = $info['total'];
            $data['bank_account'] = $info['id_no_bank_id'];
            $data['bank_username'] = $info['name'];
            $data['status'] = 1;
            $data['createtime'] = time();
            $data['order_sn'] = $info['sn'];
            $data['user_id'] = $info['user_id'];
            $data['type'] = 2;
            $data['grant_voucher'] = $row['grant_voucher'];
            $data['refund_voucher'] = $row['refund_voucher'];
            $data['real_total'] = $info['total'] * ($info['debit_per'] / 100);
            Db::startTrans();
            try {
                Db::name('order_bill')->insert($data);
                Db::name('order')->where(['id' => $info['id']])->update(['send_status' => 2]);
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

        $row = Db::name('order')
            ->alias("a")
            ->join('fa_user b','a.user_id = b.id','left')
            ->join('fa_user c','a.specialist_id = c.id','left')
            ->join('fa_specialist s','s.user_id = a.specialist_id','left')
            ->join('fa_order_excp d','a.id = d.order_id','left')
            ->join('fa_order_bill e','a.id = e.order_id','left')
            ->where(['a.id' => $ids])
            ->field('a.id,a.user_id,a.need_invoice,a.total,a.sn,a.status,b.nickname,s.nickname as specia_name,s.id_no_bank_name,s.id_no_bank_id,s.id_no_bank_user as name,d.debit_per,d.	
debit_explan,d.deal_info,e.total as bill_total,e.real_total,b.id_no_bank_name as user_id_no_bank_name,b.id_no_bank_id as user_id_no_bank_id,b.id_no_name,c.id_no_name as cname,b.company_bank_id,b.company_bank_name,s.id_no_bank_user')
            ->find();



        if(!$row['user_id_no_bank_id']){
            $row['user_id_no_bank_id'] = $row['company_bank_id'];
        }
        if(!$row['user_id_no_bank_name']){
            $row['user_id_no_bank_name'] = $row['company_bank_name'];
        }
        $total = OrderPay::where('order_id',$row['id'])->where('is_pay','in',[2,3,4,5])->sum('total');
        $row['return_price'] =  $total - ($total * ($row['debit_per'] / 100));
        $row['price'] =  $total * ($row['debit_per'] / 100);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function infos($ids = null){
        $row = Db::name('order')
            ->alias("a")
            ->join('fa_user b','a.user_id = b.id','left')
            ->join('fa_user c','a.specialist_id = c.id','left')
            ->join('fa_specialist s','s.user_id = a.specialist_id','left')
            ->join('fa_order_excp d','a.id = d.order_id','left')
            ->join('fa_order_bill e','a.id = e.order_id','left')
            ->where(['a.id' => $ids])
            ->field('a.id,a.need_invoice,a.total,a.sn,a.status,b.nickname,c.nickname as specia_name,s.id_no_bank_name,s.id_no_bank_id,c.id_no_name as name,d.debit_per,d.	
debit_explan,d.deal_info,e.total as bill_total,e.real_total,b.id_no_bank_name as user_id_no_bank_name,b.id_no_bank_id as user_id_no_bank_id,b.id_no_name,e.rate_fee_per,e.sys_fee_per,e.rate_fee,e.sys_fee,e.grant_voucher,s.id_no_bank_user')
            ->find();
        $statusArray = [
            0 => '待收款',
            1 => '待审核',
            2 => '服务中',
            3 => '待验收',
            4 => '待跟进',
            5 => '已完成',
            6 => '已取消',
            7 => '未确认收款'
        ];
        $need_invoice_arr = [0 => '不需要' , 1=> '普通增值税专用发票'];
        $row['status'] = $statusArray[$row['status']];
        $row['need_invoice'] = $need_invoice_arr[$row['need_invoice']];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    public function otherinfo($ids = null){
        $row = Db::name('order')
            ->alias("a")
            ->join('fa_user b','a.user_id = b.id','left')
            ->join('fa_user c','a.specialist_id = c.id','left')
            ->join('fa_specialist s','s.user_id = c.id','left')
            ->join('fa_order_excp d','a.id = d.order_id','left')
            ->join('fa_order_bill e','a.id = e.order_id','left')
            ->where(['a.id' => $ids])
            ->order('d.createtime','desc')
            ->field('a.id,a.need_invoice,a.total,a.sn,a.status,b.nickname,c.nickname as specia_name,s.id_no_bank_name,s.id_no_bank_id,c.id_no_name as name,d.debit_per,d.	
debit_explan,d.deal_info,e.total as bill_total,e.real_total,b.id_no_bank_name as user_id_no_bank_name,b.id_no_bank_id as user_id_no_bank_id,b.id_no_name,e.rate_fee_per,e.sys_fee_per,e.rate_fee,e.sys_fee,e.grant_voucher,e.refund_voucher,b.company_bank_id,b.company_bank_name,s.id_no_bank_user')
            ->find();

        if(!$row['user_id_no_bank_id']){
            $row['user_id_no_bank_id'] = $row['company_bank_id'];
        }
        if(!$row['user_id_no_bank_name']){
            $row['user_id_no_bank_name'] = $row['company_bank_name'];
        }


        $row['return_price'] = $row['total'] * ((100 - $row['debit_per']) / 100);
        $row['price'] = $row['total'] * ($row['debit_per'] / 100);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
