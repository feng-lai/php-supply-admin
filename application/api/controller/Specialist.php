<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Validate;
use app\common\model\Specialist as SpecialistModel;
use app\common\model\SpecialistAuth as SpecialistAuthModel;
use think\Db;
use app\common\model\TagSpecialist as TagSpecialistModel;
use app\common\model\UserArchive;
use app\common\model\Tag as TagModel;
use app\common\model\SpecialistFav as SpecialistFavModel;
use app\common\model\OrderComment as OrderCommentModel;
use app\common\model\Order as OrderModel;
/**
 * 专家接口
 */
class Specialist extends Api
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
     * 专家列表
     *
     * @ApiMethod (POST)
     * @ApiSummary  (测试描述信息)
     * @ApiHeaders  (name=Host, type=string, required=true, description="mgtoffice.qianqiance.com")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $keyword 关键词
     * @param string $industry_ids     行业标签，例：1,2,3
     * @param string $skill_ids     技能标签，例：1,2,3
     * @param string $area_ids     区域标签，例：1,2,3
     * @param string $lowest_price     报价起点
     * @param string $order_num 订单量，例：asc 正序 desc 倒叙
     * @param string $evaluate 好评，例：asc 正序 desc 倒叙
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="专家列表")
     * @ApiReturn
     * ({
        "code": 1,
        "msg": "",
        "time": "1703175528",
        "data": {
            "total": 3, //总条数
            "rows": [ //当前页记录
            {
                "id": 20, // id
                "name": "123123", //姓名
                "id_no": "1231231",//身份证
                "id_no_front_image": "/supply/upload/20231203/ab42299a4902c34baa1934171c6b4a6c.png",//身份证正面
                "id_no_backend_image": "/supply/upload/20231203/ab42299a4902c34baa1934171c6b4a6c.png",//身份证反面
                "wechat": "wewe",//微信号
                "addr": "wewewe",//地址
                "nickname": "wewe",//昵称
                "industry_ids": "11,8,3",//行业标签
                "skill_ids": "36,35",//技能标签
                "area_ids": "44",//区域标签
                "level_ids": "2,1,3",//专家评审等级
                "keywords_json": "关键词1,关键词2,关键词3",//关键词
                "lowest_price": 10000,//服务起始价格
                "case_json": [//案例
                {
                    "desc": "一句话"
                }
                ],
                "certificate_json": [//证书
                {
                    "idx": 0,
                    "name": "姓名",
                    "certifiimage": "/supply/upload/20231203/ab42299a4902c34baa1934171c6b4a6c.png",
                    "certifitime": "2023-12-01 - 2023-12-03",
                    "certifi_company": "证书机构"
                }
                ],
                "edu_json": [//教育信息
                {
                    "school_name": "学校1",
                    "degree_name": "学位1",
                    "major_name": "专业1",
                    "begin_time": "2011",
                    "end_time": "2022"
                }
                ],
                "feature_json": [//专家特色
                {
                    "name": "标题1",
                    "gender": "描述1"
                }
                ],
                "intro": "gegege",//个人简介
                "createtime": 1703173165,
                "updatetime": 1703173165,
                "deletetime": null,
                "status": "0",
                "tags": null,
                "user_id": 11,
                "status_text": "Status 0",
                "createtime_text": "2023-12-21 23:39:25",
                "updatetime_text": "2023-12-21 23:39:25",
                "avatar": "",//头像
                "role_type": "2",//角色类型：1-需求方 2-专家
                "typedata": "1",// 身份类型: 1-个人 2-企业
            }
            ]
        }
        })
     */
    public function index()
    {
        $user = $this->auth->getUser();
        if($this->auth->role_type === '2'){
            // 专家 数据隔离
            $this->error("专家无法查看其他专家信息");
        }

        $model = new SpecialistModel();
        $limit = $this->request->post('limit');
        $keyword = $this->request->post('keyword','');
        $lowest_price = $this->request->post('lowest_price');
        $order_num = $this->request->post('order_num','');
        $evaluate = $this->request->post('evaluate','');
        $order = [];
        if(!empty($order_num)){
            $order['order_count'] = $order_num;
        }
        if(!empty($evaluate)){
            $order['avg_score'] = $evaluate;
        }
        $where = [];
        /**
        $industry_ids = $this->request->post('industry_ids','');
        $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];


        foreach ($industry_arr as $parent_id) {
            getAllChildTags($parent_id, $industry_arr);
        }

        $skill_ids = $this->request->post('skill_ids','');
        $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

        foreach ($skill_arr as $parent_id) {
            getAllChildTags($parent_id, $skill_arr);
        }

        $area_ids = $this->request->post('area_ids','');
        $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];
        foreach ($area_arr as $parent_id) {
            getAllChildTags($parent_id, $area_arr);
        }
        $tag_arr = array_merge($industry_arr,$skill_arr,$area_arr);
         * **/

        $model = $model->alias('s')->field('s.*,u.avatar,u.role_type,u.typedata');

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
            $model = $model->where('s.name|s.nickname|s.keywords_json', 'like', "%$keyword%");
        }
        if($lowest_price){
            $model = $model->where('s.lowest_price','=', $lowest_price);
        }

        $verify_status = $user->verify_status ?? '0';
        $list = $model
            ->join('user u','s.user_id = u.id','right')
            ->join('fa_order_comment c', 's.user_id = c.to_user_id', 'left')
            ->join('fa_order d', 's.user_id = d.specialist_id', 'left')
            ->where('s.status','1')
            ->where($where)
            //->field('s.*,u.*,s.nickname as name,ROUND(IFNULL(AVG(c.points), 0), 1) as avg_score,IFNULL(COUNT(d.id), 0) as order_count,u.verify_status')
            ->field('s.*,u.*,s.nickname as name,IFNULL(COUNT(c.points), 0) as avg_score,IFNULL(COUNT(d.id), 0) as order_count,u.verify_status')
            ->order($order)
            ->group('s.user_id')
            ->paginate($limit)
            ->each(function($item, $key) use ($verify_status){
                if($verify_status === '0' or $verify_status === '2'){
                    $firstChar = mb_substr($item['name'], 0, 1, 'UTF-8');
                    $length = mb_strlen($item['name'], 'UTF-8') - 1;
                    $maskedNickname = $firstChar . str_repeat('*', $length >= 0 ? $length : 0);
                    $item['name'] = $maskedNickname;

                    $intro = mb_substr($item['intro'], 0, 5, 'UTF-8');
                    $intro_length = mb_strlen($item['intro'], 'UTF-8') - 5;
                    $maskedIntro = $intro . str_repeat('*', $intro_length >= 0 ? $intro_length : 0);

                    $item['intro'] = mb_substr($maskedIntro,0,15);
                    $item['id_no_name'] = '';
                    $item['username'] = '';
                    $feature_json = [];
                    foreach ($item['feature_json'] as $vo) {
                        $name = $vo["name"];
                        $gender = $vo["gender"];
                        $str = mb_strlen($name, 'utf-8') - 2;
                        $str2 = mb_strlen($gender, 'utf-8') - 2;
                        $maskedName = mb_substr($name, 0, 2, 'utf-8') . str_repeat('*', $str < 0?0:$str);
                        $genderName = mb_substr($gender, 0, 2, 'utf-8') . str_repeat('*', $str2 < 0?0:$str2);
                        $feature_json[] = ['name' => mb_substr($maskedName,0,12),'gender' => mb_substr($genderName,0,12)];
                    }
                    $item['feature_json'] = $feature_json;


                    $case_json = [];
                    if($item['case_json']){
                        foreach ($item['case_json'] as $vo) {
                            $name = $vo["desc"];
                            $str = mb_strlen($name, 'utf-8') - 2;
                            $maskedName = mb_substr($name, 0, 2, 'utf-8') . str_repeat('*', $str < 0?0:$str);

                            $case_json[] = ['desc' => mb_substr($maskedName,0,12)];
                        }
                    }

                    $item['case_json'] = $case_json;

                }

                return $item;
            });

        $data['accord_count'] = $list->total();


        $data['count'] = $model->alias('s')
            ->join('user u', 'u.id = s.user_id')
            ->join('fa_order_comment c', 's.user_id = c.to_user_id', 'left')
            ->field('s.*,s.nickname as user_nickname,u.mobile,ROUND(IFNULL(AVG(c.points), 0)) as avg_score')
            ->group('s.user_id')
            ->count();
        $data['skill_count'] = Db::name("tag")->where(['type' => '2','level' => '1'])->count();
        $data['industry_count'] = Db::name("tag")->where(['type' => '1','level' => '1'])->count();
        $data['area_count'] = Db::name("tag")->where(['type' => '3','level' => '1'])->count();


        $result = ['total' => $list->total(), 'rows' => $list->items(),'info' => $data];

        $this->success('获取成功', $result);
    }

    /**
     * 实名认证-专家
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $nickname 基础信息-昵称
     * @param string $avatar 基础信息-头像
     * @param string $captcha 基础信息-验证码
     * @param string $province_id 基础信息-省ID
     * @param string $city_id 基础信息-市ID
     * @param string $district_id 基础信息-区ID
     * @param string $mobile   手机号
     * @param string $name 身份证姓名
     * @param string $id_no 身份证号
     * @param string $id_no_front_image      身份认证正面照
     * @param string $id_no_backend_image   身份认证反面照
     * @param string $company_bank_name 公司-开户银行
     * @param string $company_bank_id 公司-开户行账号
     *@param string $id_no_bank_name 个人-开户行账号
     * @param string $id_no_bank_id 个人-开户行账号
     * @param string $id_no_bank_user 个人-收款人
     * @param string $wechat      微信号
     * @param string $addr      联系地址
     * @param string $nickname      昵称
     * @param string $industry_ids      行业标签ID,例：1,2,3
     * @param string $skill_ids      技能标签，例：1,2,3
     * @param string $area_ids      区域标签，例：1,2,3
     * @param string $level_ids      专家评审等级，例：1,2,3
     * @param string $keywords_json      关键词，例：1,2,3
     * @param string $lowest_price     服务报价起点，例：1000
     * @param string $case_json      案例数组，例：[{"desc":"一句话"},{"desc":"2句话"}]
     * @param string $edu_json      教育信息数组，例：[{"school_name":"学校1","degree_name":"学位1","major_name":"专业1","begin_time":"2011","end_time":"2022"}]
     * @param string $feature_json      专家特色数组，例：[{"name":"标题1","gender":"描述1"}]
     * @param string $intro      个人简介
     * @param string $certificate_json      资质证书数组，例：[{"idx":0,"name":"姓名","certifiimage":"/supply/upload/20231203/ab42299a4902c34baa1934171c6b4a6c.png","certifitime":"2023-12-01&nbsp;-&nbsp;2023-12-03","certifi_company":"证书机构"}]
     */
    public function verificationSpecialist()
    {

        $user = $this->auth->getUser();
//        $SpecialistModel = new SpecialistModel();
//        $UserArchive = new UserArchive();
        //已认证的专家信息
        $info = SpecialistModel::where(['user_id' => $this->auth->id])->find();
        if(SpecialistAuthModel::where(['user_id' => $this->auth->id])->find()){
            $SpecialistModel = SpecialistAuthModel::where(['user_id' => $this->auth->id])->find();
            $SpecialistModel->createtime = time();
            $SpecialistModel->updatetime = time();
        }else{
            $SpecialistModel = new SpecialistAuthModel();
        }
        Db::name("user_save")->where(['user_id' => $this->auth->id])->delete();
        // $this->request->filter(['trim']);
        if($this->auth->role_type === '1'){
            // 需求方
            $this->error("需求方请需求方认证");

        }else if($this->auth->role_type === '2'){
            // 专家 - 个人
            // 个人
            //基础信息
            $nickname = $this->request->post('nickname');
            $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
            $province_id = $this->request->post('province_id');
            $city_id = $this->request->post('city_id');
            $district_id = $this->request->post('district_id');
            //专家信息
            $id_no = $this->request->post('id_no');
            $name = $this->request->post('name');
            $id_no_front_image = $this->request->post('id_no_front_image');
            $id_no_backend_image = $this->request->post('id_no_backend_image');
            $id_no_bank_name = $this->request->post('id_no_bank_name');
            $id_no_bank_id = $this->request->post('id_no_bank_id');
            $id_no_bank_user = $this->request->post('id_no_bank_user');
            $wechat = $this->request->post('wechat');
            $addr = $this->request->post('addr');
            $industry_ids = $this->request->post('industry_ids');
            $skill_ids = $this->request->post('skill_ids');
            $area_ids = $this->request->post('area_ids');
            $level_ids = $this->request->post('level_ids');
            $keywords_json = $this->request->post('keywords_json');
            $lowest_price = $this->request->post('lowest_price');

            $case_json = $this->request->post('case_json','','strip_tags');

            $case_json = json_decode($case_json,true);
            $certificate_json = $this->request->post('certificate_json','','strip_tags');
            $certificate_json = json_decode($certificate_json,true);
            $edu_json = $this->request->post('edu_json','','strip_tags');
            $edu_json = json_decode($edu_json,true);
            $feature_json = $this->request->post('feature_json','','strip_tags');
            $feature_json = json_decode($feature_json,true);
            $intro = $this->request->post('intro');


            $is_id_no = Db::name("user")->where('id_no','=',$id_no)->where('id','<>',$this->auth->id)->find();
            $is_id_no2 = SpecialistModel::where('user_id','<>',$this->auth->id)->where('id_no','=',$id_no)->find();
            if(($is_id_no || $is_id_no2) && $id_no){
                $this->error("身份证已存在");
            }

            if($id_no == ''){
                //$this->error("身份证号不能为空");
            }
            if($name == ''){
                $this->error("身份证姓名不能为空");
            }
            if($wechat == ''){
                //$this->error("微信号码不能为空");
                $wechat = $info?$info->wechat:'';
            }
            if($id_no_front_image == ''){
                //$this->error("身份证正面照不能为空");
            }
            if($id_no_backend_image == ''){
                //$this->error("身份证反面照不能为空");
            }
            if($addr == ''){
                $this->error("地址不能为空");
            }
            if($nickname == ''){
                $this->error("昵称不能为空");
            }
            if($industry_ids == ''){
                $this->error("行业不能为空");
            }
            if($skill_ids == ''){
                $this->error("技能不能为空");
            }

            if($keywords_json == ''){
                //$this->error("关键词不能为空");
                $keywords_json = $info?$info->keywords_json:'';
            }
            if($lowest_price == ''){
                $lowest_price = $info?$info->lowest_price:'';
            }
            if($area_ids == ''){
                $this->error("地区不能为空");
            }

            if($case_json == '' || !count($case_json)){
                //$this->error("案例不能为空");
                $case_json = $info?$info->case_json:'';
            }
            if($certificate_json == '' || !count($certificate_json)){
                //$this->error("证书不能为空");
                $certificate_json = $info?$info->certificate_json:'';
            }
            if($edu_json == '' || !count($edu_json)){
                $this->error("教育经历不能为空");
            }
            if($feature_json == '' || !count($feature_json)){
                $this->error("专家特色不能为空");
            }
            if($intro == ''){
                $this->error("个人简介不能为空");
            }

            if ($nickname) {
                $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
                if ($exists) {
                    $this->error(__('昵称已存在'));
                }
            }

            $SpecialistModel->avatar = $avatar;

            if($info and ($province_id || $city_id || $district_id)){

                $new_province_name = Db::name("area")->where(['id' => $province_id])->value("name");
                $new_city_name = Db::name("area")->where(['id' => $city_id])->value("name");
                $new_district_name = Db::name("area")->where(['id' => $district_id])->value("name");

                $province_name = Db::name("area")->where(['id' => $info->province_id])->value("name");
                $city_name = Db::name("area")->where(['id' => $info->city_id])->value("name");
                $district_name = Db::name("area")->where(['id' => $info->district_id])->value("name");

                if($new_province_name != $province_name){
                    user_save($this->auth->id,"地址省".$province_name."变更".$new_province_name);
                }
                if($new_city_name != $city_name){
                    user_save($this->auth->id,"地址市".$city_name."变更".$new_city_name);
                }
                if($new_district_name != $district_name){
                    user_save($this->auth->id,"地址区".$district_name."变更".$new_district_name);
                }
            }


            if ($province_id) {
                $SpecialistModel->province_id = $province_id;
            }
            if ($city_id) {
                $SpecialistModel->city_id = $city_id;
            }
            if ($district_id) {
                $SpecialistModel->district_id = $district_id;
            }


            $SpecialistModel->user_id = $this->auth->id;
            if(isset($info->id) and $info->id_no !== $id_no){
                user_save($this->auth->id,"身份证号:".$info->id_no."变更".$id_no);
            }
            $SpecialistModel->id_no = $id_no;

            if(isset($info->name) and $info->name != $name){
                user_save($this->auth->id,"身份证姓名:".$info->name."变更".$name);
            }
            $SpecialistModel->name = $name;

            $SpecialistModel->id_no_front_image = $id_no_front_image;
            $SpecialistModel->id_no_backend_image = $id_no_backend_image;

            if(isset($info->id) and $info->id_no_bank_name !== $id_no_bank_name){
                user_save($this->auth->id,"开户银行".$info->id_no_bank_name."变更".$id_no_bank_name);
            }
            $SpecialistModel->id_no_bank_name = $id_no_bank_name;

            if(isset($info->id) and $info->id_no_bank_id !== $id_no_bank_id){
                user_save($this->auth->id,"开户账号".$info->id_no_bank_id."变更".$id_no_bank_id);
            }
            $SpecialistModel->id_no_bank_id = $id_no_bank_id;

            if(isset($info->id) and $info->wechat !== $wechat){
                user_save($this->auth->id,"微信号码".$info->wechat."变更".$wechat);
            }
            $SpecialistModel->wechat = $wechat;

            if(isset($info->id) and $info->addr !== $addr){
                user_save($this->auth->id,"联系地址".$info->addr."变更".$addr);
            }
            $SpecialistModel->addr = $addr;

            if(isset($info->id) and $info->nickname !== $nickname){
                user_save($this->auth->id,"昵称".$info->nickname."变更".$nickname);
            }
            $SpecialistModel->nickname = $nickname;

            if(isset($info->id) and $info->id_no_bank_user !== $id_no_bank_user){
                user_save($this->auth->id,"收款人".$info->id_no_bank_user."变更".$id_no_bank_user);
            }
            $SpecialistModel->id_no_bank_user = $id_no_bank_user;

            $ids1 = explode(',',$industry_ids);
            $ids2 = $info?explode(',',$info->industry_ids):[];
            sort($ids1);
            sort($ids2);
            if(isset($info->id) and $ids1 !== $ids2){
                $industry = Db::name("tag")->whereIn("id",$info->industry_ids)->column('name');
                $save_industry = Db::name("tag")->whereIn("id",$industry_ids)->column('name');
                user_save($this->auth->id,"行业标签".implode('、',$industry)."变更".implode('、',$save_industry));
            }
            $SpecialistModel->industry_ids = $industry_ids;

            $ids1 = explode(',',$skill_ids);
            $ids2 = $info?explode(',',$info->skill_ids):[];
            sort($ids1);
            sort($ids2);
            if(isset($info->id) and $ids1 !== $ids2){
                $skill = Db::name("tag")->whereIn("id",$info->skill_ids)->column('name');
                $save_skill = Db::name("tag")->whereIn("id",$skill_ids)->column('name');
                user_save($this->auth->id,"技能标签".implode('、',$skill)."变更".implode('、',$save_skill));
            }
            $SpecialistModel->skill_ids = $skill_ids;

            $ids1 = explode(',',$area_ids);
            $ids2 = $info?explode(',',$info->area_ids):[];
            sort($ids1);
            sort($ids2);
            if(isset($info->id) and $ids1 !== $ids2){
                $area = Db::name("tag")->whereIn("id",$info->area_ids)->column('name');
                $save_area = Db::name("tag")->whereIn("id",$area_ids)->column('name');
                user_save($this->auth->id,"地区标签".implode('、',$area)."变更".implode('、',$save_area));
            }
            $SpecialistModel->area_ids = $area_ids;

            $SpecialistModel->case_json = $case_json;
            $SpecialistModel->certificate_json = $certificate_json;
            $SpecialistModel->edu_json = $edu_json;
            $SpecialistModel->feature_json = $feature_json;
            $SpecialistModel->level_ids = $level_ids;

            if(isset($info->id) and $info->edu_json != $edu_json){
                user_save($this->auth->id,"教育经历:".json_encode($info->edu_json,JSON_UNESCAPED_UNICODE)."变更".json_encode($edu_json));
            }

            if(isset($info->id) and $info->keywords_json != $keywords_json){
                user_save($this->auth->id,"关键词".$info->keywords_json."变更".$keywords_json);
            }
            $SpecialistModel->keywords_json = $keywords_json;

            if(isset($info->id) and $info->lowest_price != $lowest_price){
                user_save($this->auth->id,"服务报价起点".$info->lowest_price."变更".$lowest_price);
            }
            $SpecialistModel->lowest_price = $lowest_price;

            if(isset($info->id) and $info->intro !== $intro){
                user_save($this->auth->id,"个人简介".$info->intro."变更".$intro);
            }
            $SpecialistModel->intro = $intro;

            $SpecialistModel->status = 0; //取消变成未认证状态
        }

        Db::startTrans();
        try {
            $SpecialistModel->save();

            /**
            $industry_arr = explode(",", $industry_ids);
            $skill_arr = explode(",", $skill_ids);
            $area_arr = explode(",", $area_ids);
            foreach ($industry_arr as $key => $value) {
                $TagSpecialistModel = new TagSpecialistModel();
                $TagSpecialistModel->specialist_id = $SpecialistModel->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($skill_arr as $key => $value) {
                $TagSpecialistModel = new TagSpecialistModel();
                $TagSpecialistModel->specialist_id = $SpecialistModel->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($area_arr as $key => $value) {
                $TagSpecialistModel = new TagSpecialistModel();
                $TagSpecialistModel->specialist_id = $SpecialistModel->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }**/
            //通知平台审核
            setPostMessage(1,0,'id为'.$this->auth->id.'的用户进行专家认证或者修改资料','/kfSypMgbqw.php/specialist/review_details/ids/'.$SpecialistModel->id.'/auth/1?dialog=1');
            // $user->save();
            // $UserArchive->save();
            // $user->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$SpecialistModel);
    }
    
    /**
     * 专家详情
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   专家ID:专家列表的user_id
     * @ApiReturn   ({
        "code": 1,
        "msg": "操作成功",
        "time": "1705142663",
        "data": {
            "id": 21,
            "name": "张三",
            "id_no": "110",//身份证号
            "id_no_front_image": "",//身份证正面
            "id_no_backend_image": "",//身份证反面
            "wechat": "微信",//微信号
            "addr": "地址",//地址
            "nickname": "张三三",//昵称
            "industry_ids": "2",//行业标签id
            "skill_ids": "14,16",//技能标签id
            "area_ids": "13",//区域标签id
            "level_ids": "2",//级别标签id
            "keywords_json": "",//关键词
            "lowest_price": 100,//最低价格
            "case_json": [//案例
            {
                "desc": "案例"
            }
            ],
            "certificate_json": [//证书
            {
                "name": "证书名",
                "certifiimage": "/uploads/20240111/5c643c5a1da7fb300559b6ec84957977.png",
                "certifitime": "2024-02-05-2024-02-13",
                "certifi_company": "发证机构",
                "fullurl": "https://mgtoffice.qianqiance.com/uploads/20240111/5c643c5a1da7fb300559b6ec84957977.png",
                "idx": 0
            }
            ],
            "edu_json": [//教育信息
            {
                "school_name": "学校",
                "degree_name": "学位",
                "major_name": "专业",
                "begin_time": "2024-01-31",
                "end_time": "2024-01-16"
            }
            ],
            "feature_json": [//特长描述
            {
                "name": "标题",
                "gender": "描述"
            }
            ],
            "intro": "个人简介",//个人简介
            "createtime": 1704953762,
            "updatetime": 1704953957,
            "deletetime": null,
            "status": "1",
            "tags": "",
            "user_id": 18,
            "skill": [//技能
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
                "level_text": "Level 1",
                "type_text": "Type 2",
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
                "level_text": "Level 1",
                "type_text": "Type 2",
                "createtime_text": "2023-12-05 16:06:23",
                "updatetime_text": "2023-12-05 16:06:23"
            }
            ],
            "industry": [//行业
            {
                "id": 2,
                "name": "信息技术",
                "createtime": 0,
                "deletetime": null,
                "pid": 0,
                "path": "-",
                "level": "1",
                "updatetime": 1701763089,
                "type": "1",
                "sort": 1000,
                "level_text": "Level 1",
                "type_text": "Type 1",
                "createtime_text": "1970-01-01 08:00:00",
                "updatetime_text": "2023-12-05 15:58:09"
            }
            ],
            "area": [//区域
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
                "level_text": "Level 1",
                "type_text": "Type 3",
                "createtime_text": "2023-12-02 21:42:26",
                "updatetime_text": "2023-12-02 21:42:26"
            }
            ],
            "order_num": 4,//订单数
            "comment_num": 3,//评价数
            "comment_points": 3.5,//平均评分
            "is_fav": 0,//0-未收藏,1-已收藏
            "status_text": "Status 1",
            "createtime_text": "2024-01-11 14:16:02",
            "updatetime_text": "2024-01-11 14:19:17"
        },
        "domain": "http://supply.test"
        })
     */
    public function detail(){

        $id = $this->request->post('id');
        $userArchive = SpecialistModel::alias('s')
        ->join('user u','s.user_id = u.id','left')
        ->field('s.*,u.nickname,u.verify_status')
        ->where('s.user_id', $id)
        ->order('s.id','desc')
        ->find();
        if(!$userArchive){
            $this->error("获取失败:找不到专家",'');
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
        
        $fav = SpecialistFavModel::where('specialist_user_id',$id)->where('user_id',$this->auth->id)->count();
        $userArchive['is_fav'] = $fav>0 ? 1 : 0;
        $userArchive['order_num'] = OrderModel::where('specialist_id',$id)->count();
        $userArchive['comment_num'] = OrderCommentModel::where('to_user_id',$id)->count();
        $userArchive['comment_points'] = OrderCommentModel::where('to_user_id',$id)->avg('points');
        

        // $tag_arr = array_merge($industry_arr,$skill_arr,$area_arr);
        $this->success("操作成功",$userArchive);

    }

    /**
     * 需求方-收藏/取消专家
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   专家ID:专家列表的user_id
     * @param string $type   类型:type=1收藏,type=2取消收藏
     */
    public function fav(){

        $id = $this->request->post('id');
        $userArchive = SpecialistModel::where('user_id', $id)->order('id','desc')->find();
        if(!$userArchive){
            $this->error("获取失败:找不到专家",'');
        }
        $type = $this->request->post('type',1);
        if(intval($type) === 1){
            //收藏
            $has = SpecialistFavModel::where('user_id',$this->auth->id)->where('specialist_user_id',$id)->find();
            if($has){
                $this->error("收藏失败:已收藏");
            }
            $model = new SpecialistFavModel();
            $model->user_id = $this->auth->id;
            $model->specialist_user_id = $id;
            $model->save();
            $this->success("收藏成功");
        }elseif(intval($type) === 2){
            //取消收藏
            $has = SpecialistFavModel::where('user_id',$this->auth->id)->where('specialist_user_id',$id)->find();
            if(!$has){
                $this->error("取消收藏失败:未收藏");
            }
            $has->delete();
            $this->success("取消收藏成功");
        }
        $this->success("操作成功",'');

    }

    
}
