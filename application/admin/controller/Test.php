<?php

namespace app\admin\controller;



use app\common\model\Specialist as SpecialistModel;

class Test
{

    public function ceshi(){
        $model = new SpecialistModel();
        $list = $model
            ->alias("s")
            ->join('user u','s.user_id = u.id','left')
            ->join('fa_order_comment c', 's.id = c.to_user_id', 'left')
            ->join('fa_order d', 's.id = d.specialist_id', 'left')
            ->where('s.status','1')
            ->field('s.*,u.*,ROUND(IFNULL(AVG(c.points), 0), 1) as avg_score,IFNULL(COUNT(d.id), 0) as order_count')
            ->order([
                'avg_score' => 'desc', // 按照 avg_score 字段降序排序
                'order_count' => 'desc', // 然后按照 order_count 字段降序排序
            ])
            ->group('s.id')->select();

        echo $model->getLastSql();die;
    }

}
