<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Requirement extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'requirement';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text',
        'user_type_text',
        'status_text',
        'open_price_data_text',
        'createtime_text',
        'keywords_arr',
        'files_arr',
        'industry_arr',
        'skill_arr',
        'area_arr'
    ];


    public function getIndustryArrAttr($value, $data)
    {
        // $value = $value ? $value : '';
        $arr = [];
        if(isset($data['industry_ids']) and $data['industry_ids'] != ''){
            $arr = Tag::whereIn('id',$data['industry_ids'])->field("name")->select();
        }

        return $arr;
    }

    public function getSkillArrAttr($value, $data)
    {
        // $value = $value ? $value : '';
        $arr = [];
        if(isset($data['skill_ids']) and $data['skill_ids']!=''){
            $arr = Tag::whereIn('id',$data['skill_ids'])->field("name")->select();
        }

        return $arr;
    }

    public function getAreaArrAttr($value, $data)
    {
        // $value = $value ? $value : '';
        $arr = [];
        if(isset($data['area_ids']) and $data['area_ids']!=''){
            $arr = Tag::whereIn('id',$data['area_ids'])->field("name")->select();
        }

        return $arr;
    }

    
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
        //状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        return ['0' => __('待审核'),'1' => __('待匹配'),'2' => __('匹配中'),'3' => __('订单待确认'),'4' => __('已匹配'),'5'=>'已取消','6'=>'已失效'];
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
        $list = ['0' => __('待审核'),'1' => __('待匹配'),'2' => __('匹配中'),'3' => __('订单待确认'),'4' => __('已匹配'),'5'=>'已取消','6'=>'已失效'];
        // dump($list[$value]);die;
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getOpenPriceDataTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['open_price_data']) ? $data['open_price_data'] : '');
        $list = $this->getOpenPriceDataList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['createtime'] ?? "");
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }

    public function getKeywordsArrAttr($value, $data)
    {
        
        $value = $value ? $value : (explode(",",$data['keywords_tags'] ) ?? "");
        return $value;
    }

    public function getFilesArrAttr($value, $data)
    {
        $value = $value ? $value : (json_decode($data['files'],true) ?? "");
        return $value;
    }
    





}
