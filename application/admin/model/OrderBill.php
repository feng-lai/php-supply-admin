<?php

namespace app\admin\model;

use think\Model;

class OrderBill extends Model
{
    protected $name = 'order_bill';

    public function order()
    {
        return $this->belongsTo('app\admin\model\order\Order', 'order_id','id',[],'LEFT')->bind([
            'status'	=> 'status'
        ])->setEagerlyType(0);
    }
}