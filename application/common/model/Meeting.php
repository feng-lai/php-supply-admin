<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Meeting extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'meeting';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];
    

    
    public function getTypeList()
    {
        //发起方:1=需求方,2=专家
        return ['1' => __('需求方'), '2' => __('专家')];
    }

    public function getStatusList()
    {
        //状态:0=待处理,1=已处理
        return ['0' => __('待处理'), '1' => __('已处理')];
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




}
