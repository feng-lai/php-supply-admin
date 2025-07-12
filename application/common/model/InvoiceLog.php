<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class InvoiceLog extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'invoice_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'pass_time_text'
    ];
    

    
    public function getTypeList()
    {
        // 发票种类:1=普通发票,2=专用发票
        return ['1' => __('普通发票'), '2' => __('专用发票')];
    }

    public function getStatusList()
    {
        //审核状态:0=未审核,1=已通过,2=已拒绝
        return ['0' => __('未审核'), '1' => __('已通过'), '2' => __('已拒绝')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPassTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pass_time']) ? $data['pass_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPassTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
