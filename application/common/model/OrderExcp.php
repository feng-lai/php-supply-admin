<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class OrderExcp extends Model
{

    
    use SoftDelete;
    

    // 表名
    protected $name = 'order_excp';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'createtime_text',
        'updatetime_text',
        'status_text',
        'cate_text',
        'is_pay_text'
    ];

    public function getIsPayList()
    {
        // 状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 
        return ['0' =>'待收款', '1' => '待审核', '2' => '服务中', '3' => '待验收', '4' => '待跟进', '5' => '已完成','6' => '已取消'];
    }


    public function getIsPayTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_pay']) ? $data['is_pay'] : '');
        $list = $this->getIsPayList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    

    
    public function getStatusList()
    {
        return ['0' =>'待处理', '1' => '正常进行', '2' => '已终止'];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCateList()
    {
        return ['1' => '需求方发起', '2' => '专家发起'];
    }


    public function getCateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['cate']) ? $data['cate'] : '');
        $list = $this->getCateList();
        return isset($list[$value]) ? $list[$value] : '';
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

}
