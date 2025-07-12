<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Tag extends Model
{

//    use SoftDelete;

    

    // 表名
    protected $name = 'tag';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'level_text',
        'type_text',
        'createtime_text',
        'updatetime_text'
    ];
    

    
    public function getLevelList()
    {
        return ['1' => __('Level 1'), '2' => __('Level 2'), '3' => __('Level 3')];
    }

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3')];
    }


    public function getLevelTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['level']) ? $data['level'] : '');
        $list = $this->getLevelList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['createtime'] ?? "");
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getUpdatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['updatetime'] ?? "");
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    



}
