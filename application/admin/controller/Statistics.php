<?php

namespace app\admin\controller;

use app\admin\model\order\Pay;
use app\admin\model\User;
use app\admin\model\Specialist;
use app\common\model\UserLoginLog;
use app\common\model\Requirement;
use app\common\model\Order;
use app\common\model\UserDisableLog;
use app\admin\model\UserArchive;
use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;


class Statistics extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }


    public function index($ids = null){
        $time = $this->request->get('time','');
        $range = $this->request->get('range','');
        if($range){
            $time = '';
        }

        $s_time = $this->request->get('s_time','');
        $s_range = $this->request->get('s_range','');
        if($s_range){
            $s_time = '';
        }

        $re_time = $this->request->get('re_time','');
        $re_range = $this->request->get('re_range','');
        if($re_range){
            $re_time = '';
        }

        $or_time = $this->request->get('or_time','');
        $or_range = $this->request->get('or_range','');
        if($or_range){
            $or_time = '';
        }

        $user = new User();

        $user_login = new UserLoginLog();

        $user_disable = new UserDisableLog();

        $requirement = new Requirement();

        $order = new Order();

        $userA = new UserArchive();

        $specialistModel = new Specialist();

        //-------------------------------------需求方数据

        $data = [
            $user->where(['role_type' => 1])->count(),
            $user->where(['role_type' => 1])->whereTime('createtime', 'today')->count(),
            $user_login->where(['role_type' => 1])->whereTime('login_time', 'today')->count(),
            $userA->whereTime('createtime', 'today')->group("user_id")->count(),
            $user->where(['typedata' => 2])->whereTime('createtime', 'today')->count(),
            $user_login->where('typedata',2)->whereTime('login_time', 'today')->count(),
            $user->where(['typedata' => 2,'status' => 'locked'])->count()
        ];

        $where = [];
        $start_timestamp = '';
        $end_timestamp = '';
        if($time){
            list($start_time, $end_time) = explode(' - ', $time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        if($range){
            list($start_time, $end_time) = explode(' - ', $range);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        $list_date = cut_date($start_timestamp,$end_timestamp);

        $new = []; //新增
        $active = [];//活跃用户
        $new_verify = [];//新增用户已实名
        $company_user = [];//新增企业用户
        $company_active_user = []; //活跃企业
        $company_disable_user = []; //禁用企业用户
        foreach($list_date['data'] as $v){
            if($list_date['type'] == 1){
                $where['createtime'] = ['BETWEEN', [strtotime($v), strtotime($v.' 23:59:59')]];
            }
            if($list_date['type'] == 2){
                // 计算该月的第一天
                $firstDayOfMonth = date("Y-m-01", strtotime($v));
                // 计算该月的最后一天
                $lastDayOfMonth = date("Y-m-t", strtotime($v));
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth.' 23:59:59')]];
            }
            if($list_date['type'] == 3){
                // 计算该年的第一天
                $firstDayOfMonth = $v.'-01-01';
                // 计算该年的最后一天
                $lastDayOfMonth = $v.'-12-31 23:59:59';
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth)]];

            }

            $new[] = $user->where($where)->where(['role_type' => 1])->count();
            $active[] = $user_login->where(['role_type' => 1])->where($where)->count();
            $new_verify[] = $userA->where($where)->count();
            $company_user[] = $user->where($where)->where(['role_type' => 1])->where('typedata',2)->count();
            $company_active_user[] = $user_login->where($where)->where(['role_type' => 1])->where('typedata',2)->count();
            $company_disable_user[] = $user_disable->where($where)->where(['role_type' => 1])->where('typedata',2)->count();
        }

        $this->assignconfig('dates', $list_date['data']);
        $this->assignconfig('value', [
            'new'=>$new,
            'active'=>$active,
            'new_verify'=>$new_verify,
            'company_user'=>$company_user,
            'company_active_user'=>$company_active_user,
            'company_disable_user'=>$company_disable_user,
        ]);


        //-----------------------------专家数据

        $specialist = [
            $specialistModel->count(),
            $user->where(['role_type' => '2','typedata' => 1])->whereTime('createtime', 'today')->count(),
            $user->where(['role_type' => '2','typedata' => 1])->whereTime('logintime', 'today')->count(),
            $user->alias('u')->where(['u.typedata' => '2','u.typedata' => 1,'s.status'=>'2'])->join('specialist s','s.user_id = u.id')->count()
        ];

        $where = ['role_type' => '2'];

        $start_timestamp = '';
        $end_timestamp = '';
        if($s_time){
            list($start_time, $end_time) = explode(' - ', $s_time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        if($s_range){
            list($start_time, $end_time) = explode(' - ', $s_range);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        $list_date = cut_date($start_timestamp,$end_timestamp);

        $s_new = [];//新增
        $s_active = [];//活跃
        $s_disable = [];//禁用
        foreach($list_date['data'] as $v){
            if($list_date['type'] == 1){
                $where['createtime'] = ['BETWEEN', [strtotime($v), strtotime($v.' 23:59:59')]];
            }
            if($list_date['type'] == 2){
                // 计算该月的第一天
                $firstDayOfMonth = date("Y-m-01", strtotime($v));
                // 计算该月的最后一天
                $lastDayOfMonth = date("Y-m-t", strtotime($v));
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth.' 23:59:59')]];
            }
            if($list_date['type'] == 3){
                // 计算该年的第一天
                $firstDayOfMonth = $v.'-01-01';
                // 计算该年的最后一天
                $lastDayOfMonth = $v.'-12-31 23:59:59';
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth)]];

            }
            $s_new[] = $user->where($where)->count();
            $s_active[] = $user_login->where($where)->count();
            $s_disable[] = $user_disable->where($where)->count();
        }

        $this->assignconfig('s_dates', $list_date['data']);
        $this->assignconfig('s_value', [
            's_new'=>$s_new,
            's_active'=>$s_active,
            's_disable'=>$s_disable
        ]);

        //-------------------------------需求分析

        $re= [
            $requirement->count(),
            $requirement->whereTime('createtime', 'yesterday')->count(),
            $requirement->whereTime('createtime', 'last month')->count(),
            $order->group('rid')->count(),
            $requirement->where('status','5')->count(),
            $requirement->where('status','2')->where('type','1')->count(),
        ];

        $where = [];
        $start_timestamp = '';
        $end_timestamp = '';
        if($re_time){
            list($start_time, $end_time) = explode(' - ', $re_time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        if($re_range){
            list($start_time, $end_time) = explode(' - ', $re_range);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        $list_date = cut_date($start_timestamp,$end_timestamp);

        $re_new = [];//新增
        foreach($list_date['data'] as $v){
            if($list_date['type'] == 1){
                $where['createtime'] = ['BETWEEN', [strtotime($v), strtotime($v.' 23:59:59')]];
            }
            if($list_date['type'] == 2){
                // 计算该月的第一天
                $firstDayOfMonth = date("Y-m-01", strtotime($v));
                // 计算该月的最后一天
                $lastDayOfMonth = date("Y-m-t", strtotime($v));
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth.' 23:59:59')]];
            }
            if($list_date['type'] == 3){
                // 计算该年的第一天
                $firstDayOfMonth = $v.'-01-01';
                // 计算该年的最后一天
                $lastDayOfMonth = $v.'-12-31 23:59:59';
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth)]];

            }
            $re_new[] = $requirement->where($where)->count();
        }


        $this->assignconfig('re_dates', $list_date['data']);
        $this->assignconfig('re_value', $re_new);



        //----------------------------------订单分析
        $or = [
            $order->alias('o')->join('user u','u.id = o.user_id')->join('user s','s.id = o.specialist_id')->count(),
            $order->whereTime('createtime', 'yesterday')->count(),
            $order->whereTime('createtime', 'today')->count(),
            $order->alias('o')->join('user u','u.id = o.user_id')->join('user s','s.id = o.specialist_id')->whereTime('o.createtime', 'last month')->count(),
            $order->alias('o')->join('user u','u.id = o.user_id')->join('user s','s.id = o.specialist_id')->where('o.status','5')->where('o.is_excp',0)->count(),
            $order->alias('o')->join('user u','u.id = o.user_id')->join('user s','s.id = o.specialist_id')->where('o.status','6')->count(),
        ];

        $where = [];
        $start_timestamp = '';
        $end_timestamp = '';
        if($or_time){
            list($start_time, $end_time) = explode(' - ', $or_time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        if($or_range){
            list($start_time, $end_time) = explode(' - ', $or_range);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
        }
        $list_date = cut_date($start_timestamp,$end_timestamp);

        $or_new = [];//新增
        foreach($list_date['data'] as $v){
            if($list_date['type'] == 1){
                $where['createtime'] = ['BETWEEN', [strtotime($v), strtotime($v.' 23:59:59')]];
            }
            if($list_date['type'] == 2){
                // 计算该月的第一天
                $firstDayOfMonth = date("Y-m-01", strtotime($v));
                // 计算该月的最后一天
                $lastDayOfMonth = date("Y-m-t", strtotime($v));
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth.' 23:59:59')]];
            }
            if($list_date['type'] == 3){
                // 计算该年的第一天
                $firstDayOfMonth = $v.'-01-01';
                // 计算该年的最后一天
                $lastDayOfMonth = $v.'-12-31 23:59:59';
                $where['createtime'] = ['BETWEEN', [strtotime($firstDayOfMonth), strtotime($lastDayOfMonth)]];

            }
            $or_new[] = $order->where($where)->count();
        }


        $this->assignconfig('or_dates', $list_date['data']);
        $this->assignconfig('or_value', $or_new);




        $this->view->assign("today", getTimeRange('today'));
        $this->view->assign("week", getTimeRange('week'));
        $this->view->assign("month", getTimeRange('month'));
        $this->view->assign("year", getTimeRange('year'));

        $this->view->assign("time",$time);
        $this->view->assign("range",$range);

        $this->view->assign("s_time",$s_time);
        $this->view->assign("s_range",$s_range);

        $this->view->assign("re_time",$re_time);
        $this->view->assign("re_range",$re_range);

        $this->view->assign("or_time",$or_time);
        $this->view->assign("or_range",$or_range);

        $this->view->assign('data',$data);
        $this->view->assign("specialist", $specialist);
        $this->view->assign("re", $re);
        $this->view->assign("or", $or);

        return $this->view->fetch();
    }


}
