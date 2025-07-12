<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class SpecialistFav extends Model
{


    

    // 表名
    protected $name = 'specialist_fav';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = null;
    protected $deleteTime = null;

    // 追加属性
    protected $append = [
        'createtime_text'
    ];
    
    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


}
