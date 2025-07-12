<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页展示专家
 *
 * @icon fa fa-circle-o
 */
class SpecialistHome extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];
    /**
     * Home模型对象
     * @var \app\admin\model\cms\SpecialistHome
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\SpecialistHome;
    }



    /**
     * 首页专家列表
     *
     * @ApiMethod (POST)
     * @ApiSummary  (无分页)
     * @param string $keyword 关键词
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','title':'string','link':'string'}}", description="列表")
     * @ApiReturnParams   (name="domain", type="string", required=true, sample="http://xxx", description="域名")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1704635333",
        "data": {
            "total": 1,
            "rows": [
            {
                "id": 1,
                "image": "",//图片
                "name": "111",//姓名
                "content": "<p>123123</p>",//内容
                "weigh": 1,//排序
                "status": "1",//状态 1-正常 2-禁用
                "createtime": 1704461259,
                "updatetime": 1704461309,
                "deletetime": null,
                "status_text": "Status 1",
                "createtime_text": "2024-01-05 21:27:39",//创建时间
                "updatetime_text": "2024-01-05 21:28:29"//更新时间
            }
            ]
        },
        "domain": "http://xxx"//域名
        })
     */
    public function index(){
        $limit = $this->request->post('limit',10);
        $keyword = $this->request->post('keyword','');
        $where = [];
        $model = $this->model;
        if ($keyword) {
            $model = $model->where('name', 'like', "%$keyword%");
        }
        $list = $model
        ->where('status','1')
        ->where($where)
        ->order('weigh', "desc")
        ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        $this->success('', $result);
    }

}
