<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Pay extends Model
{

    
    use SoftDelete;
    

    // 表名
    protected $name = 'pay';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        
    ];
    

    
    public function getIsPayList()
    {
        // 状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 
        return ['0' =>'未付款', '1' => '待审核', '2' => '服务中', '3' => '待验收', '4' => '待跟进', '5' => '已完成','6' => '已取消'];
    }


    public function getIsPayTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_pay']) ? $data['is_pay'] : '');
        $list = $this->getIsPayList();
        return isset($list[$value]) ? $list[$value] : '';
    }



}
