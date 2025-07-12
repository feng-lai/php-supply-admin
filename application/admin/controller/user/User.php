<?php

namespace app\admin\controller\user;

use app\admin\model\UserArchive;
use app\common\controller\Backend;
use app\common\library\Auth;

use app\admin\service\UserService;
use think\Db;
use think\exception\PDOException;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{
    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';
    protected $noNeedRight = ['vertifyNopass','vertifyPass','vertifylist','vertifyEnterprise'];

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("typedataList", $this->model->getTypedataList());
        $this->view->assign("verifyStatusList", $this->model->getVerifyStatusList());

        $model = new \app\admin\model\Requirement;
        $this->view->assign("requirementStatusList", $model->getStatusList());

        $order_model = new \app\admin\model\order\Order;
        $this->view->assign("orderStatusList", $order_model->getStatusList());
    }


    /**
     * 查看
     */
    public function index($type='')
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $param = $this->request->param();

            $filter = json_decode($param['filter'],true);
            //print_r($filter);exit;

            $where = [];

            if(!empty($type)){
                $this->model->where('u.typedata','=',(string)$type);
            }

            if(isset($filter['keyword'])){
                $this->model->where('u.id|u.nickname|u.id_no_name|u.company_name|u.mobile','like','%'.$filter['keyword'].'%');
            }

            if(isset($filter['createtime'])){
                $timeArr = explode(' - ', $filter['createtime']);
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $this->model->whereTime('u.createtime','between',[$begin,$end]);
                }
            }

            if(isset($filter['typedata']) and !empty($filter['typedata'])){
                $this->model->where('u.typedata','=',$filter['typedata']);
            }
            if(isset($filter['status']) and !empty($filter['status'])){
                $this->model->where('u.status','=',(string)$filter['status']);
            }

            $list = $this->model
                ->alias("u")
                ->where($where)
                ->where('role_type','1')
                ->order('u.id desc')
                ->paginate($limit);

            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    public function check_id_no_unique(){
        $row = $this->request->post('row/a');
        if($this->model->where('id_no',$row['id_no'])->count()){
            $this->error("身份证号码已存在");
        }
        $this->success();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
            $row = $this->request->post('row/a');
            $service = new UserService();
            $res = $service->addUser($row);
            return json($res);
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            //$row = $this->request->post('row/a');
            //$service = new UserService();
            //$res = $service->editUser($row,$ids);
            //return json($res);
            return json([
                'code' => 1,
                'msg' => ''
            ]);
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('row', $row);
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

    /**
     * 审核
     */
    public function vertifylist($type=null)
    {
        $this->model = new UserArchive();
        $this->assignconfig('type', $type);
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            // dump("vertfylist");die;
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                
                return $this->selectpage();
            }
            $param = $this->request->param();
            $filter = json_decode($param['filter'],true);
            $op = json_decode($param['op'],true);
            if(isset($filter['id_no_name']) && $filter['id_no_name']){
                $this->model->where('user_archive.id_no_name|user_archive.company_name','like','%'.$filter['id_no_name'].'%');
                unset($filter['id_no_name'],$op['id_no_name']);
            }

            if(isset($filter['id']) && $filter['id']){
                $this->model->where('user.id',$filter['id']);
                unset($filter['id'],$op['id']);
            }
            $this->request->get(['filter'=>json_encode($filter,true)]);
            $this->request->get(['op'=>json_encode($op,true)]);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            if($type == '2'){
                $this->model->where("user.typedata = '2'");
            }
            $list = $this->model
                ->join("user",'user_archive.user_id = user.id')
                ->where($where)
                ->where('role_type','1')
                ->field("user_archive.*,user.typedata,user.mobile,user.enterprise_status,user.id,user_archive.id as ac_id")
                ->order("updatetime desc")
                ->paginate($limit);
//            var_dump($this->model->getLastSql());die;
            $result = array("total" => $list->total(), "rows" => $list->items());

            // dump($result);die;
            return json($result);
        }
        // dump("vertfylist");die;
        return $this->view->fetch();
    }

    /**
     * 用户详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->get(['id' => $ids])->toArray();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row['auth_status'] = Db::name("user")->where(['id' => $row['id']])->value("enterprise_status");
        $row['province_name'] = Db::name("area")->where(['id' => $row['province_id']])->value("name");
        $row['city_name'] = Db::name("area")->where(['id' => $row['city_id']])->value("name");
        $row['district_name'] = Db::name("area")->where(['id' => $row['district_id']])->value("name");
        $row['auth_createtime'] = Db::name("user_archive")->where(['user_id' => $row['id']])->value("createtime");
        if ($this->request->isAjax()) {
            $this->success("Ajax请求成功", null, ['id' => $ids]);
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 批量更新
     *
     * @param $ids
     * @return void
     */
    public function multi($ids = null){
        if (false === $this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        if (false === $this->request->has('params')) {
            $this->error(__('No rows were updated'));
        }
        parse_str($this->request->post('params'), $values);

        //$values = $this->auth->isSuperAdmin() ? $values : array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
        //if (empty($values)) {
            //$this->error(__('You have no permission'));
        //}
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
            foreach ($list as $item) {
                $log = new \app\common\model\UserDisableLog();
                //保存禁用记录
                if(isset($values['status']) && $values['status'] == 'locked' && !$log->where('user_id',$item->id)->whereTime('disable_time', 'today')->count()){
                    $log->user_id = $item->id;
                    $log->typedata = $item->typedata;
                    $log->role_type = $item->role_type;
                    $log->disable_time = time();
                    $log->save();
                }
                $count += $item->allowField(true)->isUpdate(true)->save($values);

            }

            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }

    /**
     * 审核详情
     */
    public function vertifydetail($ids = null)
    {
        $is = $this->request->get('is','');
        $vertify = new UserArchive();
        $row = $vertify->get(['user_id' => $ids]);
        if(!$row['mobile']){
            $row['mobile'] = \app\admin\model\User::where('id',$ids)->value('mobile');
        }

        $row['auth_status'] = Db::name("user_archive")->where(['user_id' => $row['user_id']])->value("verify_status");
        if ($this->request->isPost()) {
            // $this->token();
            $row = $this->request->post('row/a');
            $service = new UserService();

            if($row['verify_status'] == "1"){
                $res = $service->vertifyPass($row,$ids);
            }else{
                $res = $service->vertifyNopass($row,$ids);
            }

            return json($res);
            $this->success("Ajax请求成功", null, ['id' => $ids]);

        }

//        $row = $this->model->get($ids);


        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));


        // dump($row->toArray() );die;
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isAjax()) {
            $this->success("Ajax请求成功", null, ['id' => $ids]);
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));

        $row['user'] = Db::name("user")->where(['id' => $row['user_id']])->find();

        $row['province_name'] = Db::name("area")->where(['id' => $row['user']['province_id']])->value("name");
        $row['city_name'] = Db::name("area")->where(['id' => $row['user']['city_id']])->value("name");
        $row['district_name'] = Db::name("area")->where(['id' => $row['user']['district_id']])->value("name");

        $this->view->assign("row", $row);
        $data = Db::name("user_save")->where("user_id",$row['user_id'])->select();
        $this->view->assign('data',$data);
        $this->view->assign('is',$is);
        return $this->view->fetch();
    }


    public function vertifyEnterprise($ids = null)
    {
        $this->model = new UserArchive();
        $row = $this->model->get(['id' => $ids]);


        if ($this->request->isPost()) {
            $user = model('User')->get(['id' => $row->user_id]);
            $user->company_name =  $row->company_name;
            $user->company_id_no =  $row->company_id_no;
            $user->company_id_no_image =  $row->company_id_no_image;
            $user->company_attachfile =  $row->company_attachfile;
            $user->company_bank_name =  $row->company_bank_name;
            $user->company_bank_id =  $row->company_bank_id;
            $user->enterprise_status = 1;
            $user->nickname = $row->nickname;
            $user->avatar = $row->avatar;

            $user->save();
            $this->model->where('id',$ids)->update(['verify_status'=>1]);

            return json([
                'code' => 1,
                'msg' => '操作成功'
            ]);
            $this->success("Ajax请求成功", null, ['id' => $ids]);

        }

    }
    /**
     * 审核-通过
     */
    public function vertifyPass($ids = null)
    {
        $row = $this->model->get(['id' => $ids]);

        if ($this->request->isPost()) {
            $row = $this->request->post();
            $service = new UserService();
            $res = $service->vertifyPass($row,$ids);
            return json($res);
            $this->success("Ajax请求成功", null, ['id' => $ids]);

        }

    }
    /**
     * 审核-不通过
     */
    public function vertifyNopass($ids = null)
    {
        // $row = $this->model->get(['id' => $ids]);
       

        if ($this->request->isPost()) {

            $row = $this->request->post();
            // dump($ids);die;
            // return $row;
            $service = new UserService();
            $res = $service->vertifyNopass($row,$ids);
            return json($res);
            $this->success("Ajax请求成功", null, ['id' => $ids]);

        }

    }

}
