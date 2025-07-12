<?php

namespace app\common\model;

use app\admin\model\Tag;
use think\Db;
use think\Model;
use traits\model\SoftDelete;

class SpecialistAuth extends Model
{

    use SoftDelete;

    // 表名
    protected $name = 'specialist_auth';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // protected $hidden = ['createtime','updatetime','id_no_front_image','id_no_backend_image','id_no','status'];

    protected $type = [
        'case_json'    =>  'array',
        'feature_json'     =>  'array',
        'certificate_json'  =>  'array',
        'edu_json'  =>  'array',
        'feature_json' => 'array',
        'case_json' => 'array',
    ];

    // 追加属性
    protected $append = [
        'status_text',
        'createtime_text',
        'updatetime_text',
        'industry_arr',
        'skill_arr',
        'area_arr'
    ];

    public function getIndustryArrAttr($value, $data)
    {
        $arr = [];
        if($data['industry_ids']!=''){
            $arr = Tag::whereIn('id',$data['industry_ids'])->field("id,name")->select();
        }

        return $arr;
    }

    public function getSkillArrAttr($value, $data)
    {
        $arr = [];
        if($data['skill_ids']!=''){
            $arr = Tag::whereIn('id',$data['skill_ids'])->field("id,name")->select();
        }

        return $arr;
    }

    public function getAreaArrAttr($value, $data)
    {
        $arr = [];
        if($data['area_ids']!=''){
            $arr = Tag::whereIn('id',$data['area_ids'])->field("id,name")->select();
        }

        return $arr;
    }

    public function getStatusList()
    {
        return ['0' => __('未审核'), '1' => __('审核已通过'), '2' => __('审核未通过')];
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
