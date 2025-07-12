<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class OrderPayDetail extends Model
{

    
    use SoftDelete;
    

    // 表名
    protected $name = 'order_pay_detail';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
    ];
    
    public function order()
    {
        return $this->belongsTo('order', 'order_id')->setEagerlyType(0);
    }




}
