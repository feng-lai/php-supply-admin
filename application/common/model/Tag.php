<?php

namespace app\common\model;

use think\Db;
use think\Model;
use traits\model\SoftDelete;

class Tag extends Model
{

//    use SoftDelete;

    

    // 表名
    protected $name = 'tag';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'level_text',
        'type_text',
        'createtime_text',
        'updatetime_text'
    ];


    public function get_children($pid,$type){
        $arrCate = self::where('type',$type)->field('id,name,type,pid')->select();
        $lists = $this->getTree($arrCate,$pid);
        $ids = array_column($lists,'id');
        $ids[] = (int)$pid;
        return $ids;
    }
    public function getTree($arrCate, $pid = 0, $level = 0){
        static $arrTree = [];	//static函数执行完后变量值仍然保存
        if(empty($arrCate)) return [];
        $level++;
        foreach($arrCate as $key => $value){
            if($value['pid'] == $pid){
                $value['level'] = $level;
                $arrTree[] = $value;
                unset($arrCate[$key]);	//注销当前节点数据，减少已无用的遍历
                $this->getTree($arrCate, $value['id'], $level);	//递归调用
            }
        }
        return $arrTree;
    }

    public function getLevelList()
    {
        return ['1' => __('一级标签'), '2' => __('二级标签'), '3' => __('三级标签')];
    }

    public function getTypeList()
    {
        return ['1' => __('行业'), '2' => __('技能'), '3' => __('区域')];
    }


    public function getLevelTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['level']) ? $data['level'] : '');
        $list = $this->getLevelList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
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
