<?php

namespace app\common\model;

use think\Model;

/**
 * 配置模型
 */
class UserDisableLog extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}
