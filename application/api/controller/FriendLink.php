<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 友情链接
 *
 * @icon fa fa-circle-o
 */
class FriendLink extends Api
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
     * FriendLink模型对象
     * @var \app\admin\model\cms\FriendLink
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\FriendLink;
    }

    /**
     * 友情链接列表
     *
     * @ApiMethod (POST)
     * @ApiSummary  (无分页)
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','title':'string','link':'string'}}", description="列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1704635681",
        "data": {
            "total": 2,
            "rows": [
            {
                "id": 1,
                "title": "百度",//标题
                "link": "http://baidu.com",//链接
                "weigh": 2,//排序
                "status": "1",
                "createtime": 1704460566,
                "updatetime": 1704460566,
                "status_text": "Status 1",
                "createtime_text": "2024-01-05 21:16:06",
                "updatetime_text": "2024-01-05 21:16:06"
            }
            ]
        },
        "domain": "http://xxx"//域名
        })
     */
    public function index(){
        $list = $this->model
        ->where('status','1')
        
        ->order('weigh', "desc")
        ->field('id,title,link,weigh,status,createtime,updatetime')
        ->select();
        $result = ['total' => count($list), 'rows' => $list];
        $this->success('', $result);
    }




}
