<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Order as OrderModel;
use app\common\model\Requirement as RequirementModel;
use app\common\model\UserArchive;

/**
 * 测试接口
 */
class Demo extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1', 'test2','test3'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function test()
    {
        $this->success('返回成功', $this->request->param());
    }

    /**
     * 模拟后台-需求审核
     *
     * @ApiMethod (POST)
     * @ApiTitle    (模拟后台审核需求)
     * @param string $id    需求ID
     * @param string $status    变更需求状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", description="需求列表")
     */
    public function test1()
    {
        $id = $this->request->post('id',0);
        $status = $this->request->post('status',0);
        $RequirementModel = OrderModel::where('id',$id)->find();
        
        if($RequirementModel){
            $RequirementModel->status = $status;
            $RequirementModel->save();
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    /**
     * 模拟后台-需求审核
     *
     * @ApiMethod (POST)
     * @ApiTitle    (模拟后台审核需求)
     * @param string $id    需求ID
     * @param string $status    变更订单状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", description="需求列表")
     */
    public function test2()
    {
        $id = $this->request->post('id',0);
        $status = $this->request->post('status',0);
        $RequirementModel = RequirementModel::where('id',$id)->find();
        
        if($RequirementModel){
            $RequirementModel->status = $status;
            $RequirementModel->save();
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }


    /**
     * 需要登录的接口
     *
     */
    // public function test2()
    // {
    //     $this->success('返回成功', ['action' => 'test2']);
    // }

    /**
     * 需要登录且需要验证有相应组的权限
     *
     */
    public function test3()
    {
        // 原始 URL
        $original_url = "/plansAndTasks/programManagement/monthlyPlanningDetails?type=special&id=394&name=John";
        $original_url = "/taskPackage/pages/details/special-detail/special-detail?id=394";

        // 解析原始 URL
        $parsed_url = parse_url($original_url);

        // 获取原始 URL 中的查询字符串
        $query_string = isset($parsed_url['query']) ? $parsed_url['query'] : '';

        // 解析查询字符串为关联数组
        parse_str($query_string, $query_params);

        // 输出解析得到的参数数组
        print_r($query_params);

        $this->success('返回成功', ['action' => 'test3']);
    }

}
