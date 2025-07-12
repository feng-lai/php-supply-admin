<?php

namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Requirement extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'requirement';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text',
        'user_type_text',
        'status_text',
        'open_price_data_text'
    ];
    

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getUserTypeList()
    {
        return ['1' => __('User_type 1'), '2' => __('User_type 2')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'),'1' => __('Status 1'),'2' => __('Status 2'),'3' => __('Status 3'),'4' => __('Status 4')];
    }

    public function getOpenPriceDataList()
    {
        return ['0' => __('Open_price_data 0'), '1' => __('Open_price_data 1')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getUserTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['user_type']) ? $data['user_type'] : '');
        $list = $this->getUserTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getOpenPriceDataTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['open_price_data']) ? $data['open_price_data'] : '');
        $list = $this->getOpenPriceDataList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }



}
