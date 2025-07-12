<?php

namespace app\admin\model\order;

use think\Model;


class Pay extends Model
{

    

    

    // 表名
    protected $name = 'order_pay';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_pay_text',
        'pay_type_text'
    ];

    

    
    public function getIsPayList()
    {
        return ['0' => __('Is_pay 0'), '1' => __('Is_pay 1')];
    }

    public function getPayTypeList()
    {
        return ['0' => '未选择', '1' => '微信','2' => '支付宝','3' => '线下'];
    }

    public function getIsPayTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_pay']) ? $data['is_pay'] : '');
        $list = $this->getIsPayList();
        return isset($list[$value]) ? $list[$value] : '';
    }



    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }



}
