<?php
namespace app\admin\service;

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


class UserService extends BaseService
{
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
            $is_user = Db::name("user")->where(['mobile' => $row['mobile']])->find();
            if($is_user){
                Db::rollback();
                throw new Exception("手机号已存在");
            }
            // 添加用户账号-默认密码为手机号
            $user->status = "normal";
            $user->typedata = $row['typedata'];
            $is_nickname = Db::name("user")->where(['nickname' => $row['nickname']])->find();
            if($is_nickname){
                Db::rollback();
                throw new Exception("昵称已存在");
            }
            $user->nickname = $row['nickname'];
            $user->username = $row['mobile'];
            $user->mobile = $row['mobile'];
            $salt = \fast\Random::alnum();
            $user->password = \app\common\library\Auth::instance()->getEncryptPassword($row['password'], $salt);
            $user->salt = $salt;

            $user->id_no_name = $row['id_no_name'];

            $user->verify_status = $row['verify_status'];// 默认用户已通过审核

            $user->company_name =  $row['company_name'];
            $user->company_id_no =  $row['company_id_no'];

            $company_id_no = Db::name("user")->where(['company_id_no' => $row['company_id_no']])->find();
            if($company_id_no && $row['typedata'] === '2' && $row['company_id_no']){
                Db::rollback();
                throw new Exception("营业执照号已存在");
            }

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
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
                $archive->verify_status = $row['verify_status'];
                $archive->user_id = $user->id;
                
            }elseif($row['typedata'] === '2'){
                // 企业档案
                $archive->typedata = '2';
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
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
     * 后台编辑用户
     */
    public function editUser($row,$id){

        #1.新增用户档案
        #2.新增用户数据
        $user = new User();
        $archive = UserArchive::where('user_id',$id)->find();
        if(!$archive){
            $archive = new UserArchive();
        }
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $is_user = Db::name("user")->where(['mobile' => $row['mobile']])->where('id','<>',$id)->find();
            if($is_user){
                Db::rollback();
                throw new Exception("手机号已存在");
            }
            $is_nickname = Db::name("user")->where(['nickname' => $row['nickname']])->where('id','<>',$id)->find();
            if($is_nickname){
                Db::rollback();
                throw new Exception("昵称已存在");
            }

            //$company_id_no = Db::name("user")->where(['company_id_no' => $row['company_id_no']])->where('id','<>',$id)->find();
            //if($company_id_no && $row['typedata'] === '2'){
                //Db::rollback();
                //throw new Exception("营业执照号已存在");
            //}

            $r1 = $user->save($row,['id'=>$id]);
            if(!$r1){
                // 用户编辑失败-回滚
                throw new Exception("用户编辑失败");
            }

            // 添加档案
            if($row['typedata'] === '1'){
                // 用户档案
                $archive->typedata = '1';
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
                $archive->verify_status = $row['verify_status'];
                $archive->user_id = $id;

            }elseif($row['typedata'] === '2'){
                // 企业档案
                $archive->typedata = '2';
                $archive->mobile = $row['mobile'];
                $archive->id_no_name = $row['id_no_name'];
                $archive->verify_status = $row['verify_status'];
                $archive->user_id = $id;
                $archive->company_name =  $row['company_name'];
                //$archive->company_id_no =  $row['company_id_no'];
                //$archive->company_id_no_image =  $row['company_id_no_image'];
                //$archive->company_attachfile =  $row['company_attachfile'];

            }
            $r2 = $archive->save();
            if(!$r2){
                // 用户添加失败-回滚
                //throw new Exception("用户编辑失败");
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
            'msg' => '编辑成功'
        ];

    }

    /**
     * 用户审核/修改
     */
    public function vertifyUser($row,$user_id){

//        dump($row);die;
        #1.用户档案
        #2.用户数据
        $user = User::where('id',$row['id'])->find();
        $archive = UserArchive::where('id',$user_id)->order('id','desc')->find();

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

            // $user->nickname = $archive->nickname;
            // $user->avatar = $archive->avatar;
            // $user->province_id = $archive->province_id;
            // $user->city_id = $archive->city_id;
            // $user->district_id = $archive->district_id;

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
        // dump($user);die;
        if(!$archive){
            return [
                'code' => 0,
                'msg' => '用户档案不存在：无认证信息'
            ];
        }
        // $mobile = isset($mobile)
        Db::startTrans();
        try {
            //setPostMessage(1,$user_id,'您提交绑定为'.$archive->company_name.'审核已通过','');
            setPostMessage(1,$user_id,'您提交的需求方信息修改审核已通过','');
            $user->verify_status = '1';// 默认用户已通过审核
            $user->updatetime = time();
            //if($archive->mobile){

                $user->company_name =  $archive->company_name;
                $user->company_id_no =  $archive->company_id_no;
                $user->company_id_no_image =  $archive->company_id_no_image;
                $user->company_attachfile =  $archive->company_attachfile;
                $user->company_bank_name =  $archive->company_bank_name;
                $user->company_bank_id =  $archive->company_bank_id;
                $user->enterprise_status = 1;

                $user->username = $archive->username;
                $archive->mobile?$user->mobile = $archive->mobile:"";
                $user->nickname = $archive->nickname;
                $user->avatar = $archive->avatar;
                $user->province_id = $archive->province_id;
                $user->city_id = $archive->city_id;
                $user->district_id = $archive->district_id;

                $user->id_no = $archive->id_no;
                $user->id_no_name = $archive->id_no_name;
                $user->id_no_front_image = $archive->id_no_front_image;
                $user->id_no_backend_image = $archive->id_no_backend_image;
                $user->id_no_bank_name = $archive->id_no_bank_name;
                $user->id_no_bank_id = $archive->id_no_bank_id;

                $user->address = $archive->address;
           //}

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
            //setPostMessage(1,$user_id,'您提交绑定为'.$archive->company_name.'审核不通过','');
            setPostMessage(1,$user_id,'您提交的需求方信息修改审核未通过','');
            //$user->enterprise_status = 2;
            //$user->verify_status = '2';// 默认用户已通过审核
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

    /**
     * 用户专家审核/通过
     */
    public function vertifySpecialistPass($row,$user_id){

        #1.用户档案
        #2.用户数据
        $specialist_id = $row['id'];
        $user = User::where('id',$user_id)->find();
        $archive = Specialist::where('user_id',$user_id)->where('id',$specialist_id)->order('id','desc')->find();
        // dump($user);die;
        if(!$archive){
            return [
                'code' => 0,
                'msg' => '用户档案不存在：无认证信息'
            ];
        }
        // $mobile = isset($mobile)
        Db::startTrans();
        try {

            $user->verify_status = '1';// 默认用户已通过审核
            $user->updatetime = time();
        
            // $user->username = $archive->mobile;
            // $user->mobile = $archive->mobile;
            $user->nickname = $archive->nickname;
            $user->avatar = $archive->avatar;
            $user->province_id = $archive->province_id;
            $user->city_id = $archive->city_id;
            $user->district_id = $archive->district_id;

            $user->id_no = $archive->id_no;
            $user->id_no_name = $archive->name;
            $user->id_no_front_image = $archive->id_no_front_image;
            $user->id_no_backend_image = $archive->id_no_backend_image;
            $user->id_no_bank_name = $archive->id_no_bank_name;
            $user->id_no_bank_id = $archive->id_no_bank_id;



            $r1 = $user->save();
            if(!$r1){
                // 用户添加失败-回滚
                throw new Exception("审核失败");
            }
            
            // 添加档案
            $archive->status = '1';
            // $archive->refuse_reason = "";
            $archive->updatetime = time();
            $r2 = $archive->save();
            if(!$r2){
                // 用户添加失败-回滚
                throw new Exception("审核失败");
            }
            
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            // dump($e->getMessage());
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
}