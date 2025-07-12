<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class OrderPayOffline extends Model
{

    
    use SoftDelete;
    

    // 表名
    protected $name = 'order_pay_offline';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' =>'待审核', '1' => '审核通过', '2' => '审核未通过'];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function order()
    {
        return $this->belongsTo('order', 'order_id')->setEagerlyType(0);
    }

    public function orderPay()
    {
        return $this->belongsTo('order_pay', 'order_pay_id')->setEagerlyType(0);
    }




}
