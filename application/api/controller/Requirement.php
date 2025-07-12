<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Config as ConfigModel;
use app\common\model\InvoiceLog as InvoiceLogModel;
use fast\Random;
use think\Config;
use think\Validate;
use think\Db;
use app\common\model\TagRequirement as TagRequirementModel;
use app\common\model\Tag as TagModel;
use app\common\model\Specialist as SpecialistModel;
use app\common\model\Requirement as RequirementModel;
use app\common\model\User;
use app\common\model\RequirementSpecialist as RequirementSpecialistModel;
use app\common\model\Meeting as MeetingModel;
use app\common\model\Order as OrderModel;
use app\common\model\Invoice as InvoiceModel;
use app\common\model\OrderPay as OrderPayModel;
use app\common\model\OrderPayLog as OrderPayLogModel;
use app\common\model\OrderPayDetail as OrderPayDetailModel;
use fast\Sensitive;
use think\Request;
// use think\facade\Request;
/**
 * 需求接口
 */
class Requirement extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third', 'index'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 需求列表列表
     *
     * @ApiMethod (POST)
     * @ApiSummary  (无token时返回假需求，有token时返回真实需求)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $keyword 关键词
     * @param string $industry_ids     行业标签，例：1,2,3
     * @param string $skill_ids     技能标签，例：1,2,3
     * @param string $area_ids     区域标签，例：1,2,3
     * @param string $lowest_price     报价起点
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="需求列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1703175528",
        "data": {
            "total": 3, //总条数
            "rows": [ //当前页记录
            {
                "id": 3,
                "type": "1",
                "user_name": "小布丁",
                "user_type": "1",
                "title": "这是一个真需求",//类型:1=真实需求,2=虚假需求
                "sn": "9007655",
                "content": "<p>萨达发大水发大水</p><p>s地方asf 安德森</p><p>阿萨法撒旦法</p>",
                "status": 1,//状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
                "begin": "一周内",
                "end": "一个月",
                "keywords_tags": "1,2,3",
                "files": "/supply/upload/20231201/aadfe428068ac2917e877181dc97a597.xlsx",
                "open_price_data": "0",
                "price": 100,
                "createtime": 1701763089,
                "updatetime": 1701763089,
                "publishtime": "2023-12-11 21:46:34",
                "deletetime": null,
                "user_id": 10,
                "num": 1,
                "avatar": "",
                "role_type": "1",
                "typedata": "2",
                "type_text": "Type 1",
                "user_type_text": "User_type 1",
                "status_text": "Status 1",
                "open_price_data_text": "Open_price_data 0",
                "createtime_text": "2023-12-05 15:58:09",
                "keywords_arr": [
                "1",
                "2",
                "3"
                ]
            }
            ]
        }
        })
     */
    public function index()
    {
        //$token = $this->request->header('token');
        //$token = \app\common\library\Token::get($token);
        //$type = "1";

        if($this->auth->verify_status == 1){
            // 真是需求
            $type = "1";
        }else{
            // 虚假需求
            $type = "2";
        }
        $model = new RequirementModel();
        $model = $model->alias('s')->field('s.*,u.avatar,u.role_type,u.typedata,u.nickname');
        $model = $model->where('s.type',$type);
        $limit = $this->request->post('limit');
        $keyword = $this->request->post('keyword','');
        $lowest_price = $this->request->post('lowest_price');
        $where = [];
        $postData = $this->request->post();

        $industry_ids = $postData['industry_ids'];
        if($industry_ids && count($industry_ids)){
            $model = $model->where(function($query) use ($industry_ids) {
                foreach($industry_ids as $v){
                    $ids = explode(',',$v);
                    foreach($ids as $val){
                        $query->whereOr("FIND_IN_SET('{$val}', s.industry_ids)");
                    }

                }
            });
        }

        $skill_ids = $postData['skill_ids'];
        if($skill_ids && count($skill_ids)){
            $model = $model->where(function($query) use ($skill_ids) {
                foreach($skill_ids as $v){
                    $ids = explode(',',$v);
                    foreach($ids as $val){
                        $query->whereOr("FIND_IN_SET('{$val}', s.skill_ids)");
                    }
                }
            });
        }

        $area_ids = $postData['area_ids'];
        if($area_ids && count($area_ids)){
            $model = $model->where(function($query) use ($area_ids) {
                foreach($area_ids as $v){
                    $ids = explode(',',$v);
                    foreach($ids as $val){
                        $query->whereOr("FIND_IN_SET('{$val}', s.area_ids)");
                    }
                }
            });
        }

        if ($keyword) {
            $model = $model->where('s.user_name|s.title|s.content|s.keywords_tags', 'like', "%$keyword%");
        }
        if($lowest_price){
            $model = $model->where('s.price','=', $lowest_price);
        }
        //隐藏专家已参与的需求
        if($this->auth->id){
            $subQuery = RequirementSpecialistModel::where('user_id','=',$this->auth->id)
                ->column('requirement_id');
            $model = $model->whereNotIn('s.id',$subQuery);
        }
        // dump($where);die;
        $list = $model->join('user u','s.user_id = u.id','left')->where('s.status','2')->where($where)
        ->order('s.id', "desc")
        ->group('s.id')
        ->paginate($limit)->each(function($item, $key){
            $industry_ids = $item->industry_ids;
            $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];

            $skill_ids = $item->skill_ids;
            $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

            $area_ids = $item->area_ids;
            $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];


            $item['skill'] = TagModel::whereIn('id',$skill_arr)->select();
            $item['industry'] = TagModel::whereIn('id',$industry_arr)->select();
            $item['area'] = TagModel::whereIn('id',$area_arr)->select();
                if($this->auth->verify_status != 1){
                    $img = \app\common\model\Config::where('name','user_img')->value('value');
                    $name = $item['user_name'];
                    $item['content'] = mb_substr($item['content'],0,5);
                    $firstChar = mb_substr($name, 0, 1, 'UTF-8');
                    $length = mb_strlen($name, 'UTF-8') - 1;
                    $item['user_name'] = $firstChar . str_repeat('*', $length >= 0 ? $length : 0);
                    $item['avatar'] = $img;
                }
            if(!$item['nickname']){
                $item['nickname'] = $item['user_name'];
            }
            return $item;
        });

        $data = [];

        $data['accord_count'] = $list->total();
        $data['count'] =  Db::name("requirement")->where('type',1)->where("deletetime is null")->count();
        $data['skill_count'] = Db::name("tag")->where(['type' => '2','level' => '1'])->count();
        $data['industry_count'] = Db::name("tag")->where(['type' => '1','level' => '1'])->count();
        $data['area_count'] = Db::name("tag")->where(['type' => '3','level' => '1'])->count();

        $result = ['total' => $list->total(), 'rows' => $list->items(),'info' => $data];
        $this->success('', $result);
    }



    /**
     * 创建需求
     *
     * @ApiMethod (POST)
     * @ApiSummary  (无token时返回假需求，有token时返回真实需求)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $specialist_user_id    预约专家的user_id(未预约传0)
     * @param string $id 编辑id
     * @param string $title    标题
     * @param string $content    需求描述
     * @param string $open_price_data     是否开启服务费起点：0=关闭，1=开启
     * @param string $price     服务费起点
     * @param string $begintime     开始时间(例:一周内)
     * @param string $endtime     结束时间(例:一个月)
     * @param string $keywords_tags     关键词(多个用,连接例:UI设计,网页设计)
     * @param string $files     附件({url:/supply/upload/20231201/a.xlsx,name:xx.xlsx})
     * @param string $industry_ids     行业标签，例：1,2,3
     * @param string $skill_ids     技能标签，例：1,2,3
     * @param string $area_ids     区域标签，例：1,2,3
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", description="需求")
     * @ApiReturn({
        "code": 1,
        "msg": "操作成功",
        "time": "1704974500",
        "data": "7",//需求ID
        "domain": "http://supply.test"
        })
     */
    public function createRequirement()
    {
        $specialist_user_id = $this->request->post('specialist_user_id',0);
        $begin = $this->request->post('begintime');
        $end = $this->request->post('endtime');
        $title = $this->request->post('title');
        $content = $this->request->post('content','');
        $keywords_tags = $this->request->post('keywords_tags');
        $files = htmlspecialchars_decode($this->request->post('files'));
        $industry_ids = $this->request->post('industry_ids');
        $skill_ids = $this->request->post('skill_ids');
        $area_ids = $this->request->post('area_ids');
        $open_price_data = $this->request->post('open_price_data');
        $price = $this->request->post('price');
        $status = 0;

        $s_files = [];
        if($files){
            //敏感图片
            foreach(json_decode($files,'true') as $v){
                $is = explode('.',$v['url']);
                if(in_array($is[1],['png','jpg','jpeg','JPG','PNG','JPEG'])){
                    if(!Sensitive::pic(config::get('upload.cdnurl').$v['url'])){
                        $s_files[] = $v['url'];
                    }
                }

            }
        }


        //敏感词
        $word = [];
        $word = array_merge($word,Sensitive::word($content),Sensitive::word($title),Sensitive::word($keywords_tags));
        $word = array_unique($word);




        $model = new RequirementModel();
        $model->sn = $model->createSn();

        $model->title = $title;
        $model->s_word = implode(',',$word);
        $model->s_files = implode(',',$s_files);
        $model->begin = $begin;
        $model->end = $end;
        $model->content = $content;
        $model->keywords_tags = $keywords_tags;
        $model->files = $files;
        $model->industry_ids = $industry_ids;
        $model->skill_ids = $skill_ids;
        $model->area_ids = $area_ids;
        $model->open_price_data = $open_price_data;
        $model->price = $price;
        $model->status = $status;
        $model->user_id = $this->auth->id;

        $model->user_name = $this->auth->id_no_name?$this->auth->id_no_name:$this->auth->username;
        if(intval($specialist_user_id)>0){
            $model->specialist_user_id = $specialist_user_id;
        }

        Db::startTrans();
        try {
            $model->save();
            $industry_arr = explode(",", $industry_ids);
            $skill_arr = explode(",", $skill_ids);
            $area_arr = explode(",", $area_ids);
            foreach ($industry_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $model->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($skill_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $model->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($area_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $model->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            //通知平台审核
            setPostMessage(3,0,'id为'.$this->auth->id.'的用户创建了一个需求：'.$title,'/kfSypMgbqw.php/requirement/detail/ids/'.$model->id);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$model->id);
        
        
    }
    /**
     * 
     */
    /**
     * 需求-需求方预约专家
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求ID
     * @param string $specialist_user_id    预约专家的user_id
     */
    public function expertApplySpecialist(){
        $id = $this->request->post('id');
        $specialist_user_id = $this->request->post('specialist_user_id',0);

        if($this->auth->role_type === '2'){
            // 专家 数据隔离
            $this->error("只有需求方才能预约专家");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证后才能预约专家");
        }
        $requirement = RequirementModel::where('id',$id)->where('user_id',$this->auth->id)->find();
        if(!$requirement){
            $this->error("需求ID不合法");
        }

        $has = RequirementSpecialistModel::where('requirement_id',$id)->where('user_id',$specialist_user_id)->count();
        if($has>0){
            $this->error("您已预约过该专家");
        }
        
        $model = new RequirementSpecialistModel();
        $model->requirement_id = $id;
        $model->user_id = $specialist_user_id;
        $model->desc = '';
        $model->files = '';
        $model->status = '0';
        $model->type = '2';
        Db::startTrans();
        try {
            $requirement->status = '2';
            $requirement->save();
            $model->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$model->id);
    }
    /**
     * 需求详情
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求ID
     * @ApiReturn({
        "code": 1,
        "msg": "操作成功",
        "time": "1705298111",
        "data": {
            "id": 18,
            "type": "1",
            "user_name": "周星星",
            "user_type": "1",
            "title": "需求十三",
            "sn": null,
            "content": "需求描述",
            "status": 2,
            "begin": "立即开始",
            "end": "一个月",
            "keywords_tags": "1,2",
            "files": "",
            "open_price_data": "1",
            "price": 700,
            "createtime": 1704976187,
            "updatetime": 1705045752,
            "publishtime": null,
            "deletetime": null,
            "user_id": 16,
            "num": 1,
            "skill_ids": "14,16",
            "area_ids": "13",
            "industry_ids": "4,8",
            "cancel_reason": null,
            "username": "18853338444",
            "nickname": "188****8444",
            "mobile": "18853338444",
            "id_no_name": "周星星",
            "avatar": "",
            "skill": [
            {
                "id": 14,
                "name": "前端开发",
                "createtime": 1701524579,
                "deletetime": null,
                "pid": 0,
                "path": "-",
                "level": "1",
                "updatetime": 1701763570,
                "type": "2",
                "sort": 1000,
                "level_text": "一级标签",
                "type_text": "技能",
                "createtime_text": "2023-12-02 21:42:59",
                "updatetime_text": "2023-12-05 16:06:10"
            },
            {
                "id": 16,
                "name": "后端开发",
                "createtime": 1701763583,
                "deletetime": null,
                "pid": 0,
                "path": "-",
                "level": "1",
                "updatetime": 1701763583,
                "type": "2",
                "sort": 1000,
                "level_text": "一级标签",
                "type_text": "技能",
                "createtime_text": "2023-12-05 16:06:23",
                "updatetime_text": "2023-12-05 16:06:23"
            }
            ],
            "industry": [
            {
                "id": 4,
                "name": "网络工程",
                "createtime": 0,
                "deletetime": null,
                "pid": 2,
                "path": "-2-",
                "level": "2",
                "updatetime": 1701763300,
                "type": "1",
                "sort": 1000,
                "level_text": "二级标签",
                "type_text": "行业",
                "createtime_text": "1970-01-01 08:00:00",
                "updatetime_text": "2023-12-05 16:01:40"
            },
            {
                "id": 8,
                "name": "机器学习",
                "createtime": 1,
                "deletetime": null,
                "pid": 9,
                "path": "-2-9-",
                "level": "3",
                "updatetime": 1701763501,
                "type": "1",
                "sort": 1000,
                "level_text": "三级标签",
                "type_text": "行业",
                "createtime_text": "1970-01-01 08:00:01",
                "updatetime_text": "2023-12-05 16:05:01"
            }
            ],
            "area": [
            {
                "id": 13,
                "name": "山东省",
                "createtime": 1701524546,
                "deletetime": null,
                "pid": 0,
                "path": "-",
                "level": "1",
                "updatetime": 1701524546,
                "type": "3",
                "sort": 1000,
                "level_text": "一级标签",
                "type_text": "区域",
                "createtime_text": "2023-12-02 21:42:26",
                "updatetime_text": "2023-12-02 21:42:26"
            }
            ],
            "requirement_specialist_1": [//平台推荐专家
            {
                "id": 1,
                "requirement_id": 18,//需求ID
                "user_id": 17,//专家ID
                "desc": "",//申请描述
                "files": "",//文件列表，多个文件,分开
                "status": "0",//状态:0-专家待确认参与 1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消;
                "createtime": 1704984430,
                "updatetime": 1704984430,
                "deletetime": null,
                "type": "1",//发起方:1-平台推荐 2-需求方预约 3-专家申请
                "reason": null,
     *          "refuse_desc":"", //拒绝原因
     *          "failed_desc":"", //沟通信息审核不通过原因
                "id_no_name": "周星星",
                "status_text": "待确认",
                "createtime_text": "2024-01-11 22:47:10",
                "updatetime_text": "2024-01-11 22:47:10"
            }
            ],
            "requirement_specialist_2": [],//需求方预约的专家
            "requirement_specialist_3": [//专家主动申请
            {
                "id": 2,
                "requirement_id": 18,
                "user_id": 18,
                "desc": "我很棒",
                "files": "",
                "status": "1",
                "createtime": 1704986225,
                "updatetime": 1705045829,
                "deletetime": null,
 *              "refuse_desc":"", //拒绝原因
 *              "failed_desc":"", //沟通信息审核不通过原因
                "type": "3",
                "reason": "弄错了",
                "id_no_name": null,
                "status_text": "已确认",
                "createtime_text": "2024-01-11 23:17:05",
                "updatetime_text": "2024-01-12 15:50:29"
            }
            ],
            "user": {
            "id": 16,
            "group_id": 0,
            "username": "18853338444",
            "nickname": "188****8444",
            "password": "e7b87422a0d315024a722b08fc92b469",
            "salt": "tpUoGu",
            "email": "",
            "mobile": "18853338444",
            "avatar": "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgaGVpZ2h0PSIxMDAiIHdpZHRoPSIxMDAiPjxyZWN0IGZpbGw9InJnYigxNjAsMjI5LDE2NSkiIHg9IjAiIHk9IjAiIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIj48L3JlY3Q+PHRleHQgeD0iNTAiIHk9IjUwIiBmb250LXNpemU9IjUwIiB0ZXh0LWNvcHk9ImZhc3QiIGZpbGw9IiNmZmZmZmYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIHRleHQtcmlnaHRzPSJhZG1pbiIgZG9taW5hbnQtYmFzZWxpbmU9ImNlbnRyYWwiPjE8L3RleHQ+PC9zdmc+",
            "level": 1,
            "gender": 0,
            "birthday": null,
            "bio": "",
            "money": "0.00",
            "score": 0,
            "successions": 1,
            "maxsuccessions": 1,
            "prevtime": 1704974169,
            "logintime": 1705142033,
            "loginip": "172.18.0.185",
            "loginfailure": 0,
            "joinip": "192.168.65.1",
            "jointime": 1703158149,
            "createtime": 1703158149,
            "updatetime": 1705142033,
            "token": "",
            "status": "normal",
            "verification": {
                "email": 0,
                "mobile": 0
            },
            "id_no": null,
            "id_no_front_image": null,
            "id_no_backend_image": null,
            "company_name": null,
            "company_id_no": null,
            "company_id_no_image": null,
            "company_attachfile": null,
            "verify_status": "1",
            "id_no_name": "周星星",
            "role_type": "1",
            "typedata": "1",
            "company_bank_name": null,
            "company_bank_id": null,
            "url": "/u/16"
            },
            "type_text": "Type 1",
            "user_type_text": "User_type 1",
            "status_text": "匹配中",
            "open_price_data_text": "Open_price_data 1",
            "createtime_text": "2024-01-11 20:29:47",
            "keywords_arr": [
            "1",
            "2"
            ],
           "select_user":"已匹配用户昵称",  //已匹配的用户昵称
            "join": {
                "id": 23,
                "requirement_id": 33,
                "user_id": 23,
                "desc": "22222222222",//描述
                "files": "/uploads/20240127/5c643c5a1da7fb300559b6ec84957977.png",//文件，多个,隔开
                "status": "2",//状态:0-专家待确认参与 1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消;
                "createtime": 1706369499,
                "updatetime": 1706423261,
                "deletetime": null,
                "type": "3",//发起方:1-平台推荐 2-需求方预约 3-专家申请
                "reason": null,
                "vertify_status": 1,//审核状态：0-平台未审核 1-审核通过 2-审核不通过
                "id_no_name": "柯贤",//申请专家名称
                "meeting_id": null,//沟通ID
                "meeting_desc": null,//沟通描述
                "meeting_status": null,//申请沟通状态:0=待处理,1=已处理,2-不通过
                "meeting_info": null,//沟通反馈信息
                "meeting_confirm": null,//申请沟通专家确认状态:0-未确认,1-已确认，2-已拒绝
                "status_text": "待需求方确认",
                "createtime_text": "2024-01-27 23:31:39",
                "updatetime_text": "2024-01-28 14:27:41",
                "files_arr": [
                    "/uploads/20240127/5c643c5a1da7fb300559b6ec84957977.png"
                ],
                "vertify_status_text": "平台已审核通过"
            },
            "join_text": "匹配中",
            "join_type": "2",、//0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；6-已失效
            "order": {
                "id": 5,
                "sn": "2024011216191248481014",//订单编号
                "rid": 18,//关联匹配申请的ID
                "requirement_specialist_id": 2,//关联需求的ID
                "title": "就你了",//订单标题
                "desc": "快点开始",//订单描述
                "user_id": 16,//所属用户
                "specialist_id": 18,//关联专家的user_id
                "total": "3000.00",//订单总额
                "num": 2,//付款期次
                "now_point": 1,//当前付款期次
                "status": "0",//状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 
                "specialist_source": 3,//专家来源:1-系统推荐,2-需求方预约,3-专家主动申请
                "need_acceptance": "0",//0-无需验收,1-需要验收
                "need_invoice": "1",//0-无需发票,1-需要发票
                "createtime": 1705047552,
                "updatetime": 1705047552,
                "deletetime": null,
                "finishtime": 1705047552,//订单确认时效截止时间
                "confirm_day": 0,//订单确认时效天数
                "confirm": "0",//0-专家未确认,1-专家已确认
                "status_text": "待收款",
                "need_acceptance_text": "无需验收",
                "finishtime_text": "2024-01-12 16:19:12",
                "createtime_text": "2024-01-12 16:19:12",
                "updatetime_text": "2024-01-12 16:19:12",
                "confirm_text": "专家确认订单",
                "is_excp": 0,//是否异常订单:0-否,1-是
                "pay": [//付款信息
                    {
                        "id": 1,
                        "order_id": 5,
                        "idx": 1,
                        "desc": "预付款",
                        "begin": "2023年9月18日",
                        "end": "2023年10月18日",
                        "total": 1000,
                        "is_pay": "0",//付款状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 
                        "createtime": 1705047552,
                        "updatetime": 1705047552,
                        "deletetime": null,
                        "is_pay_text": "未付款",
                        "is_excp": 0,//是否异常订单:0-否,1-是
                    },
                    {
                        "id": 2,
                        "order_id": 5,
                        "idx": 2,
                        "desc": "尾款",
                        "begin": "2023年10月18日",
                        "end": "2023年11月18日",
                        "total": 2000,
                        "is_pay": "0",
                        "createtime": 1705047553,
                        "updatetime": 1705047553,
                        "deletetime": null,
                        "is_pay_text": "未付款",
                        "is_excp": 0,//是否异常订单:0-否,1-是
                    }
                ]
                
            }
        },
        "domain": "http://supply.test"
        })
     */
    public function detail(Request $request){

        $id = $this->request->post('id');
        $model = new RequirementModel();
        $userArchive = $model->with(['user' => function($query) {
            $query->field('username,nickname,mobile,id_no_name,avatar');
        }])->where('requirement.id', $id)
        ->order('id','desc')->find();

        if(!$userArchive){
            $this->error("获取失败",'');
        }
        $industry_ids = $userArchive->industry_ids;
        $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];

        $skill_ids = $userArchive->skill_ids;
        $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

        $area_ids = $userArchive->area_ids;
        $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];

        $userArchive['skill'] = TagModel::whereIn('id',$skill_arr)->select();
        $userArchive['industry'] = TagModel::whereIn('id',$industry_arr)->select();
        $userArchive['area'] = TagModel::whereIn('id',$area_arr)->select();
        $status_text = Db::name("requirement_specialist")->where(['requirement_id' => $id,'status' => '1'])->find();
//        var_dump($status_text);die;
//        if($status_text){
//            $userArchive['status_text'] = "已确认";
//        }

        $order_model = new OrderModel();
        $order_data = $order_model
        ->where('user_id|specialist_id',$this->auth->id)
        ->where('rid',$id)
        ->order('id', "desc")
        ->find();
        if($order_data){
            //有订单信息
            $order_id = $order_data->id;
            $order_data['pay'] = OrderPayModel::where('order_id',$order_id)->order('idx','asc')->select();
            // $order_data['log'] = OrderPayLogModel::where('order_id',$order_id)->order('id','desc')->select();
            // $order_data['excp'] = OrderExcpModel::where('order_id',$order_id)->order('id','desc')->select();
            
            //专家
            $join_info = RequirementSpecialistModel::alias('rs')
            ->join('user u','u.id = rs.user_id')
            ->join('specialist s','s.user_id = rs.user_id')
            ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
            ->field('rs.*,u.id_no_name,s.nickname,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm')
            // ->where('rs.type', '3')
            // ->where('rs.user_id',$this->auth->id)
            ->where('rs.id',$order_data->requirement_specialist_id)
            ->group('rs.id')
            ->order('rs.id','desc')
            ->find();
            if($this->auth->role_type == '1'){
                //需求方
                if($join_info['status'] == '0'){
                    $join_info['status_text'] = '待确认';
                }
                if($join_info['status'] == '1'){
                    $join_info['status_text'] = '已确认';
                }
                if($join_info['status'] == '2'){
                    $join_info['status_text'] = '待确认';
                }
            }else{
                //专家
                if($join_info['status'] == '0'){
                    $join_info['status_text'] = '待确认';
                }

                if($join_info['status'] == '1'){
                    $join_info['status_text'] = '已确认';
                }
            }

            $order_data['join_info'] = $join_info;
            $userArchive['order'] = $order_data;
        }else{
            //无订单信息
        }
        

        //判断是否为需求方
        if($userArchive['user_id'] == $this->auth->id){
            //返回平台推荐信息
            $userArchive['requirement_specialist_1'] = RequirementSpecialistModel::alias('rs')
            ->join('user u','u.id = rs.user_id')
            ->join('specialist s','s.user_id = rs.user_id')
            ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
            ->field('rs.*,u.id_no_name,s.nickname,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm,m.refuse_desc,m.failed_desc')
            ->where('rs.type', '1')
            ->where('rs.requirement_id',$id)
            ->group('rs.id')
            ->order('rs.id','desc')
            ->select();
            $userArchive['requirement_specialist_2'] = RequirementSpecialistModel::alias('rs')
            ->join('user u','u.id = rs.user_id')
            ->join('specialist s','s.user_id = rs.user_id')
            ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
            ->field('rs.*,u.id_no_name,s.nickname,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm,m.refuse_desc,m.failed_desc')
            ->where('rs.type', '2')
            ->where('rs.requirement_id',$id)
            ->group('rs.id')
            ->order('rs.id','desc')
            ->select();
            $userArchive['requirement_specialist_3'] = RequirementSpecialistModel::alias('rs')
            ->join('user u','u.id = rs.user_id')
            ->join('specialist s','s.user_id = rs.user_id')
            ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
            ->field('rs.*,u.id_no_name,s.nickname,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm,m.refuse_desc,m.failed_desc')
            ->where('rs.type', '3')
            ->where('rs.vertify_status',1)
            ->where('rs.requirement_id',$id)
            ->group('rs.id')
            ->order('rs.id','desc')
            ->select();
        }else{
            //专家
            $join = RequirementSpecialistModel::alias('rs')
            ->join('user u','u.id = rs.user_id')
            ->join('specialist s','s.user_id = rs.user_id')
            ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
            ->field('rs.*,u.id_no_name,s.nickname,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm')
            // ->where('rs.type', '3')
            ->where('rs.user_id',$this->auth->id)
            ->where('rs.requirement_id',$id)
            ->group('rs.id')
            ->order('rs.id','desc')
            ->find();
            $select_user =  RequirementSpecialistModel::alias('rs')
                ->join('user u','u.id = rs.user_id')
                ->where('rs.requirement_id',$id)
                ->where('rs.status','3')

                ->value('u.nickname');
            $userArchive['select_user'] = $select_user;
            $userArchive['join'] = $join;
            $join_text = '';
            $join_type = '';
            $item = $userArchive;
            // dump($join);die;
            if($join){
                //已参与的专家获取详情
                //状态:0-待确认参与,1-待审核,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效,7-审核未通过
                if($item->status === '1' || $item->status === '2'){
                    //需求匹配中
                    if($join->status === '0'){
                        $join_text = '待确认参与';
                        $join_type = '0';
                    }elseif($join->status === '1'){
                        if($join->vertify_status === 0){
                            $join_text = '待审核';
                            $join_type = '1';
                        }elseif($join->vertify_status === 1){
                            $join_text = '匹配中';
                            $join_type = '2';
                        }elseif($join->vertify_status === 2){
                            $join_text = '审核未通过';
                            $join_type = '7';
                        }
                        
                    }elseif($join->status === '2'){
                        //待需求方确认
                        $join_text = '匹配中';
                        $join_type = '2';
                    }elseif($join->status === '3'){
                        //需求方已确认
                        $join_text = '匹配中';
                        $join_type = '2';
                    }elseif($join->status === '4'){
                        //已拒绝 已失效 状态:0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；6-已失效
                        $join_text = '已拒绝';
                        $join_type = '4';
                    }elseif($join->status === '5'){
                        //已取消
                        $join_text = '已取消';
                        $join_type = '5';
                    }elseif($join->status === '6'){
                        $join_text = '已失效';
                        $join_type = '6';
                    }
                }elseif($item->status === '3'){
                    $join_text = '订单待确认';
                    $join_type = '3';
                    if($join->status === '4'){
                        //已拒绝
                        $join_text = '已拒绝';
                        $join_type = '4';
                    }elseif($join->status === '5'){
                        //已取消
                        $join_text = '已取消';
                        $join_type = '5';
                    }elseif($join->status === '6'){
                        $join_text = '已失效';
                        $join_type = '6';
                    }
                }elseif($item->status === '4'){
                    $join_text = '已匹配';
                    $join_type = '4';
                    if($join->status === '4'){
                        //已拒绝
                        $join_text = '已拒绝';
                        $join_type = '4';
                    }elseif($join->status === '5'){
                        //已取消
                        $join_text = '已取消';
                        $join_type = '5';
                    }elseif($join->status === '6'){
                        $join_text = '已失效';
                        $join_type = '6';
                    }
                }elseif($item->status === '5'){
                    $join_text = '已取消';
                    $join_type = '5';
                    if($join->status === '4'){
                        //已拒绝
                        $join_text = '已失效';
                        $join_type = '6';
                    }elseif($join->status === '5'){
                        //已取消
                        $join_text = '已取消';
                        $join_type = '5';
                    }elseif($join->status === '6'){
                        $join_text = '已失效';
                        $join_type = '7';
                    }
                }elseif($item->status === '6'){
                    $join_text = '已失效';
                    $join_type = '6';
                    if($join->status === '4'){
                        //已拒绝
                        $join_text = '已失效';
                        $join_type = '6';
                    }elseif($join->status === '5'){
                        //已取消
                        $join_text = '已取消';
                        $join_type = '5';
                    }elseif($join->status === '6'){
                        $join_text = '已失效';
                        $join_type = '7';
                    }
                }

                $userArchive['join_text'] = $join_text;
                $userArchive['join_type'] = $join_type;
            }else{
                //未参与的专家获取详情
            }
            
        }
        $this->success("操作成功",$userArchive);

    }
    /**
     * 获取订单专家信息
     *
     * @ApiMethod (POST)
     * @ApiSummary  (无token时返回假需求，有token时返回真实需求)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $requirement_id 需求id
     */
    public function specialist_info(){
        $requirement_id = $this->request->post('requirement_id','');
//        $specialist_id = $this->request->post('specialist_id','');
        $data = Db::name("requirement_specialist")
            ->alias("a")
            ->join("user b",'a.user_id = b.id','left')
            ->join("specialist s",'s.user_id = b.id','left')
            ->where(['a.id' => $requirement_id])
            ->field("a.type,s.nickname,a.desc,a.files")
            ->find();
//        echo Db::name("requirement_specialist")->getLastSql();die;
        $this->success("操作成功",$data);

    }



    /**
     * 个人中心(需求方)-我的需求
     *
     * @ApiMethod (POST)
     * @ApiSummary  (无token时返回假需求，有token时返回真实需求)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $status 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @param string $specialist_user_id     专家的user_id
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="需求列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1703175528",
        "data": {
            "total": 3, //总条数
            "rows": [ //当前页记录
            {
                "id": 3,
                "type": "1",
                "user_name": "小布丁",
                "user_type": "1",
                "title": "这是一个真需求",//类型:1=真实需求,2=虚假需求
                "sn": "9007655",//需求编号
                "content": "<p>萨达发大水发大水</p><p>s地方asf 安德森</p><p>阿萨法撒旦法</p>",
                "status": 1,//状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
                "begin": "一周内",
                "end": "一个月",
                "keywords_tags": "1,2,3",
                "files": "/supply/upload/20231201/aadfe428068ac2917e877181dc97a597.xlsx",
                "open_price_data": "0",
                "price": 100,
                "createtime": 1701763089,
                "updatetime": 1701763089,
                "publishtime": "2023-12-11 21:46:34",
                "deletetime": null,
                "user_id": 10,
                "num": 1,
                "avatar": "",
                "role_type": "1",
                "typedata": "2",
                "type_text": "Type 1",
                "user_type_text": "User_type 1",
                "status_text": "Status 1",
                "open_price_data_text": "Open_price_data 0",
                "createtime_text": "2023-12-05 15:58:09",
                "keywords_arr": [
                "1",
                "2",
                "3"
                ],
                "skill":[],//技能标签
                "industry":[],//行业标签
                "area":[],//地区标签,
                "requirement_specialist": {//关联的需求申请-匹配中的订单会返回，否则此字段为null
                    "id": 2,
                    "requirement_id": 18,//所属需求ID
                    "user_id": 18,//专家ID
                    "desc": "我很棒",//描述
                    "files": "",//文件：多个,分开
                    "status": "1",//关联状态:0-待确认 1-已确认 2-已拒绝 3-已取消
                    "createtime": 1704986225,
                    "updatetime": 1705045829,
                    "deletetime": null,
                    "type": "3",//发起方:1-平台推荐 2-需求方预约 3-专家申请
                    "reason": "弄错了",//拒绝/取消理由
                    "status_text": "待匹配",
                    "createtime_text": "2024-01-11 23:17:05",
                    "updatetime_text": "2024-01-12 15:50:29"
                },
            }
            ]
        }
        })
     */
    public function my_requirement()
    {
        $type = "1";
        
        $model = new RequirementModel();
        $model = $model;
        $limit = $this->request->post('limit',10);
        $keyword = $this->request->post('keyword','');
        $lowest_price = $this->request->post('lowest_price');
        $status = $this->request->post('status','');
        $specialist_user_id = $this->request->post('specialist_user_id','');
        $where = [];
        
        
        
        $industry_ids = $this->request->post('industry_ids','');
        $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];

        $skill_ids = $this->request->post('skill_ids','');
        $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

        $area_ids = $this->request->post('area_ids','');
        $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];

        $tag_arr = array_merge($industry_arr,$skill_arr,$area_arr);

        $model = $model->alias('s')->field('s.*,u.avatar,u.role_type,u.typedata')->where('s.type',$type)->where('s.user_id',$this->auth->id);
        // $model = $model->hidden(['s.createtime','s.updatetime','s.id_no_front_image','s.id_no_backend_image','s.id_no','s.status']);
        if(count($tag_arr)>0){
            $model = $model->join('tag_requirement ts','s.id = ts.requirement_id')
            ->where('ts.tag_id','in', $tag_arr);
        }
        if ($keyword) {
            $model = $model->where('s.title|u.nickname', 'like', "%$keyword%");
        }
        if($lowest_price){
            $model = $model->where('s.price','=', $lowest_price);
        }
        if($status!=''){
            $model = $model->where('s.status',$status);
        }
        if($specialist_user_id!=''){
            $subQuery = RequirementSpecialistModel::where('user_id','=',$specialist_user_id)->column('requirement_id');
            $model = $model->whereNotIn('s.id',$subQuery);
        }
        // dump($where);die;
        $list = $model->join('user u','s.user_id = u.id','left')->where($where)
        ->order('s.id', "desc")
        ->group('s.id')
        
        ->paginate($limit)->each(function($item, $key){
            
            $industry_ids = $item->industry_ids;
            $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];

            $skill_ids = $item->skill_ids;
            $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

            $area_ids = $item->area_ids;
            $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];

            $item['skill'] = TagModel::whereIn('id',$skill_arr)->select();
            $item['industry'] = TagModel::whereIn('id',$industry_arr)->select();
            $item['area'] = TagModel::whereIn('id',$area_arr)->select();

            $requirement_specialist = null;
            if($item->status === 2){
            //     //正在匹配中
                $requirement_specialist = 
                RequirementSpecialistModel::alias('rs')
                ->join('user u','u.id = rs.user_id')
                ->field('rs.*,u.nickname,u.id_no_name')
                ->where('rs.status', '1')
                ->where('rs.requirement_id',$item->id)
                ->group('rs.id')
                ->order('rs.id','desc')
                ->find();

            }
            $item['requirement_specialist'] = $requirement_specialist;
            return $item;
        });

        $result = ['total' => $list->total(), 'rows' => $list->items()];
        $this->success('', $result);
    }


    /**
     * 个人中心(需求方)-取消发布
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求ID
     * @param string $reason 取消原因
     */
    public function cancel(){

        $id = $this->request->post('id');
        $reason = $this->request->post('reason','');
//        if($reason == ''){
//            $this->error("请填写取消原因");
//        }
        $model = new RequirementModel();
        $requirement = $model->where('id', $id)->where('user_id',$this->auth->id)->order('id','desc')->find();
        
        if(!$requirement){
            $this->error("需求不存在",'');
        }
        // 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        if($requirement->status === '5'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '6'){
            $this->error("该需求已失效");
        }
        if(($requirement->status === '4')&&(intval($id) !== 28)){
            $this->error("已匹配成功的需求无法取消");
        }
        if($requirement->status === '3'){
            $this->error("订单待确认的需求无法取消");
        }

        $requirement->status = '5';
        $requirement->cancel_reason = $reason;

        Db::startTrans();
        try {
            $requirement->save();
            RequirementSpecialistModel::where('requirement_id',$id)
            ->whereIn('status',['0','1'])->update([
                'status' => '3',
                'reason' => "该需求已取消发布"
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }

    /**
     * 个人中心(需求方)-确认专家申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求参与ID
     */
    public function confirm(){

        $id = $this->request->post('id');// 需求参与ID
        if(!$id){
            $this->error("需求参与ID不能为空");
        }
        $RequirementSpecialist = RequirementSpecialistModel::where('id',$id)
        ->where('status','2')->find();
        if(!$RequirementSpecialist){
            $this->error("操作失败：无法确认");
        }

        $model = new RequirementModel();
        $requirement = $model->where('id', $RequirementSpecialist->requirement_id)->where('user_id',$this->auth->id)->order('id','desc')->find();
        if(!$requirement){
            
            $this->error("需求不存在",'');
        }
        // 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        if($requirement->status === '5'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '6'){
            $this->error("该需求已失效");
        }
        if($requirement->status == '0'){
            $this->error("该需求还未审核");
        }
        if($requirement->status == '4'){
            $this->error("订单已匹配");
        }
        if($requirement->status == '3'){
            $this->error("订单待确认中");
        }
        if($requirement->status == '2'){
            // $this->error("您已有正在匹配中的申请，请先处理~");
        }

        // $requirement->status = '2';
        $RequirementSpecialist->status = '3';
        Db::startTrans();
        try {
            $RequirementSpecialist->save();
            // $has = RequirementSpecialistModel::where('requirement_id',$RequirementSpecialist->requirement_id)
            // ->where('status','0')->count();
            $requirement->status = '2';
            $requirement->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }

    /**
     * 个人中心(需求方)-拒绝专家申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求参与ID
     * @param string $reason 拒绝说明
     */
    public function refuse(){

        $id = $this->request->post('id');// 需求参与ID
        if(!$id){
            $this->error("需求参与ID不能为空");
        }
        $reason = $this->request->post('reason','');// 拒绝说明
        if(!$reason){
            $this->error("拒绝说明不能为空");
        }
        $RequirementSpecialist = RequirementSpecialistModel::where('id',$id)
        // ->where('status','0')->where('type','3')
        ->find();
        if(!$RequirementSpecialist){
            $this->error("操作失败：无法确认");
        }
        $model = new RequirementModel();
        $requirement = $model->where('id', $RequirementSpecialist->requirement_id)->where('user_id',$this->auth->id)->order('id','desc')->find();
        if(!$requirement){
            
            $this->error("需求不存在",'');
        }
        // 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        if($requirement->status === '5'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '6'){
            $this->error("该需求已失效");
        }
        if($requirement->status == '0'){
            $this->error("该需求还未审核");
        }
        if($requirement->status == '4'){
            $this->error("该需求已匹配");
        }
        if($requirement->status == '3'){
            $this->error("订单待确认中");
        }
        if($requirement->status != '2'){
            $this->error("只有匹配中的需求才能拒绝");
        }
        $RequirementSpecialist->reason = $reason;
        $RequirementSpecialist->status = '4';
        Db::startTrans();
        try {
            $RequirementSpecialist->save();
            $has = RequirementSpecialistModel::where('requirement_id',$RequirementSpecialist->requirement_id)
            ->whereIn('status',['0','1','2','3'])->count();
            if($has>0){
                //如果还有匹配中的订单，订单就是匹配中
                $requirement->status = '2';
            }else{
                //如果没有匹配中的订单，订单就是待匹配
                $requirement->status = '1';







            }
            
            $requirement->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }

    /**
     * 个人中心(需求方)-申请沟通
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求参与ID
     * @param string $desc 申请说明
     */
    public function supply_meeting(){

        $id = $this->request->post('id');// 需求参与ID
        if(!$id){
            $this->error("需求参与ID不能为空");
        }
        $desc = $this->request->post('desc','');// 拒绝说明
        if(!$desc){
            $this->error("申请说明不能为空");
        }
        $RequirementSpecialist = RequirementSpecialistModel::where('id',$id)->find();
        if(!$RequirementSpecialist){
            $this->error("操作失败：双方暂无需求关联");
        }
        if($RequirementSpecialist->status !== '3'){
            $this->error("操作失败：只有确认关联的需求才能沟通");
        }
        $model = new RequirementModel();
        $requirement = $model->where('id', $RequirementSpecialist->requirement_id)->where('user_id',$this->auth->id)->order('id','desc')->find();
        if(!$requirement){
            
            $this->error("需求不存在",'');
        }
        // 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        if($requirement->status === '5'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '6'){
            $this->error("该需求已失效");
        }
        if($requirement->status == '0'){
            $this->error("该需求还未审核");
        }
        if($requirement->status == '1'){
            $this->error("该需求还在等待匹配中");
        }
        $has = MeetingModel::where('requirement_id',$RequirementSpecialist->requirement_id)->where('specialist_user_id',$RequirementSpecialist->user_id)->count();
        if($has>0){
            $this->error("已申请沟通过，请勿重复提交");
        }
        $meetingModel = new MeetingModel();
        $meetingModel->requirement_id = $RequirementSpecialist->requirement_id;
        $meetingModel->requirement_user_id = $requirement->user_id;
        $meetingModel->specialist_user_id = $RequirementSpecialist->user_id;
        $meetingModel->type = '1';
        $meetingModel->desc = $desc;
        $meetingModel->status = '0';
        $meetingModel->requirenment_specialist_id = $id;

        Db::startTrans();
        try {
            $meetingModel->save();
            setPostMessage(3,0,'id为'.$this->auth->id.'的用户申请沟通(需求：'.$requirement->title.')','/kfSypMgbqw.php/requirement/detail/ids/'.$requirement->id);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }

    /**
     * 个人中心-获取沟通信息
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $meeting_id   沟通信息ID
     * @ApiReturn   ({
        "code": 1,
        "msg": "操作成功",
        "time": "1706234265",
        "data": {
            "id": 1,
            "requirement_id": 18,
            "requirement_user_id": 16,//需求方id
            "specialist_user_id": 18,//专家id
            "type": "1",
            "desc": "沟通一下麻烦",//需求方的申请描述
            "status": "1",//平台审核状态:0=待处理,1=已处理,2-不通过
            "createtime": 1705045885,
            "updatetime": 1706150641,
            "deletetime": null,
            "requirenment_specialist_id": 2,//关联的匹配ID
            "info": null,//平台反馈的会议信息
            "confirm": 0,//专家确认状态:0-未确认,1-已确认，2-已拒绝
            "type_text": "需求方",
            "status_text": "已处理"
        },
        "domain": "http://supply.test"
        })
     */
    public function meeting_detail(){

        $id = $this->request->post('meeting_id');// 需求参与ID
        if(!$id){
            $this->error("ID不能为空");
        }
        

        $meeting = MeetingModel::where('id',$id)->find();
        
        $this->success("操作成功",$meeting);

    }

    /**
     * 个人中心(专家)-接受沟通申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $meeting_id   沟通信息ID
     */
    public function confirm_meeting(){
        // dump($this->auth->id);die;
        $id = $this->request->post('meeting_id');// 需求参与ID
        if(!$id){
            $this->error("ID不能为空");
        }
        $meeting = MeetingModel::where('id',$id)->where('specialist_user_id',$this->auth->id)->find();
        // dump($meeting);die;
        if(!$meeting){
            $this->error("该沟通不存在");
        }
        if($meeting->status == '0'){
            $this->error("该沟通平台还未审核，无法操作");
        }

        if($meeting->status == '2'){
            $this->error("该沟通平台审核未通过，无法操作");
        }

        if($meeting->confirm  === 1){
            $this->error("您已确认，请勿重复操作");
        }
        if($meeting->confirm  === 2){
            $this->error("您已拒绝，请勿重复操作");
        }
        $meeting->confirm = 1;
        $meeting->save();
        $requirement = Db::name("requirement")->where(['id' => $meeting->requirement_id])->find();

        setPostMessage(2,$requirement['user_id'],'您的沟通申请专家已接受，点击查看详情','/myNeed/detail?status='.$requirement['status'].'&id='.$requirement['id']);

        $this->success("操作成功",$meeting);

    }

    /**
     * 个人中心(专家)-拒绝沟通申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $meeting_id   沟通信息ID
     */
    public function confuse_meeting(){

        
        $id = $this->request->post('meeting_id');// 需求参与ID
        $refuse_desc = $this->request->post('refuse_desc');
        if(!$id){
            $this->error("ID不能为空");
        }
        $meeting = MeetingModel::where('id',$id)->where('specialist_user_id',$this->auth->id)->find();
        if(!$meeting){
            $this->error("该沟通不存在");
        }
        if($meeting->status == '0'){
            $this->error("该沟通平台还未审核，无法操作");
        }

        if($meeting->status == '2'){
            $this->error("该沟通平台审核未通过，无法操作");
        }

        if($meeting->confirm  === 1){
            $this->error("您已确认，请勿重复操作");
        }
        if($meeting->confirm  === 2){
            $this->error("您已拒绝，请勿重复操作");
        }
        $meeting->confirm = 2;
        $meeting->refuse_desc = $refuse_desc;
        $meeting->save();

        $requirement = Db::name("requirement")->where(['id' => $meeting->requirement_id])->find();

        setPostMessage(2,$requirement['user_id'],'您的沟通申请专家已婉拒，点击查看详情','/myNeed/detail?status='.$requirement['status'].'&id='.$requirement['id']);


        $this->success("操作成功",$meeting);

    }

    /**
     * 个人中心(需求方)-创建订单
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求参与ID
     * @param string $title   订单标题
     * @param string $desc 订单说明
     * @param string $need_acceptance 验收:0-不需要验收,1-需要验收
     * @param string $need_invoice 开票:0-不需要开票,1-需要开票
     * @param string $confirm_day 需求确认时效:天数
     * @param string $pay_arr 付款信息，例：[{"idx":1,"desc":"预付款","begin":"2023年9月18日","end":"2023年10月18日","total":1000},{"idx":2,"desc":"尾款","begin":"2023年10月18日","end":"2023年11月18日","total":2000}]
     * @param string $invoice_id 发票ID:新建发票传0,选择已有发票传发票ID
     * @param string $invoice_type 发票种类:1=普通发票,2=专用发票
     * @param string $invoice_title 发票抬头
     * @param string $invoice_company_number 发票税号
     * @param string $invoice_company_addr 发票详细地址
     * @param string $invoice_company_tel 发票联系电话
     * @param string $invoice_recepient_email 发票接收邮箱
     * @param string $invoice_recepient_addr 发票收票地址
     * @param string $invoice_recepient_tel 发票收票电话
     * @param string $invoice_recepient_name 发票收票人
     * @param string $invoice_bank_name 专票:开户行
     * @param string $invoice_bank_account 专票:开户账号
     */
    public function createOrder(){

        $id = $this->request->post('id');// 需求参与ID
        $order_id = $this->request->post('order_id');// 需求参与ID
        if(!empty($order_id)){
            $id = Db::name("order")->where(['id' => $order_id])->value("requirement_specialist_id");
        }
        if(!$id){
            $this->error("需求参与ID不能为空");
        }
        $desc = $this->request->post('desc','');// 订单说明
        if(!$desc){
            $this->error("申请说明不能为空");
        }
        $title = $this->request->post('title','');// 订单标题
        if(!$title){
            $this->error("申请说明不能为空");
        }

        $need_acceptance = $this->request->post('need_acceptance','0');// 验收:0-不需要验收,1-需要
        $need_invoice = $this->request->post('need_invoice','0');
        $confirm_day = $this->request->post('confirm_day','0');

        $pay_arr = $this->request->post('pay_arr','[]','strip_tags');
        $pay_arr = json_decode($pay_arr,true);

        
        $invoice_id = $this->request->post('invoice_id',0);// 发票ID
        $invoice_type = $this->request->post('invoice_type','');// 发票种类:1=普通发票,2=专用发票
        $invoice_title = $this->request->post('invoice_title','');// 发票抬头
        $invoice_company_number = $this->request->post('invoice_company_number','');// 发票税号
        $invoice_company_addr = $this->request->post('invoice_company_addr','');// 发票详细地址
        $invoice_company_tel = $this->request->post('invoice_company_tel','');// 发票电话
        $invoice_recepient_email = $this->request->post('invoice_recepient_email','');// 发票接收邮箱
        $invoice_recepient_name = $this->request->post('invoice_recepient_name','');// 发票接收人
        $invoice_recepient_addr = $this->request->post('invoice_recepient_addr','');// 发票接收地址
        $invoice_recepient_tel = $this->request->post('invoice_recepient_tel','');// 发票接收电话
        $invoice_bank_name = $this->request->post('invoice_bank_name','');// 专票:开户行
        $invoice_bank_account = $this->request->post('invoice_bank_account','');// 专票:银行账户
        
        $RequirementSpecialist = RequirementSpecialistModel::where('id',$id)
        ->whereIn('status',['3'])->find();
        if(!$RequirementSpecialist){
            $this->error("操作失败：双方暂无需求关联");
        }
        $model = new RequirementModel();
        $requirement = $model->where('id', $RequirementSpecialist->requirement_id)->where('user_id',$this->auth->id)->order('id','desc')->find();
        if(!$requirement){
            $this->error("需求不存在",'');
        }
        if($requirement->status === '4'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '5'){
            $this->error("该需求已失效");
        }
        if($requirement->status == '0'){
            $this->error("该需求还未审核");
        }
        if($requirement->status == '1'){
            $this->error("该需求匹配中");
        }
        if(!empty($order_id)){
            $orderModel = OrderModel::where(['id' => $order_id])->find();
            Db::name("order_pay")->where(['order_id' => $order_id])->delete();
        }else{
            $orderModel = new OrderModel();
        }
        $invoice = new InvoiceLogModel();
        $orderPayModel = new OrderPayModel();
        $orderPayDetailModel = new OrderPayDetailModel();
        $orderModel->rid = $RequirementSpecialist->requirement_id;
        $orderModel->user_id = $requirement->user_id;
        $orderModel->specialist_id = $RequirementSpecialist->user_id;
        $orderModel->requirement_specialist_id = $id;
        $orderModel->title = $title;
        $orderModel->desc = $desc;
        $orderModel->need_acceptance = $need_acceptance;
        $orderModel->need_invoice = $need_invoice;
        $orderModel->confirm_day = $confirm_day;
        $now = time();
        $finishtime = $now + $confirm_day * 24 * 3600;
        $orderModel->finishtime = $finishtime;
        $orderModel->status = '0';
        $orderModel->confirm = '0';
        $orderModel->specialist_source = $RequirementSpecialist->type;
        $orderModel->now_point = 1;
        //生成sn订单号
        $orderModel->sn = $orderModel->createOrderNo();
        $num = count($pay_arr);
        $orderModel->num = $num;
        $total = 0;
        $total_first = 0;//第一笔款
        foreach ($pay_arr as $key => $value) {
            $total += $value['total'];
            if($key == 0){
                $total_first = $value['total'];
            }
        }
        $orderModel->total = $total;

        //更改需求状态
        $requirement->status = '3';
        $requirement->num = $num;

        if($need_invoice == '1'){

                //新建发票
                if(!$invoice_type || !$invoice_title || !$invoice_company_number || !$invoice_company_addr || !$invoice_company_tel || !$invoice_recepient_email || !$invoice_recepient_name || !$invoice_recepient_addr || !$invoice_recepient_tel)
                {
                    $this->error("发票信息不能为空");
                }
                if($invoice_type == '2' && (!$invoice_bank_name || !$invoice_bank_account)){
                    $this->error("专票信息不能为空");
                }
                $invoice->user_id = $this->auth->id;
                $invoice->type = $invoice_type;
                $invoice->title = $invoice_title;
                $invoice->company_number = $invoice_company_number;
                $invoice->company_addr = $invoice_company_addr;
                $invoice->company_tel = $invoice_company_tel;
                $invoice->recepient_email = $invoice_recepient_email;
                $invoice->recepient_name = $invoice_recepient_name;
                $invoice->recepient_addr = $invoice_recepient_addr;
                $invoice->recepient_tel = $invoice_recepient_tel;
                $invoice->bank_name = $invoice_bank_name;
                $invoice->bank_account = $invoice_bank_account;
                $invoice->sn = $orderModel->sn;
                $rate_system = ConfigModel::where('name','rate_system')->value('value');
                $invoice->rate = 0;

        }

        Db::startTrans();
        try {
            //更该需求状态
            $requirement->save();
            //todo:更改参与表的状态，将其他人的状态改为已失效?
            
            if($need_invoice == '1'){
                $invoice->save();
                $orderModel->invoice_id = $invoice->id;
            }
            $orderModel->save();
            Db::name("requirement_specialist")->where(['requirement_id' => $requirement->id])->where("id","<>",$id)->where('status','in',[0,1,2,3])->update(['status' => '6']);
            if($need_invoice == '1'){
                $invoice->order_id = $orderModel->id;
                $invoice->save();
            }

            if($num>0){
                foreach($pay_arr as &$item){
                    $item['order_id'] = $orderModel->id;
                }
                $orderPayModel->saveAll($pay_arr);
            }
            //orderPayDetailModel
            setPostMessage(2,$orderModel->specialist_id,'您有一条新的确认订单，点击查看详情','/myProject/detail?status='.$orderModel->status.'&id='.$requirement->id);
            //插入明细状态
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }

    /**
     * 个人中心(专家)-我的需求
     *
     * @ApiMethod (POST)
     * @ApiSummary  (
        我的待定项目
        <br>返回参数解析
        <br>join_text  参与状态描述
        <br>join_type  参与状态:0-待确认参与,1-待审核,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效,7-审核未通过
        <br>join       参与信息
     )
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $type 状态:0-待确认参与,1-待审核,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="需求列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1705055467",
        "data": {
            "total": 1,
            "rows": [
            {
                "id": 19,
                "type": "1",
                "user_name": "13026381111",
                "user_type": "1",
                "title": "标题",//需求标题
                "sn": null,
                "content": "需求描述",
                "status": 1,//需求状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
                "begin": "启动时间",
                "end": "完成时间",
                "keywords_tags": "关键词",
                "files": "",
                "open_price_data": "1",
                "price": 100,
                "createtime": 1704989557,
                "updatetime": 1704989557,
                "publishtime": null,
                "deletetime": null,
                "user_id": 19,
                "num": 1,
                "skill_ids": "14",
                "area_ids": "13",
                "industry_ids": "2",
                "cancel_reason": null,
                "avatar": "",
                "role_type": "1",
                "typedata": "1",
                "requirement_specialist_id": 3,
                "join_status": "0",//参与状态:0-待确认 1-已确认 2-已拒绝 3-已取消
                "reason": null,//取消、拒绝原因
                "skill": [],//技能标签
                "industry": [],//行业标签
                "area": [],//区域标签
                "type_text": "Type 1",
                "user_type_text": "User_type 1",
                "status_text": "待匹配",//需求状态文字描述
                "open_price_data_text": "Open_price_data 1",
                "createtime_text": "2024-01-12 00:12:37",
                "keywords_arr": [
                "关键词"
                ]
            }
            ]
        },
        "domain": "http://supply.test"
        })
     */
    public function my_join_requirement()
    {
        // $type = "1";
        $type = $this->request->post('type','');
        $model = new RequirementModel();
        $model = $model;
        $limit = $this->request->post('limit',10);
//        $limit = 100;

        $keyword = $this->request->post('keyword','');
        $lowest_price = $this->request->post('lowest_price');
        $status = $this->request->post('type','');
        $where = [];
        
        
        
        $industry_ids = $this->request->post('industry_ids','');
        $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];

        $skill_ids = $this->request->post('skill_ids','');
        $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

        $area_ids = $this->request->post('area_ids','');
        $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];

        $tag_arr = array_merge($industry_arr,$skill_arr,$area_arr);

        $model = $model->alias('s')->field('s.*,u.avatar,u.role_type,u.typedata,rs.id as requirement_specialist_id,rs.status as join_status,rs.reason')
        ->join('requirement_specialist rs','rs.requirement_id = s.id')
        ->where('s.type','1');
        //->where('rs.user_id',$this->auth->id);

//        $model = $model->where('s.status', '>=', '3')
//            ->where('rs.vertify_status', '1');

        // $model = $model->hidden(['s.createtime','s.updatetime','s.id_no_front_image','s.id_no_backend_image','s.id_no','s.status']);
        if(count($tag_arr)>0){
            $model = $model->join('tag_requirement ts','s.id = ts.requirement_id')
            ->where('ts.tag_id','in', $tag_arr);
        }
        if ($keyword) {
            $model = $model->where('s.name|s.nickname', 'like', "%$keyword%");
        }
        if($lowest_price){
            $model = $model->where('s.price','=', $lowest_price);
        }
        if($status===''){
            $model = $model->where('rs.user_id',$this->auth->id);
        }elseif(intval($status)===0){
            //待确认参与
            $model = $model->where('s.status','>', 0);
            $model = $model->where('rs.status', '0')->where('rs.user_id',$this->auth->id);
        }elseif(intval($status)===1){
            //待审核(已申请，并且平台未审核)
            $model = $model->where('rs.status', '1')->where('rs.vertify_status',0)->where('rs.user_id',$this->auth->id);
        }elseif(intval($status)===2){
            //匹配中
            $model = $model->whereIn('s.status', ['1','2']);
            $model = $model->whereIn('rs.status', ['2','3'])->where('rs.vertify_status',1)->where('rs.user_id',$this->auth->id);
            
        }elseif(intval($status)===3){
            //订单待确认
            $model = $model->join('order o','o.requirement_specialist_id = rs.id');
            $model = $model->where('s.status', '3');
            $model = $model->where('rs.status', '3')->where('rs.vertify_status',1)->where('rs.user_id',$this->auth->id);
            
        }elseif(intval($status)===4){
            //已匹配
            $model = $model->join('order o','o.requirement_specialist_id = rs.id');
            $model = $model->where('s.status', '4');
            $model = $model->where('rs.status', '3')->where('rs.vertify_status',1)->where('rs.user_id',$this->auth->id);
        }elseif(intval($status)===5){
            //已取消
            $model = $model->where('rs.status', '5')->where('rs.user_id',$this->auth->id);
        }elseif(intval($status)===6){
            //已失效-已选择其他专家匹配
            // $model = $model->join('requirement_specialist rs','rs.requirement_id = s.id');
            //$model = $model->whereIn('s.status', ['2','3','4']);
            //$model = $model->where('rs.status', '4')->where('rs.user_id',$this->auth->id);
            $model = $model->where('rs.status', '6')->where('rs.user_id',$this->auth->id);
            
        }
        $list = $model->join('user u','s.user_id = u.id','left')
            ->field("")
            ->where($where)
        ->order('rs.id', "desc")
        ->group('s.id')
        
        ->paginate($limit)->each(function($item, $key){
            $item->user_id?$item['user'] = User::get($item->user_id):'';
            $industry_ids = $item->industry_ids;
            $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];

            $skill_ids = $item->skill_ids;
            $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

            $area_ids = $item->area_ids;
            $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];

            $item['skill'] = TagModel::whereIn('id',$skill_arr)->select();
            $item['industry'] = TagModel::whereIn('id',$industry_arr)->select();
            $item['area'] = TagModel::whereIn('id',$area_arr)->select();
            $join = RequirementSpecialistModel::alias('rs')
            ->join('user u','u.id = rs.user_id')
            ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
            ->field('rs.*,u.id_no_name,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm')
            // ->where('rs.type', '3')
            ->where('rs.user_id',$this->auth->id)
            ->where('rs.requirement_id',$item->id)
            ->group('rs.id')
            ->order('rs.id','desc')
            ->find();

            $item['join'] = $join;
            $join_text = '';
            $join_type = '';
            //状态:0-待确认参与,1-待审核,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效,7-审核未通过
            if($item->status === '1' || $item->status === '2'){

                //需求匹配中
                if($join['status'] === '0'){
                    $join_text = '待确认参与';
                    $join_type = '0';
                }elseif($join['status'] === '1'){
                    if($join['vertify_status'] === 0){
                        $join_text = '待审核';
                        $join_type = '1';
                    }elseif($join['vertify_status'] === 1){
                        $join_text = '匹配中';
                        $join_type = '2';
                    }elseif($join['vertify_status'] === 2){
                        $join_text = '审核未通过';
                        $join_type = '7';
                    }
                    
                }elseif($join['status'] === '2'){
                    //待需求方确认
                    $join_text = '匹配中';
                    $join_type = '2';
                }elseif($join['status'] === '3'){
                    //需求方已确认
                    $join_text = '匹配中';
                    $join_type = '2';
                }elseif($join['status'] === '4'){
                    //已拒绝  已失效
                    $join_text = '';
                    $join_type = '6';
                }elseif($join['status'] === '5'){
                    //已取消
                    $join_text = '已取消';
                    $join_type = '5';
                }elseif($join['status'] === '6'){
                    $join_text = '已失效';
                    $join_type = '6';
                }
            }elseif($item->status === '3'){
                $join_text = '订单待确认';
                $join_type = '3';
                if($join['status'] === '4'){
                    //已拒绝
                    $join_text = '已失效';
                    $join_type = '6';
                }elseif($join['status'] === '5'){
                    //已取消
                    $join_text = '已取消';
                    $join_type = '5';
                }elseif($join['status'] === '6'){
                    $join_text = '已失效';
                    $join_type = '6';
                }
            }elseif($item->status === '4'){
                $join_text = '已匹配';
                $join_type = '4';
                if($join['status'] === '4'){
                    //已拒绝
                    $join_text = '已失效';
                    $join_type = '6';
                }elseif($join['status'] === '5'){
                    //已取消
                    $join_text = '已取消';
                    $join_type = '5';
                }elseif($join['status'] === '6'){
                    $join_text = '已失效';
                    $join_type = '6';
                }
            }elseif($item->status === '5'){
                $join_text = '已取消';
                $join_type = '5';
                if($join['status'] === '4'){
                    //已拒绝
                    $join_text = '已失效';
                    $join_type = '6';
                }elseif($join['status'] === '5'){
                    //已取消
                    $join_text = '已取消';
                    $join_type = '5';
                }elseif($join['status'] === '6'){
                    $join_text = '已失效';
                    $join_type = '7';
                }
            }elseif($item->status === '6'){
                $join_text = '已失效';
                $join_type = '6';
                if($$join['status'] === '4'){
                    //已拒绝
                    $join_text = '已失效';
                    $join_type = '6';
                }elseif($join['status'] === '5'){
                    //已取消
                    $join_text = '已取消';
                    $join_type = '5';
                }elseif($join['status'] === '6'){
                    $join_text = '已失效';
                    $join_type = '7';
                }
            }
            
            $item['join_text'] = $join_text;
            $item['join_type'] = $join_type;
            return $item;
        });


        $result = ['total' => $list->total(), 'rows' => $list->items()];
        $this->success('', $result);
    }

    /**
     * 个人中心(专家)-确认参与
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求参与ID:(列表中的requirement_specialist_id)
     */
    public function join_confirm(){

        $id = $this->request->post('id');// 需求参与ID
        if(!$id){
            $this->error("需求参与ID不能为空");
        }
        $RequirementSpecialist = RequirementSpecialistModel::where('id',$id)
        ->where('user_id',$this->auth->id)
        ->where('status','0')->whereIn('type',['1','2'])->find();
        if(!$RequirementSpecialist){
            $this->error("操作失败：无法确认");
        }

        $model = new RequirementModel();
        $requirement = $model->where('id', $RequirementSpecialist->requirement_id)->order('id','desc')->find();
        if(!$requirement){
            
            $this->error("需求不存在",'');
        }
        // 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        if($requirement->status === '5'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '6'){
            $this->error("该需求已失效");
        }
        if($requirement->status == '0'){
            $this->error("该需求还未审核");
        }
        if($requirement->status == '4'){
            $this->error("订单已匹配");
        }
        if($requirement->status == '3'){
            $this->error("订单待确认中");
        }
        if($requirement->status == '2'){
            $this->error("需求匹配中,请稍后再试~");
        }

        // $requirement->status = '2';
        $RequirementSpecialist->status = '1';
        Db::startTrans();
        try {
            $RequirementSpecialist->save();
            // $has = RequirementSpecialistModel::where('requirement_id',$RequirementSpecialist->requirement_id)
            // ->where('status','0')->count();
            $requirement->status = '2';
            $requirement->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }



    /**
     * 个人中心(专家)-提交参与申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求ID
     * @param string $desc 申请说明
     * @param string $files 附件({url:/supply/upload/20231201/a.xlsx,name:xx.xlsx})
     */
    public function expertApply(){
        $id = $this->request->post('id');
        $desc = $this->request->post('desc','');
        $files = htmlspecialchars_decode($this->request->post('files',''));
        //判断用户是否为认证专家

        if($this->auth->role_type === '1'){
            // 专家 数据隔离
            $this->error("只有专家才能申请");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证专家才能申请");
        }
        $requirement = RequirementModel::where('id',$id)->find();
        //判断是否有审核中和审核通过的参与
        $has = RequirementSpecialistModel::where('requirement_id',$id)->where('user_id',$this->auth->id)->whereIn('vertify_status',[0,1])->order('id','desc')->find();
        if($has){

            $hid = $has->id;
            //有申请
            if($has->status === '0'){
                //待确认参与-
                $has->status = '1';
                $has->desc = $desc;
                $has->files = $files;
                Db::startTrans();
                try {
                    $has->save();
                    $has = RequirementSpecialistModel::where('requirement_id',$id)
                    ->whereIn('status',['0','1','2','3'])->count();
                    if($has>0){
                        //如果还有匹配中的订单，订单就是匹配中
                        $requirement->status = '2';
                        $requirement->save();
                    }else{
                        //如果没有匹配中的订单，订单就是待匹配
                        $requirement->status = '1';
                        $requirement->save();
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error("操作失败",$e->getMessage());
                }
                $this->success("操作成功",$hid);
            }else{
                $this->error("您已申请过该需求");
            }

        }

        $model = RequirementSpecialistModel::where('requirement_id',$id)->where('user_id',$this->auth->id)->find();
        if(!$model){
            $model = new RequirementSpecialistModel();
            $model->type = '3';
        }
        $model->requirement_id = $id;
        $model->user_id = $this->auth->id;
        $model->desc = $desc;
        $model->files = $files;
        $model->status = '1';//状态:0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；
        $model->vertify_status = 0;
        $model->reason = '';
        Db::startTrans();
        try {
            $model->save();
            $has = RequirementSpecialistModel::where('requirement_id',$id)
            ->whereIn('status',['0','1','2','3'])->count();
            if($has>0){
                //如果还有匹配中的订单，订单就是匹配中
                $requirement->status = '2';
                $requirement->save();
            }else{
                //如果没有匹配中的订单，订单就是待匹配
                $requirement->status = '1';
                $requirement->save();
            }
            setPostMessage(3,0,'id为'.$this->auth->id.'的用户进行申请加入需求：'.$requirement->title,'/kfSypMgbqw.php/requirement/detail/ids/'.$id.'?dialog=1');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$model->id);
    }

    /**
     * 个人中心(专家)-确认参与需求
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求ID
     * @param string $desc 申请说明
     * @param string $files 附件({url:/supply/upload/20231201/a.xlsx,name:xx.xlsx})
     */
    public function comfirm_join(){
        $id = $this->request->post('id');
        $desc = $this->request->post('desc','');
        $files = htmlspecialchars_decode($this->request->post('files',''));
        //判断用户是否为认证专家

        if($this->auth->role_type === '1'){
            // 专家 数据隔离
            $this->error("只有专家才能确认");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证专家才能确认");
        }
        $requirement = RequirementModel::where('id',$id)->find();
        //判断是否有审核中和审核通过的参与
        $has = RequirementSpecialistModel::where('requirement_id',$id)->where('user_id',$this->auth->id)->whereIn('vertify_status',[0,1])->order('id','desc')->find();
        if($has){
            $hid = $has->id;
            //有申请
            if($has->status === '0'){
                //待确认参与-
                $has->status = '1';
                $has->desc = $desc;
                $has->files = $files;
                Db::startTrans();
                try {
                    $has->save();
                    $has = RequirementSpecialistModel::where('requirement_id',$id)
                        ->whereIn('status',['0','1','2','3'])->count();
                    if($has>0){
                        //如果还有匹配中的订单，订单就是匹配中
                        $requirement->status = '2';
                        $requirement->save();
                    }else{
                        //如果没有匹配中的订单，订单就是待匹配
                        $requirement->status = '1';
                        $requirement->save();
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error("操作失败",$e->getMessage());
                }
                $this->success("操作成功",$hid);
            }else{
                $this->error("您已申请过该需求");
            }

        }



//        $model = new RequirementSpecialistModel();
        $model = RequirementSpecialistModel::where('requirement_id',$id)->where('user_id',$this->auth->id)->find();
        if(!$model){
            $model = new RequirementSpecialistModel();
            $model->type = '3';
        }

        $model->requirement_id = $id;
        $model->user_id = $this->auth->id;
        $model->desc = $desc;
        $model->files = $files;
        $model->status = '1';//状态:0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；
        $model->vertify_status = 0;
        $model->reason = '';
//        $model->type = '3';
        Db::startTrans();
        try {
            $model->save();
            $has = RequirementSpecialistModel::where('requirement_id',$id)
                ->whereIn('status',['0','1','2','3'])->count();
            if($has>0){
                //如果还有匹配中的订单，订单就是匹配中
                $requirement->status = '2';
                $requirement->save();
            }else{
                //如果没有匹配中的订单，订单就是待匹配
                $requirement->status = '1';
                $requirement->save();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        setPostMessage(3,$this->auth->id,'用户id为'.$this->auth->id.'的专家确认参与需求:'.$requirement->title,'/kfSypMgbqw.php/requirement/detail/ids/'.$requirement->id.'?dialog=1&dialog=1');
        $this->success("操作成功",$model->id);
    }

    /**
     * 个人中心(专家)-拒绝参与
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求参与ID:(列表中的requirement_specialist_id)
     * @param string $reason 拒绝说明
     */
    public function join_refuse(){
        $id = $this->request->post('id');// 需求参与ID
        if(!$id){
            $this->error("需求参与ID不能为空");
        }
        $reason = $this->request->post('reason','');// 拒绝说明
        if(!$reason){
            $this->error("拒绝说明不能为空");
        }
        $RequirementSpecialist = RequirementSpecialistModel::where('id',$id)->where('user_id',$this->auth->id)->find();

        if(!$RequirementSpecialist){
            $this->error("操作失败：无法确认");
        }

        $model = new RequirementModel();
        $requirement = $model->where('id', $RequirementSpecialist->requirement_id)->order('id','desc')->find();
        if(!$requirement){
            $this->error("需求不存在",'');
        }
        // 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        if($requirement->status === '5'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '6'){
            $this->error("该需求已失效");
        }
        if($requirement->status == '0'){
            $this->error("该需求还未审核");
        }
        if($requirement->status == '4'){
            $this->error("该需求已匹配");
        }
//        if($requirement->status == '3'){
//            $this->error("订单待确认中");
//        }
        
        $RequirementSpecialist->reason = $reason;
        $RequirementSpecialist->status = '4';
        Db::startTrans();
        try {
            $RequirementSpecialist->save();
            // $has = RequirementSpecialistModel::where('requirement_id',$RequirementSpecialist->requirement_id)
            // ->where('status','0')->count();
            $requirement->status = '2';
            $requirement->save();

            //其他参与需求的失效用户改为待匹配
            Db::name("requirement_specialist")->where(['requirement_id' => $RequirementSpecialist->requirement_id])
                ->where("status",6)
                ->where('user_id',"<>",$this->auth->id)
                ->update(['status' => '0']);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }

    /**
     * 个人中心(专家)-接收订单邀请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   需求参与ID:(列表中的requirement_specialist_id)
     */
    public function order_confirm(){

        $id = $this->request->post('id');// 需求参与ID
        if(!$id){
            $this->error("需求参与ID不能为空");
        }
        $RequirementSpecialist = RequirementSpecialistModel::where('id',$id)
        ->where('user_id',$this->auth->id)
        // ->where('status','0')->whereIn('type',['1','2'])
        ->find();
        if(!$RequirementSpecialist){
            $this->error("操作失败：无法确认");
        }

        $model = new RequirementModel();
        $requirement = $model->where('id', $RequirementSpecialist->requirement_id)->order('id','desc')->find();
        if(!$requirement){
            
            $this->error("需求不存在",'');
        }
        // 状态:0-待审核,1-待匹配,2-匹配中,3-订单待确认,4-已匹配,5-已取消,6-已失效
        if($requirement->status === '5'){
            $this->error("该需求已取消");
        }
        if($requirement->status === '6'){
            $this->error("该需求已失效");
        }
        if($requirement->status == '0'){
            $this->error("该需求还未审核");
        }
        if($requirement->status == '4'){
            $this->error("订单已匹配");
        }
        if($requirement->status != '3'){
            $this->error("订单状态不符合");
        }
        //找到对应的订单
        $orderModel = new OrderModel();
        $invoice = new InvoiceModel();
        $orderPayModel = new OrderPayModel();
        $order = $orderModel->where('requirement_specialist_id',$id)->where('specialist_id',$this->auth->id)->where('confirm','0')->find();
        if(!$order){
            $this->error("订单不存在");
        }
        //判断订单是否确认超时
        $now = time();
        if($now > $order->finishtime){
            $order->confirm = '2';
            Db::startTrans();
            try {
                $order->save();
                $RequirementSpecialist->save();
                $has = RequirementSpecialistModel::where('requirement_id',$RequirementSpecialist->requirement_id)
                ->where('status','0')->count();
                if($has>0){
                    //还有待确认的匹配
                    $requirement->status = '2';
                }else{
                    //没有待确认的匹配-回滚需求状态
                    $requirement->status = '1';
                }
                $requirement->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error("操作失败",$e->getMessage());
            }
            
            $this->error("需求确认超时");
        }
        

        $order->confirm = '1';
        $order->now_point = 1;
        $order->status = '0';
        $RequirementSpecialist->status = '3';
        $requirement->status = '4';

        //获取第一笔款
        $orderPay = $orderPayModel->where('order_id',$order->id)->where('idx',1)->find();
        if(!$orderPay){
            $this->error("找不到付款信息");
        }
        $total_first = $orderPay->total;
        //插入节点状态
        //插入大明细状态标题
        $orderPayDetailModel = new OrderPayDetailModel();
        $status_1 = "待收款";//平台
        $status_2 = "待支付";//用户
        $status_3 = "待收款";//专家
        $tip_1 = "待用户托管支付金额{$total_first}元";
        $tip_2 = "{$total_first}元";
        $tip_3 = "{$total_first}元";
        $orderPayDetailModel->order_id = $order->id;
        $orderPayDetailModel->idx = 1;
        $orderPayDetailModel->type = $orderPay->is_pay;
        $orderPayDetailModel->tip_1 = $tip_1;
        $orderPayDetailModel->tip_2 = $tip_2;
        $orderPayDetailModel->tip_3 = $tip_3;
        $orderPayDetailModel->status_1 = $status_1;
        $orderPayDetailModel->status_2 = $status_2;
        $orderPayDetailModel->status_3 = $status_3;
        
        Db::startTrans();
        try {
            setPostMessage(2,$requirement->user_id,'您有待支付订单，请及时处理','/myOrder/detail?status='.$order->status.'&id='.$order->id);
            $orderPayDetailModel->save();
            $RequirementSpecialist->save();
            $order->save();
            $requirement->status = '4';
            $requirement->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$requirement);

    }

    
}
