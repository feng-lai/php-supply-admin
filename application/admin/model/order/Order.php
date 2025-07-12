<?php

namespace app\admin\model\order;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'need_acceptance_text',
        'finishtime_text',
        'specialist_source_text',
        // 'user_id_no_name'
        'createtime_text',
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5'), '6' => __('Status 6'),'8' => __('Status 8'),'9' => __('Status 9')];
    }

    public function getNeedAcceptanceList()
    {
        return ['0' => __('Need_acceptance 0'), '1' => __('Need_acceptance 1')];
    }

    public function getSpecialistSourceList()
    {
        return ['0' => __('未匹配专家'), '1' => __('系统推荐'), '2' => __('用户预约'), '3' => __('专家主动申请')];
    }


    public function getSpecialistSourceTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['specialist_source']) ? $data['specialist_source'] : '');
        $list = $this->getSpecialistSourceList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getNeedAcceptanceTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['need_acceptance']) ? $data['need_acceptance'] : '');
        $list = $this->getNeedAcceptanceList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getFinishtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['finishtime']) ? $data['finishtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFinishtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id')->bind([
            'user_id_no_name'	=> 'nickname',
        ])->setEagerlyType(0);
    }

    public function specialist()
    {
        return $this->belongsTo('app\admin\model\User', 'specialist_id')->bind([
            'specialist_id_no_name'	=> 'id_no_name',
        ])->setEagerlyType(0);
    }

    public function requirement()
    {
        return $this->belongsTo('app\admin\model\Requirement', 'rid')->setEagerlyType(0);
    }

    public function rs()
    {
        return $this->belongsTo('app\common\model\RequirementSpecialist', 'requirement_specialist_id')->setEagerlyType(0);
    }

    

}
