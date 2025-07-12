<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'need_acceptance_text',
        'finishtime_text',
        'createtime_text',
        'updatetime_text',
        'confirm_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('待收款'), '1' => __('待审核'), '2' => __('服务中'), '3' => __('待验收'), '4' => __('待跟进'), '5' => __('已完成'), '6' => __('已取消'),'7' => __('未确认收款')];
    }

    public function getNeedAcceptanceList()
    {
        return ['0' => __('无需验收'), '1' => __('需要验收')];
    }

    public function getConfirmList()
    {
        return ['0' => __('待专家确认订单'), '1' => __('专家已确认'), '2' => __('专家确认超时')];
    }


    public function getConfirmTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['confirm']) ? $data['confirm'] : '');
        $list = $this->getConfirmList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getNeedAcceptanceTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['need_acceptance']) ? $data['need_acceptance'] : '');
        $list = $this->getNeedAcceptanceList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getFinishtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['finishtime']) ? $data['finishtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getUpdatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['updatetime']) ? $data['updatetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFinishtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    //生成唯一订单号
    public function createOrderNo()
    {
        return date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }

    public function log()
    {
        return $this->hasMany('OrderPay', 'order_id', 'id', [], 'LEFT')->setEagerlyType(0)->order('id','desc');
    }
    

}
