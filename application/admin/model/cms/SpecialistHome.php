<?php

namespace app\admin\model\cms;

use think\Model;
use traits\model\SoftDelete;

class SpecialistHome extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'specialist_home';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'createtime_text',
        'updatetime_text'
    ];
    

    protected static function init()
    {
        //self::afterInsert(function ($row) {
            //$pk = $row->getPk();
            //$row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        //});
    }

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
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
