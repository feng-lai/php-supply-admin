<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class OrderPayOffline extends Model
{

    // 表名
    protected $name = 'order_pay_offline';
    public function orderPay()
    {
        return $this->belongsTo('app\admin\model\order\Pay', 'order_pay_id','id',[],'LEFT')->bind([
            'pay_count' => 'total',
            'idx' => 'idx',
            'is_excp' => 'is_excp',
            'is_stop' => 'is_stop'
        ])->setEagerlyType(0);
    }

    public function order()
    {
        return $this->belongsTo('app\admin\model\order\Order', 'order_id','id',[],'LEFT')->bind([
            'total_count'	=> 'total',
            'ownership_no' => 'sn',
            'user_id' => 'user_id',
        ])->setEagerlyType(0);
    }
    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id')->bind([
            'user_id_no_name'	=> 'nickname',
        ])->setEagerlyType(0);
    }

}
