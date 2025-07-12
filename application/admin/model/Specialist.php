<?php

namespace app\admin\model;

use app\admin\model\Level;
use think\Model;
use traits\model\SoftDelete;

class Specialist extends Model
{

//    use SoftDelete;

    

    // 表名
    protected $name = 'specialist';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $type = [
        'case_json'    =>  'array',
        'feature_json'     =>  'array',
        'certificate_json'  =>  'array',
        // 'keywords_json'  =>  'array',
        'edu_json'  =>  'array',
//        'industry_json' => 'array',
        // 'skill_ids' => 'array',
        // 'industry_ids' => 'array',
        // 'level_ids' => 'array'
    ];

    // 追加属性
    protected $append = [
        'status_text',
        'keywords_arr',
        'case_arr',
        'level_arr',
        'createtime_text',
        'industry_arr',
        'skill_arr',
        'area_arr'
    ];


    public function getIndustryArrAttr($value, $data)
    {
        // $value = $value ? $value : '';
        $arr = [];
        if($data['industry_ids']!=''){
            $arr = Tag::whereIn('id',$data['industry_ids'])->field("name")->select();
        }

        return $arr;
    }

    public function getSkillArrAttr($value, $data)
    {
        // $value = $value ? $value : '';
        $arr = [];
        if($data['skill_ids']!=''){
            $arr = Tag::whereIn('id',$data['skill_ids'])->field("name")->select();
        }

        return $arr;
    }

    public function getAreaArrAttr($value, $data)
    {
        // $value = $value ? $value : '';
        $arr = [];
        if($data['area_ids']!=''){
            $arr = Tag::whereIn('id',$data['area_ids'])->field("name")->select();
        }

        return $arr;
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        // dump($value);die;
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getKeywordsArrAttr($value, $data)
    {
        
        $value = $value ? $value : (explode(",",$data['keywords_json'] ) ?? "");
        return $value;
    }

    public function getCaseArrAttr($value, $data)
    {
        // dump(json_decode($data['keywords_json'],true ));die;
        $value = $value ? $value : (json_decode($data['case_json'],true ) ?? []);
        // $value = json_decode($value,true);
        return $value;
    }

    public function getLevelArrAttr($value, $data)
    {
        // $value = $value ? $value : '';
        $arr = [];
        if($data['level_ids']!=''){
            $arr = explode(",",$data['level_ids']);;
        }
        
        return $arr;
    }


    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['createtime'] ?? "");
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    




}
