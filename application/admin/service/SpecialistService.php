<?php
namespace app\admin\service;

use app\common\model\TagSpecialist as TagSpecialistModel;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use think\Db;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;
use app\admin\service\BaseService;
use app\admin\model\User;
use app\admin\model\UserArchive;
use app\admin\model\Specialist;
use app\admin\model\Tag;


class SpecialistService extends BaseService
{

    /**
     * 后台增加用户
     */
    public function add($row){

        #1.新增用户档案
        #2.新增用户数据
        $user = new User();

        $archive = new Specialist();
        // dump($row);die;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            // 添加用户账号-默认密码为手机号
            $user->status = "normal";
            $user->typedata = 1;// 注册用户为个人
            $user->role_type = 2;// 注册用户为专家
            $user->username = $row['nickname'];
            $is_username = Db::name("user")->where(['nickname' => $row['nickname']])->find();
            if($is_username){
                Db::rollback();
                throw new Exception("昵称已存在");
            }
            $user->mobile = $row['mobile'];
            $is_user = Db::name("user")->where(['mobile' => $row['mobile']])->find();
            if($is_user){
                Db::rollback();
                throw new Exception("手机号已存在");
            }
            $salt = \fast\Random::alnum();
            $user->password = \app\common\library\Auth::instance()->getEncryptPassword($row['password'], $salt);
            $user->salt = $salt;


            $user->id_no_name = $row['name'];

            $user->verify_status = $row['status'];// 默认用户已通过审核
            // dump($user);die;
            $r1 = $user->save();
            if(!$r1){
                // 用户添加失败-回滚
                throw new Exception("用户添加失败");
            }
            
            // 添加专家档案

            $archive->name = $row['name'];

            $archive->wechat = $row['wechat'];
            $archive->status = $row['status'];
            $archive->user_id = $user->id;
            $archive->addr = $row['addr'];
            $archive->nickname = $row['nickname'];
            $archive->industry_ids = $row['industry_ids'];
            $archive->skill_ids = $row['skill_ids'];
            $archive->area_ids = $row['area_ids'];
            $archive->level_ids = $row['Level_ids'];
            $archive->keywords_json = $row['keywords_json'];
            $archive->lowest_price = $row['lowest_price'];
            $archive->case_json = json_decode($row['case_json'],true);
            $archive->edu_json = json_decode($row['edu_json'],true);
            $archive->feature_json = json_decode($row['feature_json'],true);
            $archive->intro = $row['intro'];
            $archive->certificate_json = json_decode($row['certificate_json'],true);
            $r2 = $archive->save();
            $industry_arr = explode(",", $row['industry_ids']);
            $skill_arr = explode(",",  $row['skill_ids']);
            $area_arr = explode(",", $row['area_ids']);
            foreach ($industry_arr as $key => $value) {
                $TagSpecialistModel = new TagSpecialistModel();
                $TagSpecialistModel->specialist_id = $archive->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($skill_arr as $key => $value) {
                $TagSpecialistModel = new TagSpecialistModel();
                $TagSpecialistModel->specialist_id = $archive->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($area_arr as $key => $value) {
                $TagSpecialistModel = new TagSpecialistModel();
                $TagSpecialistModel->specialist_id = $archive->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            if(!$r2){
                // 用户添加失败-回滚
                throw new Exception("专家添加失败");
            }
            
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            // $this->error($e->getMessage());
            return [
                'code' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'code' => 1,
            'msg' => '添加成功'
        ];

    }

    /**
     * 后台增加用户
     */
    public function addUser($row){

        #1.新增用户档案
        #2.新增用户数据
        $user = new User();
        $archive = new UserArchive();
        // dump($this->request->module());die;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            // 添加用户账号-默认密码为手机号
            $user->status = "normal";
            $user->typedata = $row['typedata'];
            $user->username = $row['mobile'];
            $user->mobile = $row['mobile'];
            $salt = \fast\Random::alnum();
            $user->password = \app\common\library\Auth::instance()->getEncryptPassword($row['mobile'], $salt);
            $user->salt = $salt;
            $user->id_no = $row['id_no'];
            $user->id_no_name = $row['id_no_name'];
            $user->id_no_front_image = $row['id_no_front_image'];
            $user->id_no_backend_image = $row['id_no_backend_image'];
            $user->verify_status = $row['verify_status'];// 默认用户已通过审核

            $user->company_name =  $row['company_name'];
            $user->company_id_no =  $row['company_id_no'];
            $user->company_id_no_image =  $row['company_id_no_image'];
            $user->company_attachfile =  $row['company_attachfile'];
            
            $r1 = $user->save();
            if(!$r1){
                // 用户添加失败-回滚
                throw new Exception("用户添加失败");
            }
            
            // 添加档案
            if($row['typedata'] === '1'){
                // 用户档案
                $archive->typedata = '1';
                $archive->id_no = $row['id_no'];
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
                $archive->id_no_front_image = $row['id_no_front_image'];
                $archive->id_no_backend_image = $row['id_no_backend_image'];
                $archive->verify_status = $row['verify_status'];
                $archive->user_id = $user->id;
                
            }elseif($row['typedata'] === '2'){
                // 企业档案
                $archive->typedata = '2';
                $archive->id_no = $row['id_no'];
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
                $archive->id_no_front_image = $row['id_no_front_image'];
                $archive->id_no_backend_image = $row['id_no_backend_image'];
                $archive->verify_status = $row['verify_status'];
                $archive->user_id = $user->id;
                $archive->company_name =  $row['company_name'];
                $archive->company_id_no =  $row['company_id_no'];
                $archive->company_id_no_image =  $row['company_id_no_image'];
                $archive->company_attachfile =  $row['company_attachfile'];
                
            }
            $r2 = $archive->save();
            if(!$r2){
                // 用户添加失败-回滚
                throw new Exception("用户添加失败");
            }
            
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            // $this->error($e->getMessage());
            return [
                'code' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'code' => 1,
            'msg' => '添加成功'
        ];

    }

    /**
     * 用户审核/修改
     */
    public function vertifyUser($row,$user_id){

        #1.用户档案
        #2.用户数据
        $user = User::where('id',$user_id)->find();
        $archive = UserArchive::where('user_id',$user_id)->order('id','desc')->find();
        // $mobile = isset($mobile)
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            // 添加用户账号-默认密码为手机号
            // $user->status = "normal";
            // $user->typedata = $row['typedata'];
            $user->username = $row['mobile'];
            $user->mobile = $row['mobile'];
            // $salt = \fast\Random::alnum();
            // $user->password = \app\common\library\Auth::instance()->getEncryptPassword($row['mobile'], $salt);
            // $user->salt = $salt;
            $user->id_no = $row['id_no'];
            $user->id_no_name = $row['id_no_name'];
            $user->id_no_front_image = $row['id_no_front_image'];
            $user->id_no_backend_image = $row['id_no_backend_image'];
            $user->verify_status = $row['verify_status'];// 默认用户已通过审核

            $user->company_name =  $row['company_name'];
            $user->company_id_no =  $row['company_id_no'];
            $user->company_id_no_image =  $row['company_id_no_image'];
            $user->company_attachfile =  $row['company_attachfile'];
            $user->company_bank_name =  $row['company_bank_name'];
            $user->company_bank_id =  $row['company_bank_id'];
            
            $r1 = $user->save();
            if(!$r1){
                // 用户添加失败-回滚
                throw new Exception("审核失败");
            }
            
            // 添加档案
            if($archive->typedata === '1'){
                // 用户档案
                // $archive->typedata = '1';
                $archive->id_no = $row['id_no'];
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
                $archive->id_no_front_image = $row['id_no_front_image'];
                $archive->id_no_backend_image = $row['id_no_backend_image'];
                $archive->verify_status = $row['verify_status'];
                // $archive->user_id = $user->id;
                $archive->company_name =  "";
                $archive->company_id_no =  "";
                $archive->company_id_no_image =  "";
                $archive->company_attachfile =  "";
                $archive->company_bank_name =  "";
                $archive->company_bank_id =  "";
                
            }elseif($archive->typedata === '2'){
                // 企业档案
                // $archive->typedata = '2';
                $archive->id_no = $row['id_no'];
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
                $archive->id_no_front_image = $row['id_no_front_image'];
                $archive->id_no_backend_image = $row['id_no_backend_image'];
                $archive->verify_status = $row['verify_status'];
                // $archive->user_id = $user->id;
                $archive->company_name =  $row['company_name'];
                $archive->company_id_no =  $row['company_id_no'];
                $archive->company_id_no_image =  $row['company_id_no_image'];
                $archive->company_attachfile =  $row['company_attachfile'];
                $archive->company_bank_name =  $row['company_bank_name'];
                $archive->company_bank_id =  $row['company_bank_id'];
                
            }
            if($row['verify_status'] === '1'){
                // 审核通过
                $archive->refuse_reason = "";
            }else{
                // 拒绝审核
                $archive->refuse_reason = $row['refuse_reason'];
            }
            $r2 = $archive->save();
            if(!$r2){
                // 用户添加失败-回滚
                throw new Exception("审核失败");
            }
            
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            // $this->error($e->getMessage());
            return [
                'code' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'code' => 1,
            'msg' => '操作成功'
        ];

    }

    /**
     * 用户审核/通过
     */
    public function vertifyPass($row,$user_id){

        #1.用户档案
        #2.用户数据
        $user = User::where('id',$user_id)->find();
        $archive = UserArchive::where('user_id',$user_id)->order('id','desc')->find();
        // $mobile = isset($mobile)
        Db::startTrans();
        try {
            
            $user->verify_status = '1';// 默认用户已通过审核
            $user->updatetime = time();
            $r1 = $user->save();
            if(!$r1){
                // 用户添加失败-回滚
                throw new Exception("审核失败");
            }
            
            // 添加档案
            $archive->verify_status = '1';
            $archive->refuse_reason = "";
            $archive->pass_time = time();
            $r2 = $archive->save();
            if(!$r2){
                // 用户添加失败-回滚
                throw new Exception("审核失败");
            }
            
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            // $this->error($e->getMessage());
            return [
                'code' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'code' => 1,
            'msg' => '操作成功'
        ];

    }

    /**
     * 用户审核/通过
     */
    public function vertifyNopass($row,$user_id){

        #1.用户档案
        #2.用户数据
        $user = User::where('id',$user_id)->find();
        $archive = UserArchive::where('user_id',$user_id)->order('id','desc')->find();
        // $mobile = isset($mobile)
        // dump($archive );die;
        if(!$archive){
            return [
                'code' => 0,
                'msg' => "用户档案不存在"
            ];
        }
        Db::startTrans();
        try {
            
            $user->verify_status = '2';// 默认用户已通过审核
            $user->updatetime = time();
            $r1 = $user->save();
            if(!$r1){
                // 用户添加失败-回滚
                throw new Exception("审核失败1");
            }
            
            // 添加档案
            $archive->verify_status = '2';
            $archive->refuse_reason = $row['refuse_reason'];
            $archive->updatetime = time();
            $r2 = $archive->save();
            if(!$r2){
                // 用户添加失败-回滚
                throw new Exception("审核失败2");
            }
            
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            // $this->error($e->getMessage());
            return [
                'code' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'code' => 1,
            'msg' => '操作成功'
        ];

    }

    public function tags($type = '1', $level = '1', $pid = 0){

        // 获取一级标签列表
        $tags = Tag::where('type',$type)->field('*,0 as sel')->where('level',$level)->where('pid',$pid)->order('sort','desc')->order('id','asc')->select();

        if($level > 1){
            $top = Tag::where('id',$pid)->find();
            // 添加全部选项
            $allOption = [
                'id' => $pid,
                'name' => $top->name."(全部)",
                'path' => $top->path,
                'sel'  => 1, //1-可选,0-不可选
                'level' => $top->level,
                'pid'  => $top->pid,
                'type'  => $top->type
            ];
            $tags = array_merge([$allOption],$tags);
            if($level > 2){

                foreach($tags as &$v){
                    $v['sel'] = 1;
                }
            }
        }
        return $tags;

    }

    public function search($where = []){

        $top = Tag::where($where)->field('*,0 as sel')->find();

        return $top;
    }
}