<?php

namespace app\common\model;

use think\Db;
use think\Model;
use traits\model\SoftDelete;

class RequirementSpecialist extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'requirement_specialist';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'createtime_text',
        'updatetime_text',
        'files_arr',
        'vertify_status_text',
    ];


    public function getStatusList()
    {
        //状态:0-专家待参与 1-平台待审核 2-需求方确认 3-已拒绝 4-已取消
        //状态:0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；
        return ['0'=>'专家待确认参与','1' => '专家已申请参与','2' => '待需求方确认','3' => '需求方已确认','4' => '已拒绝','5'=>'已取消'];
        // return ['0' => '待审核','1' => '待匹配','2' => '匹配成功','3' => '匹配失败'];
    }

    public function getVertifyStatusList()
    {
        //审核状态:0-待审核,1-已审核,2-不通过
        return [0=>'待平台审核',1 => '平台已审核通过',2 => '平台审核未通过'];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getVertifyStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['vertify_status']) ? $data['vertify_status'] : '');
        $list = $this->getVertifyStatusList();
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

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }

    public function requirement()
    {
        return $this->belongsTo('requirement', 'requirement_id')->setEagerlyType(0);
    }

    public function getFilesArrAttr($value, $data)
    {
        $value = $value ? $value : (json_decode($data['files'],true) ?? "");
        return $value;
    }
    





}
