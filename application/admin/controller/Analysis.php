<?php

namespace app\admin\controller;

use app\admin\model\order\Pay;
use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 地区管理
 *
 * @icon fa fa-circle-o
 */
class Analysis extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }


    public function index($ids = null){

        $order_bill = new \app\admin\model\OrderBill;
        $time = $this->request->get('time','');
        $chart_time = $this->request->get('chart_time','');
        $chart_day = $this->request->get('chart_day','');
        $table_time = $this->request->get('table_time','');
        $table_range = $this->request->get('table_range','');
        if($chart_time){
            $chart_day = '';
        }
        if($table_time){
            $table_range = '';
        }
        $where = [];
        if($time){
            list($start_time, $end_time) = explode(' - ', $time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            $where['createtime'] = ['BETWEEN', [$start_timestamp, $end_timestamp]];
        }
        //专家服务费
        $services_count = $order_bill->where(['type' => 1])->where($where)->sum("total");
        //平台服务费
        $platform = $order_bill->where(['type' => 1])->where($where)->sum("sys_fee");
        //代扣专家税金及发票税费
        $rate_fee = $order_bill->where(['type' => 1])->where($where)->sum("rate_fee");
        //其他费用
        $services_count_true = $order_bill->where(['type' => 2])->where($where)->sum("rate_fee");
        //专家实收
        $services_count_true = $services_count + $services_count_true - $platform - $rate_fee;
        //资金结余
        $services_count_false = $platform + $rate_fee;


        $this->view->assign("services_count_false", $services_count_false);
        $this->view->assign("services_count_true", $services_count_true);
        $this->view->assign("rate_fee", $rate_fee);
        $this->view->assign("platform", $platform);
        $this->view->assign("services_count", $services_count);

        $where = [];
        if($chart_time){
            list($start_time, $end_time) = explode(' - ', $chart_time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            $where['createtime'] = ['BETWEEN', [$start_timestamp, $end_timestamp]];
        }
        if($chart_day){
            list($start_time, $end_time) = explode(' - ', $chart_day);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            $where['createtime'] = ['BETWEEN', [$start_timestamp, $end_timestamp]];
        }
        $line = $order_bill
            ->where($where)
            ->where(['type' => 1])
            ->field('DATE_FORMAT(FROM_UNIXTIME(createtime, "%Y-%m-%d"), "%c.%e") as date, SUM(sys_fee) as total')
            ->order("createtime asc")
            ->group('date')
            ->select();

        $dates = array_column($line, 'date');
        $value = array_column($line, 'total');

        $this->assignconfig('dates', $dates);
        $this->assignconfig('value', $value);


        $where = [];
        if($table_time){
            list($start_time, $end_time) = explode(' - ', $table_time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            $where['createtime'] = ['BETWEEN', [$start_timestamp, $end_timestamp]];
        }
        if($table_range){
            list($start_time, $end_time) = explode(' - ', $table_range);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time.' 23:59:59');
            $where['createtime'] = ['BETWEEN', [$start_timestamp, $end_timestamp]];
        }
        $data = $order_bill
            ->where(['type' => 1])
            ->where($where)
            ->field('DATE_FORMAT(FROM_UNIXTIME(createtime, "%Y-%m-%d"), "%c.%e") as date, sys_fee,real_total')
            ->order("createtime asc")
            ->select();

        $this->view->assign("data", $data);
        $this->view->assign("today", getTimeRange('today'));
        $this->view->assign("week", getTimeRange('week'));
        $this->view->assign("month", getTimeRange('month'));
        $this->view->assign("year", getTimeRange('year'));
        $this->view->assign("day3", getTimeRange('day3'));
        $this->view->assign("day6", getTimeRange('day6'));
        $this->view->assign("day9", getTimeRange('day9'));
        $this->view->assign("time", $time);
        $this->view->assign("chart_time", $chart_time);
        $this->view->assign("chart_day", $chart_day);
        $this->view->assign("table_time", $table_time);
        $this->view->assign("table_range", $table_range);

        return $this->view->fetch();
    }


}
