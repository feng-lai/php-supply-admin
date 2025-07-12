<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Message extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'message';


    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'cate_text',
        'createtime_text',
        'updatetime_text',
    ];
    

    
    public function getCateList()
    {
        // 通知类型:0=默认,1=需求通知,2=后台通知,3=审核通知4=认证通知,5=订单通知
        return [0 => __('默认'), 1 => __('系统通知'), 2 => __('订单通知'), 3 => __('需求通知'), 4 => __('平台公告')];
    }


    public function getCateTextAttr($value, $data)
    {
        $value = $data['type'] ?: (isset($data['type']) ? $data['type'] : 0);
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
