<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class OrderComment extends Model
{

    
    use SoftDelete;
    

    // 表名
    protected $name = 'order_comment';
    
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
        'type_text'
    ];

    public function getTypeList()
    {
        return ['1' => '需求方对专家的评价', '2' => '专家对需求方的评价'];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCateList()
    {
        return ['1' => '需求方发起', '2' => '专家发起'];
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
