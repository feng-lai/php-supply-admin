<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Notice extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'notice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'publish_time_text'
    ];
    

    



    public function getPublishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['publish_time']) ? $data['publish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPublishTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
