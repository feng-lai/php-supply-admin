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
use app\admin\model\Requirement;
use app\common\model\RequirementSpecialist;


class RequirementService extends BaseService
{


    

    /**
     * 后台增加
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
     * 审核/修改
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
     * 审核/通过
     */
    public function vertifyPass($ids){

        $requirement = Requirement::where('id',$ids)->find();
        // dump($requirement);die;
        #1.更改需求状态
        $requirement->status = '1';
        #2.更新预约信息
        if(!$requirement){
            return [
                'code' => 0,
                'msg' => '需求不存在'
            ];
        }
        // $mobile = isset($mobile)
        Db::startTrans();
        try {
            setPostMessage(3,$requirement->user_id,'您提交的需求已审核通过，点击查看详情','/myNeed/detail?status=1&id='.$requirement->id);
            if($requirement->specialist_user_id&&(intval($requirement->specialist_user_id)>0)){
                //有预约的专家
                $rs_model = new RequirementSpecialist();
                $rs_model->requirement_id = $requirement->id;
                $rs_model->user_id = $requirement->specialist_user_id;
                $rs_model->desc = '';
                $rs_model->type = '2';
                $rs_model->files = '';
                $rs_model->status = '0';
                $rs_model->save();
                $requirement->status = '2';
            }
            $requirement->save();


            
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
     * 审核/不通过
     */
    public function vertifyNopass($ids,$refuse_reason=''){

        $requirement = Requirement::where('id',$ids)->find();
        #1.更改需求状态
        $requirement->status = '6';
        $requirement->refuse_reason = $refuse_reason;
        #2.更新预约信息
        if(!$requirement){
            return [
                'code' => 0,
                'msg' => '需求不存在'
            ];
        }
        // $mobile = isset($mobile)
        Db::startTrans();
        try {
            setPostMessage(3,$requirement->user_id,'您提交的需求审核未通过，点击查看详情','/myNeed/detail?status=6&id='.$requirement->id);
            $requirement->save();
            
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
}