<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Edu extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'edu';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];
    

    







}
