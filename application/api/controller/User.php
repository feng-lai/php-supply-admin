<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Order;
use app\common\model\User as UserModel;
use app\common\model\Specialist as SpecialistModel;
use fast\Random;
use think\Config;
use think\Validate;
use app\common\model\UserArchive;
use think\Db;
use think\Cache;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third','wxlogin','bind_mobile'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 个人信息
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     */
    public function index()
    {
        $userInfo = $this->auth->getUserinfo();
        $userArchive = false;
        if($userInfo['role_type'] == '1'){
            //需求方
            $userArchive = $this->auth->getVerification();
        }

        if($userInfo['role_type'] == '2'){
            //专家
            $userArchive = $this->auth->getSpecVerification();

            $data = SpecialistModel::alias("s")->where('s.user_id', $userInfo['id'])
                ->join('fa_order_comment c', 's.user_id = c.to_user_id', 'left')
                ->join('fa_order d', 's.user_id = d.specialist_id', 'left')
                ->field("s.*,ROUND(IFNULL(AVG(c.points), 0), 1) as avg_score,IFNULL(COUNT(d.id), 0) as order_count")
                ->order('s.id','desc')
                ->find();

            $userInfo['avg_score'] = $data['avg_score'];
            $userInfo['order_comment'] = Db::name("order_comment")->where("to_user_id",$userArchive['user_id'])->where('points','>',3)->count();
            $userInfo['order_price'] = Db::name("order")
            ->alias("a")
            ->join("order_bill b",'a.id = b.order_id','left')
            ->where(['a.specialist_id' => $userArchive['user_id'],'b.type' => '1','b.status' => '1'])
            ->sum('b.real_total');

            $userInfo['comment_count'] = Db::name("order_comment")->where(['to_user_id' => $data['user_id']])->where('points >= 3')->count();

            $skill_ids = explode(",",$userArchive['skill_ids']);
            $industry_ids = explode(",",$userArchive['industry_ids']);
            $area_ids = explode(",",$userArchive['area_ids']);

            $userInfo['skill'] = Db::name("tag")->where('id','in',$skill_ids)->select();
            $userInfo['industry'] = Db::name("tag")->where('id','in',$industry_ids)->select();
            $userInfo['area'] = Db::name("tag")->where('id','in',$area_ids)->select();
            $userInfo['verify_status'] = $userArchive['status']?$userArchive['status']:0;
            $userInfo['id_no_bank_user'] = $userArchive['id_no_bank_user'];
        }

        

        $this->success('', [
            'userinfo' => $userInfo,
            'user_archive' => $userArchive
        ]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $info = $this->auth->getUserinfo();
            //保存登陆记录
            $this->auth->save_login_time($info);
            if($info['status'] == 'locked'){
                $this->error('账户已被禁用');
            }
            //查询专家信息是否被禁用
            $status = SpecialistModel::where('user_id',$info['id'])->value('status');
            if($status == 2){
                $this->error('专家账户已被禁用');
            }
            $data = ['userinfo' => $info];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $captcha, 'login');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'login');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->auth->save_login_time($data['userinfo']);
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * pc微信扫码登录
     *
     * @ApiMethod (POST)
     * @param string $code  微信code
     */
    public function wxlogin()
    {
        $code = $this->request->post('code');
        if (!$code) {
            $this->error(__('Invalid parameters'));
        }
        $appId = Config::get('appId');
        $appSecret = Config::get('appSecret');
        //获取access_token
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appId.'&secret='.$appSecret.'&code='.$code.'&grant_type=authorization_code');
        $res = $response->getBody();
        $res = json_decode($res,true);
        if(isset($res['errcode'])){
            $this->error($res['errmsg']);
        }
        $openid = $res['openid'];
        $access_token = $res['access_token'];

        //获取用户个人信息
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid);
        $res = $response->getBody();
        $res = json_decode($res,true);
        if(isset($res['errcode'])){
            $this->error($res['errmsg']);
        }
        //判断用户是否存在
        $user = UserModel::where('unionid',$res['unionid'])->find();
        if ($user && $user->mobile) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
            if ($ret) {
                $data = ['userinfo' => $this->auth->getUserinfo()];
                $this->auth->save_login_time($data['userinfo']);
                $this->success(__('Logged in successful'), $data);
            } else {
                $this->error($this->auth->getError());
            }
        }else{
            if(!$user){
                //保存
                $user = new UserModel;
                $user->unionid = $res['unionid'];
                $user->openid = $res['openid'];
                $user->avatar = $res['headimgurl'];
                $user->nickname = $res['nickname'];
                $user->save();
            }
            $this->success('新用户,还需要绑定手机以及选择角色类型和身份类型',['id'=>$user->id], 100);
        }
    }

    /**
     * pc微信扫码登录后续绑定手机以及选择角色类型和身份类型
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param integer $id  扫码登陆返回的id
     * @param integer $role_type  角色类型:1=需求方,2=专家
     * @param integer $typedata  身份类型:1=个人,2=企业

    public function bind_mobile(){
        $mobile = $this->request->post('mobile');
        $id = $this->request->post('id');
        $role_type = $this->request->post('role_type');
        $typedata = $this->request->post('typedata');
        if (!$mobile || !$typedata || !$id || !$role_type) {
            $this->error(__('Invalid parameters'));
        }
        if(UserModel::where('mobile',$mobile)->count()){
            $this->error('手机号已存在');
        }
        $time = time();
        $user = UserModel::get($id);
        if(!$user){
            $this->error('用户不存在');
        }
        $user->status = 'normal';
        $user->mobile = $mobile;
        $user->role_type = $role_type;
        $user->typedata = $typedata;
        $user->jointime = $time;
        $user->joinip = request()->ip();
        $user->save();
        $ret = $this->auth->direct($id);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }
     *  */

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @ApiParams (name="id", type="integer", required=false, description="微信扫码返回的id")
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     * @ApiParams (name="password2", type="string", required=true, description="重复密码")
     * @ApiParams (name="code", type="string", required=true, description="验证码")
     * @ApiParams (name="role_type", type="integer", required=true, description="角色类型:1=需求方,2=专家")
     * @ApiParams (name="typedata", type="integer", required=true, description="身份类型:1=个人,2=企业")
     */
    public function register()
    {
        $extend = [];
        $id = $this->request->post('id');
        $username = $this->request->post('mobile');
        $password = $this->request->post('password');
        $password2 = $this->request->post('password2');
        $mobile = $this->request->post('mobile');
        $code = $this->request->post('code');
        $role_type = $this->request->post('role_type');
        $typedata = $this->request->post('typedata');
        if($id){
            $info = UserModel::get($id);
            if($info['salt']){
                $this->error('id有误');
            }
            $extend['avatar'] = $info['avatar'];
            $extend['nickname'] = $info['nickname'];
            $extend['unionid'] = $info['unionid'];
            $extend['openid'] = $info['openid'];
            $extend['id'] = $info['id'];

        }
        if (!$username || !$password || !$password2) {
            $this->error(__('Invalid parameters'));
        }
        if ($password !== $password2) {
            $this->error("两次密码不一致");
        }
        if(!in_array($role_type,["1","2"])){
            $this->error("角色类型不合法");
        }
        if(!in_array($typedata,["1","2"])){
            $this->error("身份类型不合法");
        }
        // if ($email && !Validate::is($email, "email")) {
        //     $this->error(__('Email is incorrect'));
        // }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
         $ret = Sms::check($mobile, $code, 'register');

//        $ret = ($code == '123456')?true:false;
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $email = "";
        $ret = $this->auth->register($username, $password, $email, $mobile, $extend,$role_type, $typedata);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->auth->save_login_time($data['userinfo']);
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
             $ret = Sms::check($mobile, $captcha, 'resetpwd');
            // $ret = ($captcha == '123456')?true:false;
            // if (!$ret) {
            //     $this->error(__('Captcha is incorrect'));
            // }
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 实名认证-需求方
     *
     * @ApiMethod (POST)
     * @ApiSummary  (修改的时候，基础信息不用填)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $nickname 基础信息-昵称
     * @param string $avatar 基础信息-头像
     * @param string $mobile 基础信息-手机号
     * @param string $captcha 基础信息-验证码
     * @param string $province_id 基础信息-省ID
     * @param string $city_id 基础信息-市ID
     * @param string $district_id 基础信息-区ID
     * @param string $id_no   身份证号
     * @param string $id_no_name 身份证姓名
     * @param string $id_no_front_image 身份证正面照
     * @param string $id_no_backend_image      身份证反面照
     * @param string $id_no_bank_name 个人-银行名称
     * @param string $id_no_bank_id 个人-银行卡号
     * @param string $company_name 公司名称（企业专用）
     * @param string $company_id_no 营业执照号码（企业专用）
     * @param string $company_id_no_image 营业执照照片（企业专用）
     * @param string $company_attachfile 法人授权照片（企业专用）
     * @param string $company_bank_name 对公银行名称（企业专用）
     * @param string $company_id_no 对公账号（企业专用）
     */
    public function verificationUser()
    {
        if($this->auth->role_type === '2'){
            $this->error("专家请进行专家认证");
        }

        $user = $this->auth->getUser();

        //ture = 首次认证或者 false=资料修改
        $is = false;

        //认证信息
        $UserArchive = UserArchive::where(['user_id' => $this->auth->id])->find();
        if(!$UserArchive){
            $is = true;
            $UserArchive = new UserArchive();
        }

        //删除记录
        Db::name("user_save")->where(['user_id' => $this->auth->id])->delete();

        $nickname = $this->request->post('nickname');
        $mobile = $this->request->post('mobile');
        $province_id = $this->request->post('province_id');
        $city_id = $this->request->post('city_id');
        $district_id = $this->request->post('district_id');
        $captcha = $this->request->post('captcha');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        $id_no = $this->request->post('id_no');
        $id_no_name = $this->request->post('id_no_name');
        $company_name = $this->request->post('company_name');
        $company_id_no = $this->request->post('company_id_no');
        $company_id_no_image = $this->request->post('company_id_no_image');
        $company_attachfile = $this->request->post('company_attachfile');
        $company_bank_name = $this->request->post('company_bank_name');
        $company_bank_id = $this->request->post('company_bank_id');
        $address = $this->request->post('address');
        $id_no_front_image = $this->request->post('id_no_front_image');
        $id_no_backend_image = $this->request->post('id_no_backend_image');
        $id_no_bank_name = $this->request->post('id_no_bank_name');
        $id_no_bank_id = $this->request->post('id_no_bank_id');

        if($id_no == ''){
            //$this->error("身份证号不能为空");
        }
        if($id_no_name == ''){
            $this->error("身份证姓名不能为空");
        }
        if($id_no_front_image == ''){
            //$this->error("身份证正面照不能为空");
        }
        if($id_no_backend_image == ''){
            //$this->error("身份证反面照不能为空");
        }


        // 需求方
        //基础信息

        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exist'));
            }
            if(isset($UserArchive->id) and $user->nickname !== $nickname){
                user_save($this->auth->id,"昵称".$user->nickname."变更".$nickname);
            }

            $UserArchive->nickname = $nickname;
        }

         $UserArchive->avatar = $avatar;

        if ($mobile) {
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('验证码错误'));
            }
            if(isset($UserArchive->id) and $user->mobile !== $mobile){
                user_save($this->auth->id,"手机号".$user->mobile."变更".$mobile);
            }
            $UserArchive->mobile = $mobile;
        }
        if(isset($UserArchive->id) and ($province_id || $city_id || $district_id)){

            $new_province_name = Db::name("area")->where(['id' => $province_id])->value("name");
            $new_city_name = Db::name("area")->where(['id' => $city_id])->value("name");
            $new_district_name = Db::name("area")->where(['id' => $district_id])->value("name");

            $province_name = Db::name("area")->where(['id' => $user->province_id])->value("name");
            $city_name = Db::name("area")->where(['id' => $user->city_id])->value("name");
            $district_name = Db::name("area")->where(['id' => $user->district_id])->value("name");
            if($province_name !== $new_province_name){
                user_save($this->auth->id,"地址".$province_name."变更".$new_province_name);
            }
            if($city_name !== $new_city_name){
                user_save($this->auth->id,"地址".$city_name."变更".$new_city_name);
            }
            if($district_name !== $new_district_name){
                user_save($this->auth->id,"地址".$district_name."变更".$new_district_name);
            }
        }


        if ($province_id) {
            $UserArchive->province_id = $province_id;
        }
        if ($city_id) {
            $UserArchive->city_id = $city_id;
        }
        if ($district_id) {
            $UserArchive->district_id = $district_id;
        }


        // 个人  企业
        if($this->auth->typedata === '1'){

            // 个人
            //身份证号码
            if(isset($UserArchive->id) and $user->id_no !== $id_no){
                user_save($this->auth->id,"身份证号码".$user->id_no."变更".$id_no);
            }
            $UserArchive->id_no = $id_no;
            
            //身份证照片
            $UserArchive->id_no_front_image = $id_no_front_image;
            $UserArchive->id_no_backend_image = $id_no_backend_image;

            //身份证姓名
            if(isset($UserArchive->id) and $user->id_no_name !== $id_no_name){
                user_save($this->auth->id,"身份证姓名".$user->id_no_name."变更".$id_no_name);
            }
            $UserArchive->id_no_name = $id_no_name;

            //开户银行
            if(isset($UserArchive->id) and $user->id_no_bank_name !== $id_no_bank_name){
                user_save($this->auth->id,"开户银行".$user->id_no_bank_name."变更".$id_no_bank_name);
            }
            $UserArchive->id_no_bank_name = $id_no_bank_name;

            //开户账号
            if(isset($UserArchive->id) and $user->id_no_bank_id !== $id_no_bank_id){
                user_save($this->auth->id,"开户账号".$user->id_no_bank_id."变更".$id_no_bank_id);
            }
            $UserArchive->id_no_bank_id = $id_no_bank_id;


            $UserArchive->user_id = $this->auth->id;
            $UserArchive->verify_status = 0;

        }else if($this->auth->typedata === '2'){

            // 企业
            if($company_name == ''){
                $this->error("公司名称不能为空");
            }
            //判断公司名称是否存在
            if(UserModel::where('company_name',$company_name)->where('id','<>',$this->auth->id)->where('status','<>',2)->count() || \app\admin\model\UserArchive::where('user_id','<>',$this->auth->id)->where('company_name',$company_name)->count()){
                $this->error("您所设置的企业已绑定其他经办人");
            }
            if($company_id_no == ''){
                //$this->error("营业执照号码不能为空");
            }
            if($company_id_no_image == ''){
                //$this->error("营业执照照片不能为空");
            }
            if($company_attachfile == ''){
                //$this->error("法人授权照片不能为空");
            }

            //身份证号码
            $UserArchive->user_id = $this->auth->id;
            if(isset($UserArchive->id) and $user->id_no !== $id_no){
                user_save($this->auth->id,"身份证号码".$user->id_no."变更".$id_no);
            }
            $UserArchive->id_no = $id_no;

            //身份证姓名
            if(isset($UserArchive->id) and $user->id_no_name !== $id_no_name){
                user_save($this->auth->id,"身份证姓名".$user->id_no_name."变更".$id_no_name);
            }
            $UserArchive->id_no_name = $id_no_name;

            $UserArchive->id_no_front_image = $id_no_front_image;
            $UserArchive->id_no_backend_image = $id_no_backend_image;
            $UserArchive->verify_status = 0;

            //公司名称
            if(isset($UserArchive->id) and $user->company_name !== $company_name){
                user_save($this->auth->id,"公司名称".$user->company_name."变更".$company_name);
            }
            $UserArchive->company_name = $company_name;

            //营业执照号码
            if(isset($UserArchive->id) and $user->company_id_no !== $company_id_no){
                user_save($this->auth->id,"营业执照号码".$user->company_id_no."变更".$company_id_no);
            }
            $UserArchive->company_id_no = $company_id_no;

            $UserArchive->company_id_no_image = $company_id_no_image;

            $UserArchive->company_attachfile = $company_attachfile;

            //企业开户银行
            if(isset($UserArchive->id) and $user->company_bank_name !== $company_bank_name){
                user_save($this->auth->id,"开户银行".$user->company_bank_name."变更".$company_bank_name);
            }
            $UserArchive->company_bank_name = $company_bank_name;

            //企业开户行账号
            if(isset($UserArchive->id) and $user->company_bank_id !== $company_bank_id){
                user_save($this->auth->id,"开户行账号".$user->company_bank_id."变更".$company_bank_id);
            }
            $UserArchive->company_bank_id = $company_bank_id;

            $company_id_no = Db::name("user")->where(['company_id_no' => $company_id_no])->where('id','<>',$this->auth->id)->find();
            if($company_id_no && $this->request->post('company_id_no')){
                $this->error("营业执照号已存在");
            }

            //公司地址
            if(isset($UserArchive->id) and $user->address !== $address){
                user_save($this->auth->id,"公司地址".$user->address."变更".$address);
            }
            $UserArchive->address = $address;
        }

        $is_mobile = Db::name("user")->where('mobile','=',$mobile)->where('id','<>',$this->auth->id)->find();
        if($is_mobile){
            $this->error("手机号已存在");
        }

        $is_nickname = Db::name("user")->where('nickname','=',$nickname)->where('id','<>',$this->auth->id)->find();
        if($is_nickname){
            $this->error("昵称已存在");
        }

        $is_id_no = Db::name("user")->where('id_no','=',$id_no)->where('id','<>',$this->auth->id)->find();
        if($is_id_no && $id_no){
            $this->error("身份证号已存在");
        }

        /**$cardV = IdCardOCRVerification($id_no_front_image);
        if($cardV['code'] == 0){
            setPostMessage(1,$this->auth->id,'您提交的个人实名认证未通过','');
            $this->error($cardV['msg']);
        }else{
            if($cardV['data']['Result'] !== '0'){
                setPostMessage(1,$this->auth->id,'您提交的个人实名认证未通过','');
                $this->error($cardV['data']['Description']);
            }
        }
        if($is){
            $user->verify_status = 1;
            setPostMessage(1,$this->auth->id,'您提交的个人实名认证已通过','');
        }**/
        $user->verify_status = 1;
        setPostMessage(1,$this->auth->id,'您提交的个人实名认证已通过','');

        Db::startTrans();
        try {
            //1个人 2企业
            if($this->auth->typedata === '1'){
                //通知平台审核
                setPostMessage(1,0,'id为'.$this->auth->id.'的个人用户进行修改资料','/kfSypMgbqw.php/user/user/vertifydetail/ids/'.$this->auth->id.'?dialog=1');
            }else{
                if($this->auth->enterprise_status != 1){
                    //企业认证
                    //通知平台审核
                    setPostMessage(1,0,'id为'.$this->auth->id.'的用户进行企业认证','/kfSypMgbqw.php/user/user/vertifydetail?is=1&ids='.$this->auth->id.'&dialog=1');
                }else{
                    //通知平台审核
                    setPostMessage(1,0,'id为'.$this->auth->id.'的企业用户进行修改资料','/kfSypMgbqw.php/user/user/vertifydetail/ids/'.$this->auth->id.'?dialog=1');
                }

            }
            $UserArchive->save();
            $user->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败");
        }
        
        $this->success();
    }

    
}
