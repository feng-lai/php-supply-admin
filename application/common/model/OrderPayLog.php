<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;


class OrderPayLog extends Model
{

    
    use SoftDelete;
    

    // 表名
    protected $name = 'order_pay_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'createtime_text',
        'updatetime_text',
        'type_text'
    ];
    

    
    public function getTypeList()
    {
        // 付款状态:0-未付款,1-待审核,2-服务中,3-已完成,4-已取消,5-异常
        return ['0' =>'未付款', '1' => '待审核', '2' => '服务中', '3' => '已完成', '4' => '已取消', '5' => '异常'];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
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

    public function order()
    {
        return $this->belongsTo('app\admin\model\order\Order', 'order_id','id',[],'LEFT')->bind([
            'total_count'	=> 'total',
            'ownership_no' => 'sn',
            'user_id' => 'user_id',
        ])->setEagerlyType(0);
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id')->bind([
            'user_id_no_name'	=> 'nickname',
            'mobile' => 'mobile'
        ])->setEagerlyType(0);
    }
    public function orderPay()
    {
        return $this->belongsTo('app\admin\model\order\Pay', 'order_pay_id','id',[],'LEFT')->bind([
            'pay_count' => 'total',
            'is_excp' => 'is_excp',
            'is_stop' => 'is_stop'
        ])->setEagerlyType(0);
    }



}
