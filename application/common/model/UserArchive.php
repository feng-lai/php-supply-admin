<?php

namespace app\common\model;

use think\Db;
use think\Model;


class UserArchive extends Model
{


    // 表名
    protected $name = 'user_archive';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'prevtime_text',
        'logintime_text',
        'jointime_text',
        'status_text',
        'typedata_text',
        'verify_status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['30' => __('Status 30')];
    }

    public function getTypedataList()
    {
        return ['个人' => __('个人'), '企业' => __('企业')];
    }

    public function getVerifyStatusList()
    {
        return ['0' => __('Verify_status 0'), '1' => __('Verify_status 1'), '2' => __('Verify_status 2')];
    }


    public function getPrevtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['prevtime']) ? $data['prevtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['logintime']) ? $data['logintime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getJointimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['jointime']) ? $data['jointime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypedataTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['typedata']) ? $data['typedata'] : '');
        $list = $this->getTypedataList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getVerifyStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['verify_status']) ? $data['verify_status'] : '');
        $list = $this->getVerifyStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPrevtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLogintimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setJointimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
