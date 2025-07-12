<?php

namespace app\admin\controller;

use app\admin\controller\general\Config;
use app\admin\model\User;
use app\admin\model\UserArchive;
use app\common\controller\Backend;
use app\admin\service\SpecialistService;
use app\admin\service\UserService;
use app\common\model\Tag;
use app\common\model\Tag as TagModel;
use app\common\model\SpecialistAuth as SpecialistAuthModel;
use app\common\model\OrderComment;
use app\common\model\Specialist as SpecialistModel;
use app\common\model\TagSpecialist;
use fast\Tree;
use think\Db;
use think\exception\PDOException;

/**
 * 专家信息
 *
 * @icon fa fa-circle-o
 */
class Specialist extends Backend
{

    /**
     * Specialist模型对象
     * @var \app\admin\model\Specialist
     */
    protected $model = null;

    protected $noNeedRight = ['multi2', 'getTagList', 'seltag', 'search', 'vslist', 'pass', 'detail'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Specialist;
        $this->view->assign("statusList", $this->model->getStatusList());

        $model = new \app\admin\model\Requirement;
        $this->view->assign("requirementStatusList", $model->getStatusList());

        $order_model = new \app\admin\model\order\Order;

        $this->view->assign("orderStatusList", $order_model->getStatusList());
    }

    /**
     * 专家查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {

            $this->view->assign("industry", $this->getTagList(1));
            $this->view->assign("skill", $this->getTagList(2));
            $this->view->assign("area", $this->getTagList(3));
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $where = [];
        $param = $this->request->param();
        $filter = json_decode($param['filter'], true);
        $model = $this->model;
        if (isset($filter['keywords'])) {
            $model->whereOr('s.name', 'like', "%{$filter['keywords']}%")
                ->whereOr('s.nickname', 'like', "%{$filter['keywords']}%")
                ->whereOr('u.mobile', 'like', "%{$filter['keywords']}%")
                ->whereOr('s.id', $filter['keywords']);
        }
        if (isset($filter['createtime'])) {
            $timeArr = explode(' - ', $filter['createtime']);
            if (count($timeArr) == 2) {
                $begin = strtotime($timeArr[0]);
                $end = strtotime($timeArr[1]);
                $model = $model->whereTime('s.createtime', 'between', [$begin, $end]);
            }
        }
        if (isset($filter['tag'])) {
            $filter['tag'] = str_replace('(全部)', '', $filter['tag']);
            $tag_name = explode(",", $filter['tag']);
            $tag = Db::name("tag")->where('name', 'in', $tag_name)->select();
            $industry_ids = [];
            $skill_ids = [];
            $area_ids = [];

            foreach ($tag as $key => $val) {
                if ($val['type'] === '1') {
                    $industry_ids[] = $val['id'];
                    foreach ($industry_ids as $parent_id) {
                        getAllChildTags($parent_id, $industry_ids);
                    }
                    $industry_ids = array_unique(array_merge($industry_ids, getAllParentTagsId($val['id'])));
                } else if ($val['type'] === '2') {
                    $skill_ids[] = $val['id'];
                    foreach ($skill_ids as $parent_id) {
                        getAllChildTags($parent_id, $skill_ids);
                    }
                    $skill_ids = array_unique(array_merge($skill_ids, getAllParentTagsId($val['id'])));
                } else if ($val['type'] === '3') {
                    $area_ids[] = $val['id'];
                    foreach ($area_ids as $parent_id) {
                        getAllChildTags($parent_id, $area_ids);
                    }
                    $area_ids = array_unique(array_merge($area_ids, getAllParentTagsId($val['id'])));
                }
            }

            if (!empty($industry_ids)) {
                $filter['industry_ids'] = $industry_ids;
            }
            if (!empty($skill_ids)) {
                $filter['skill_ids'] = $skill_ids;
            }
            if (!empty($area_ids)) {
                $filter['area_ids'] = $area_ids;
            }

        }

        if (isset($filter['industry_ids']) and $filter['industry_ids'][0] > 0) {
            $files = $filter['industry_ids'];
            $model = $model->where(function ($query) use ($files) {
                foreach ($files as $val) {
                    $query->whereOr("FIND_IN_SET('{$val}', s.industry_ids)");
                }
            });
        }

        if (isset($filter['skill_ids']) and $filter['skill_ids'][0] > 0) {
            $files = $filter['skill_ids'];
            $model = $model->where(function ($query) use ($files) {
                foreach ($files as $val) {
                    $query->whereOr("FIND_IN_SET('{$val}', s.skill_ids)");
                }
            });
        }

        if (isset($filter['area_ids']) and $filter['area_ids'][0] > 0) {
            $files = $filter['area_ids'];
            $model = $model->where(function ($query) use ($files) {
                foreach ($files as $val) {
                    $query->whereOr("FIND_IN_SET('{$val}', s.area_ids)");
                }
            });
        }


        if (isset($filter['status'])) {
            $files = $filter['status'];
            $model = $model->where('s.status', '=', $files);
        }

        if (isset($filter['rate1'])) {
            $model->having('IFNULL(AVG(c.points), 0) >= ' . $filter['rate1'] . ' AND IFNULL(AVG(c.points), 0) <= ' . $filter['rate2']);
        }

        $list = $model->alias('s')
            ->join('user u', 'u.id = s.user_id')
            ->join('fa_order_comment c', 's.user_id = c.to_user_id', 'left')
            ->field('s.*,s.nickname as user_nickname,u.mobile,ROUND(IFNULL(AVG(c.points), 0)) as avg_score')
            ->group('s.user_id')
            // ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    public function getTagList($type)
    {
        $tagList = collection(Tag::where('type', $type)->select())->toArray();
        foreach ($tagList as $k => &$v) {
            $v['name'] = __($v['name']);
        }
        unset($v);
        Tree::instance()->init($tagList);
        $tagList = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0), 'name');
        $result = [0 => "全部"];
        foreach ($tagList as $k => &$v) {
            $result[$v['id']] = $v['name'];
        }
        return $result;
    }

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function vertifylist()
    {
//        $this->model = new UserArchive();
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = SpecialistAuthModel::where($where)
            ->order('createtime', 'desc')
            ->paginate($limit);
        foreach ($list as $k => $v) {
            $name = SpecialistModel::where('user_id', $v->user_id)->value('name');
            $list[$k]->name = $name ? $name : $v->name;
            $list[$k]->createtime = date('Y-m-d H:i:s', $v->createtime);
        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 详情
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $uid = $this->request->post("uid");
            $level_ids = $this->request->post("level_ids");
            Db::name("specialist")->where(['user_id' => $uid])->update(['level_ids' => $level_ids]);
            return json([
                'code' => 1,
                'msg' => '操作成功'
            ]);
        }
        $row = $this->model->alias('s')
            ->join('user u', 'u.id = s.user_id')
            ->field('s.*,u.username,u.mobile,u.createtime,u.logintime')
            ->where('s.id', $ids)
            ->group('s.user_id')
            ->find();
        $this->modelValidate = false;


        $row['skill'] = getAllParentTags($row->skill_ids);
        $row['industry'] = getAllParentTags($row->industry_ids);
        $row['area'] = getAllParentTags($row->area_ids);

        $auth_time = SpecialistAuthModel::where('user_id', $row['user_id'])->value('createtime');
        $row['auth_time'] = $auth_time ? date('Y-m-d H:i:s', $auth_time) : '';

        $row['province_name'] = Db::name("area")->where(['id' => $row['province_id']])->value("name");
        $row['city_name'] = Db::name("area")->where(['id' => $row['city_id']])->value("name");
        $row['district_name'] = Db::name("area")->where(['id' => $row['district_id']])->value("name");

        $row['good'] = OrderComment::where('to_user_id|user_id', $row['user_id'])->where("points", 'in', '4,5')->count();

        $row['avg'] = OrderComment::where('to_user_id|user_id', $row['user_id'])->avg('points');


        $case_json = isset($row->case_json) && $row->case_json ? $row->case_json : [];
        foreach ($case_json as $key => &$val) {
            $val = is_array($val) ? $val : json_decode($val, true);
        }
        $row->case_json = $case_json;
        if ($this->request->isPost()) {
            $user_id = $row['user_id'];

            $userService = new UserService();
            $userService->vertifySpecialistPass($row, $user_id);
        }
        $this->view->assign('specialist', $row);

        return parent::edit($row['id']);
    }


    /**
     * 选择标签
     */
    public function seltag()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $post = $this->request->param();
        $service = new SpecialistService();
        $type = isset($post['type']) ? $post['type'] : "1";
        $level = isset($post['level']) ? $post['level'] : "1";
        $pid = isset($post['pid']) ? $post['pid'] : 0;
        $tags = $service->tags($type, $level, $pid);
        // dump($post );die;
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }


            return $this->success("获取成功", "", $tags);
        }

        $tagModel = new \app\admin\model\Tag;
        $this->view->assign("type", $type);
        $this->view->assign("levelList", $tagModel->getLevelList());
        $this->view->assign("typeList", $tagModel->getTypeList());
        $this->view->assign("tags", $tags);

        return $this->view->fetch();
    }

    public function search()
    {
        $post = $this->request->param();
        $service = new SpecialistService();
        $where['name'] = ['like', "%" . $post['name'] . "%"];
        $tags = $service->search($where);
        return $this->success("获取成功", "", $tags);
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            // $this->token();
            // dump($this->request->post());die;
            // 注册用户
            $data = $this->request->post();
            $service = new SpecialistService();

            //判断昵称不能重复 nickname
            if (SpecialistModel::where('nickname', $data['row']['nickname'])->count()) {
                $this->error(__('昵称已存在'));
            }
            $arr = json_decode($data['row']['certificate_json'], true);
            if (!count($arr)) {
                $this->error(__('请添加资质证书'));
            }
            foreach ($arr as &$v) {
                $certifitime = explode('-', $v['certifitime']);
                $certifitime[0] = trim(str_replace('/', '-', $certifitime[0]));
                $certifitime[1] = trim(str_replace('/', '-', $certifitime[1]));
                $v['certifitime'] = $certifitime;
            }
            $data['row']['certificate_json'] = json_encode($arr,JSON_UNESCAPED_UNICODE);
            $res = $service->add($data['row']);
            return json($res);
        }
        return parent::add();
    }

    public function check_id_no_unique()
    {
        $row = $this->request->post('row/a');
        if ($this->model->where('id_no', $row['id_no'])->count()) {
            $this->error("身份证号码已存在");
        }
        $this->success();
    }

    /**
     * 编辑
     */
    public function detail($ids = null)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post()['row'];
            $info = SpecialistModel::where(['user_id' => $data['user_id']])->find();
            Db::name("user_save")->where(['user_id' => $data['user_id']])->delete();
            $SpecialistModel = SpecialistAuthModel::where(['user_id' => $data['user_id']])->find();
            if ($SpecialistModel) {
                $SpecialistModel->createtime = time();
                $SpecialistModel->updatetime = time();
            } else {
                $SpecialistModel = new SpecialistAuthModel();
                $SpecialistModel->name = $info->name;
                $SpecialistModel->wechat = $info->wechat;
                $SpecialistModel->addr = $info->addr;
                $SpecialistModel->nickname = $info->nickname;
                $SpecialistModel->industry_ids = $info->industry_ids;
                $SpecialistModel->skill_ids = $info->skill_ids;
                $SpecialistModel->area_ids = $info->area_ids;
                $SpecialistModel->keywords_json = $info->keywords_json;
                $SpecialistModel->lowest_price = $info->lowest_price;
                $SpecialistModel->certificate_json = $info->certificate_json;
                $SpecialistModel->edu_json = $info->edu_json;
            }
            $SpecialistModel->user_id = $data['user_id'];
            $SpecialistModel->status = 0;

            if (isset($info->id) and json_encode($info->case_json,JSON_UNESCAPED_UNICODE) != trim($data['case_json'])) {
                user_save($data['user_id'], "服务案例文本:" . json_encode($info->case_json,JSON_UNESCAPED_UNICODE) . "变更" . $data['case_json']);
            }
            $SpecialistModel->case_json = json_decode($data['case_json'], true);

            if (isset($info->id) and json_encode($info->feature_json,JSON_UNESCAPED_UNICODE) != trim($data['feature_json'])) {
                user_save($data['user_id'], "专家特色:" . json_encode($info->feature_json,JSON_UNESCAPED_UNICODE) . "变更" . $data['feature_json']);
            }
            $SpecialistModel->feature_json = json_decode($data['feature_json'], true);

            if (isset($info->id) and $info->level_ids != $data['level_ids']) {
                user_save($data['user_id'], "专家评审等级" . $info->level_ids . "变更" . $data['level_ids']);
            }
            $SpecialistModel->level_ids = $data['level_ids'];


            if (isset($info->id) and $info->intro !== $data['intro']) {
                user_save($data['user_id'], "个人简介" . $info->intro . "变更" . $data['intro']);
            }
            $SpecialistModel->intro = $data['intro'];

            $SpecialistModel->save();
            return [
                'code' => 1,
                'msg' => '成功'
            ];
        }
        $row = $this->model->alias('s')
            ->join('user u', 'u.id = s.user_id')
            ->field('s.*,u.username,u.mobile,u.createtime,u.logintime')
            ->where('s.id', $ids)
            ->group('s.user_id')
            ->find();
        //行业标签
        $in_tag = explode(',', $row->industry_ids);
        $in_arr = [];
        foreach ($in_tag as $v) {
            $name = Tag::get($v);
            if($name){
                if (Tag::where('pid', $v)->count()) {
                    $in_arr[] = ['id' => $v, 'name' => $name->name . '(全部)'];
                } else {
                    $in_arr[] = ['id' => $v, 'name' => $name->name];
                }
            }

        }

        //技能标签
        $sk_tag = explode(',', $row->skill_ids);
        $sk_arr = [];
        foreach ($sk_tag as $v) {
            $name = Tag::get($v);
            if($name){
                if (Tag::where('pid', $v)->count()) {
                    $sk_arr[] = ['id' => $v, 'name' => $name->name . '(全部)'];
                } else {
                    $sk_arr[] = ['id' => $v, 'name' => $name->name];
                }
            }

        }

        //地区标签
        $area_tag = explode(',', $row->area_ids);
        $area_arr = [];
        foreach ($area_tag as $v) {
            $name = Tag::get($v);
            if($name){
                if (Tag::where('pid', $v)->count()) {
                    $area_arr[] = ['id' => $v, 'name' => $name->name . '(全部)'];
                } else {
                    $area_arr[] = ['id' => $v, 'name' => $name->name];
                }
            }

        }
        $certificate_json = $row->certificate_json;
        foreach ($certificate_json as $k => $v) {
            if($v){
                foreach ($v['certifitime'] as $key => $val) {
                    $certificate_json[$k]['certifitime'][$key] = str_replace('-', '/', $val);
                }
            }

        }
        $row->certificate_json = $certificate_json;

        $this->assignconfig('certificate_json', $row->certificate_json);

        $this->view->assign('case_json', json_encode($row->case_json));

        $this->view->assign('edu_json', json_encode($row->edu_json));
        $this->view->assign('feature_json', json_encode($row->feature_json));

        $this->view->assign('certificate_json', json_encode($row->certificate_json));
        $this->view->assign('row', $row);
        $this->view->assign('sk_arr', $sk_arr);
        $this->view->assign('area_arr', $area_arr);
        $this->view->assign('in_arr', $in_arr);
        return $this->view->fetch();
    }

    //审核详情
    public function review_details($ids, $auth = '')
    {
        if ($auth) {
            $row = Db::name("specialist_auth")
                ->alias("a")
                ->join("user b", "a.user_id = b.id", "left")
                ->where(['a.id' => $ids])
                ->field("a.*,b.mobile,b.logintime")
                ->find();
        } else {
            $row = Db::name("specialist")
                ->alias("a")
                ->join("user b", "a.user_id = b.id", "left")
                ->where(['a.id' => $ids])
                ->field("a.*,b.mobile,b.logintime")
                ->find();
        }
        $row['logintime'] = $row['logintime']?date('Y-m-d H:i:s',$row['logintime']):'';

        //行业标签
        $in_tag = explode(',', $row['industry_ids']);
        $in_arr = [];
        foreach ($in_tag as $v) {
            $name = Tag::get($v);
            if($name){
                if (Tag::where('pid', $v)->count()) {
                    $in_arr[] = ['id' => $v, 'name' => $name->name . '(全部)'];
                } else {
                    $in_arr[] = ['id' => $v, 'name' => $name->name];
                }
            }

        }

        //技能标签
        $sk_tag = explode(',', $row['skill_ids']);
        $sk_arr = [];
        foreach ($sk_tag as $v) {
            $name = Tag::get($v);
            if($name){
                if (Tag::where('pid', $v)->count()) {
                    $sk_arr[] = ['id' => $v, 'name' => $name->name . '(全部)'];
                } else {
                    $sk_arr[] = ['id' => $v, 'name' => $name->name];
                }
            }

        }

        //地区标签
        $area_tag = explode(',', $row['area_ids']);
        $area_arr = [];
        foreach ($area_tag as $v) {
            $name = Tag::get($v);
            if($name) {
                if (Tag::where('pid', $v)->count()) {
                    $area_arr[] = ['id' => $v, 'name' => $name->name . '(全部)'];
                } else {
                    $area_arr[] = ['id' => $v, 'name' => $name->name];
                }
            }
        }


        $row['case_json'] = json_decode($row['case_json'], true);
        $row['feature_json'] = json_decode($row['feature_json'], true);

        $row['edu_json'] = json_decode($row['edu_json'], true);
        $certificate_json = json_decode($row['certificate_json'], true);
        if($certificate_json){
            foreach ($certificate_json as $k => $v) {
                if($v){
                    if (is_array($v['certifitime'])) {
                        $certificate_json[$k]['certifitime'] = implode(' 到 ', $v['certifitime']);
                    }
                }else{
                    unset($certificate_json[$k]);
                }
            }
            $row['certificate_json'] = $certificate_json;
        }else{
            $row['certificate_json'] = [];
        }



        $industry = getAllParentTags($row['industry_ids']);

        $skill = getAllParentTags($row['skill_ids']);

        $area = getAllParentTags($row['area_ids']);

        $row['skill'] = implode(',', $skill);

        $row['area'] = implode(',', $area);

        $row['industry'] = implode(',', $industry);

        $row['province_name'] = Db::name("area")->where(['id' => $row['province_id']])->value("name");
        $row['city_name'] = Db::name("area")->where(['id' => $row['city_id']])->value("name");
        $row['district_name'] = Db::name("area")->where(['id' => $row['district_id']])->value("name");

        $this->view->assign('row', $row);
        $data = Db::name("user_save")->where("user_id", $row['user_id'])->select();
        foreach ($data as $k => $v) {
            $content = str_replace('desc','内容',$v['content']);
            $content = str_replace('name','名称',$content);
            $content = str_replace('gender','描述',$content);
            $content = str_replace('idx','序号',$content);
            $content = str_replace('certifiimage','凭证',$content);
            $content = str_replace('certifitime','时间',$content);
            $content = str_replace('certifi_company','机构',$content);
            $data[$k]['content'] = $content;
            if (preg_match('/教育经历/', $v['content'])) {
                $content = str_replace('教育经历:', '', $v['content']);
                $content = explode('变更', $content);
                $before = '';
                foreach (json_decode($content[0], true) as $key => $val) {
                    $before .= '(' . ($key + 1) . ') 学校名称:' . $val['school_name'] . ', 学位:' . $val['degree_name'] . ', 专业/研究方向:' . $val['major_name'] . ', 时间段:' . $val['begin_time'] . '-' . $val['end_time'] . '<br/>';
                }
                $after = '';
                foreach (json_decode($content[1], true) as $key => $val) {
                    $after .= '(' . ($key + 1) . ') 学校名称:' . $val['school_name'] . ', 学位:' . $val['degree_name'] . ', 专业/研究方向:' . $val['major_name'] . ', 时间段:' . $val['begin_time'] . '-' . $val['end_time'] . '<br/>';
                }
                $data[$k]['content'] = '教育经历:<br/>' . $before . '变更<br/>' . $after;
            }
        }
        $this->assignconfig('certificate_json', $row['certificate_json']);
        $this->view->assign('case_json', json_encode($row['case_json']));
        $this->view->assign('feature_json', json_encode($row['feature_json']));
        $this->view->assign('edu_json', json_encode($row['edu_json']));
        $this->view->assign('sk_arr', $sk_arr);
        $this->view->assign('area_arr', $area_arr);
        $this->view->assign('in_arr', $in_arr);
        $this->view->assign('data', $data);
        return $this->view->fetch();
    }

    /**
     * 专家审核
     */
    public function pass($ids = null)
    {
        if ($this->request->isPost()) {
            // $this->token();
        }
        // $row = $this->model->get($ids);

        $row = $this->model->alias('s')
            ->join('user u', 'u.id = s.user_id')
            ->field('s.*,u.username,u.mobile')
            ->where('s.id', $ids)
            ->group('s.id')
            ->find();
        // dump($row->mobile);die;
        $this->modelValidate = false;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $user_id = $row['user_id'];

            $userService = new UserService();
            $res = $userService->vertifySpecialistPass($row, $user_id);
            return json($res);
        }
    }

    /**
     * 专家推荐查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function vslist($ids)
    {

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        // dump($where);die;
        $param = $this->request->param();
        $filter = json_decode($param['filter'], true);
        $model = $this->model;
        if ($ids) {

        }
        if (isset($filter['title'])) {
            $model = $model->where('u.nickname|u.id|u.mobile|u.id_no_name', 'like', '%' . $filter['title'] . '%');
        }
        if (isset($filter['createtime'])) {
            $timeArr = explode(' - ', $filter['createtime']);
            // dump($timeArr);die;
            // $where[] = ['createtime','between',[$timeArr[0],$timeArr[1]]];
            if (count($timeArr) == 2) {
                $begin = strtotime($timeArr[0]);
                $end = strtotime($timeArr[1]);
                $model = $model->whereTime('s.updatetime', 'between', [$begin, $end]);
            }
        }

        if (isset($filter['rate1'])) {
            $model->having('IFNULL(AVG(c.points), 0) >= ' . $filter['rate1'] . ' AND IFNULL(AVG(c.points), 0) <= ' . $filter['rate2']);
        }
        if (isset($filter['num1'])) {
            $model->having('IFNULL(COUNT(d.id), 0) >= ' . $filter['num1'] . ' AND IFNULL(COUNT(d.id), 0) <= ' . $filter['num2']);
        }


        if (isset($filter['tag'])) {
            $filter['tag'] = str_replace('(全部)', '', $filter['tag']);
            $tag_name = explode(",", $filter['tag']);
            $tag = Db::name("tag")->where('name', 'in', $tag_name)->select();
            $industry_ids = [];
            $skill_ids = [];
            $area_ids = [];

            foreach ($tag as $key => $val) {
                if ($val['type'] === '1') {
                    $industry_ids[] = $val['id'];
                    foreach ($industry_ids as $parent_id) {
                        getAllChildTags($parent_id, $industry_ids);
                    }
                } else if ($val['type'] === '2') {
                    $skill_ids[] = $val['id'];
                    foreach ($skill_ids as $parent_id) {
                        getAllChildTags($parent_id, $skill_ids);
                    }
                } else if ($val['type'] === '3') {
                    $area_ids[] = $val['id'];
                    foreach ($area_ids as $parent_id) {
                        getAllChildTags($parent_id, $area_ids);
                    }
                }
            }
            if (!empty($industry_ids)) {
                $filter['industry_ids'] = $industry_ids;
            }
            if (!empty($skill_ids)) {
                $filter['skill_ids'] = $skill_ids;
            }
            if (!empty($area_ids)) {
                $filter['area_ids'] = $area_ids;
            }

        }


        if (isset($filter['industry_ids']) and $filter['industry_ids'][0] > 0) {
            $files = $filter['industry_ids'];
            $model = $model->where(function ($query) use ($files) {
                foreach ($files as $val) {
                    $query->whereOr("FIND_IN_SET('{$val}', industry_ids)");
                }
            });
        }

        if (isset($filter['skill_ids']) and $filter['skill_ids'][0] > 0) {
            $files = $filter['skill_ids'];
            $model = $model->where(function ($query) use ($files) {
                foreach ($files as $val) {
                    $query->whereOr("FIND_IN_SET('{$val}', skill_ids)");
                }
            });
        }

        if (isset($filter['area_ids']) and $filter['area_ids'][0] > 0) {
            $files = $filter['area_ids'];
            $model = $model->where(function ($query) use ($files) {
                foreach ($files as $val) {
                    $query->whereOr("FIND_IN_SET('{$val}', area_ids)");
                }
            });
        }

        $list = $model->alias('s')
            ->join('user u', 'u.id = s.user_id')
            ->join('fa_order_comment c', 's.user_id = c.to_user_id', 'left')
            ->join('fa_order d', 'u.id = d.specialist_id', 'left')
            ->field('s.*,u.mobile,s.nickname as user_nickname,u.id_no_name as user_id_no_name,u.id as user_id,ROUND(IFNULL(AVG(c.points), 0), 1) as avg_score,IFNULL(COUNT(d.id), 0) as order_count')
            ->group('s.user_id')
            ->where("s.status = '1'")
            ->order('avg_score desc,order_count desc')
            ->paginate($limit);
//        echo $model->getLastSql();die;
        foreach ($list->items() as $key => $val) {
            $skill_ids = Db::name("tag")->where('id', 'in', $val->skill_ids)->field('GROUP_CONCAT(name) as tag_names')->find()['tag_names'];
            $area_ids = Db::name("tag")->where('id', 'in', $val->area_ids)->field('GROUP_CONCAT(name) as tag_names')->find()['tag_names'];
            $industry_ids = Db::name("tag")->where('id', 'in', $val->industry_ids)->field('GROUP_CONCAT(name) as tag_names')->find()['tag_names'];
            // 初始化一个空数组，用于存放有值的变量
            $combinedArray = [];
            if ($skill_ids) {
                $combinedArray[] = $skill_ids;
            }
            if ($area_ids) {
                $combinedArray[] = $area_ids;
            }
            if ($industry_ids) {
                $combinedArray[] = $industry_ids;
            }
            $combinedString = implode(',', $combinedArray);
            $val->tags = $combinedString;
        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


    /**
     * 批量更新
     *
     * @param $ids
     * @return void
     */
    public function multi($ids = null)
    {
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
        $values = $this->auth->isSuperAdmin() ? $values : array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
        if (empty($values)) {
            $this->error(__('You have no permission'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
            foreach ($list as $item) {
                if ($values['status'] == '1') {
                    setPostMessage(1, $item->user_id, '您提交认证专家身份审核已通过', '');
                    $this->model->where('user_id = ' . $item->user_id . ' and id <> ' . $item->id)->delete();
                    User::where('id', $item->user_id)->update(['verify_status' => 1]);
                } else {
                    setPostMessage(1, $item->user_id, '您提交认证专家身份审核未通过', '/exportPersonCenter/myMsg/editInfo');
                    $log = new \app\common\model\UserDisableLog();
                    //保存禁用记录
                    if ($values['status'] == 2 && !$log->where('user_id', $item->user_id)->whereTime('disable_time', 'today')->count()) {
                        $log->user_id = $item->user_id;
                        $log->role_type = 2;
                        $log->disable_time = time();
                        $log->save();
                    }
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
     * 更新
     *
     * @param $ids
     * @return void
     */
    public function multi2($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $status = $this->request->post('status','');
        $reason = $this->request->post('reason','');

        if (empty($status)) {
            $this->error(__('Parameter %s can not be empty', 'status'));
        }

        Db::startTrans();
        try {
            $list = SpecialistAuthModel::where('id', $ids)->find();
            $list->status = $status;
            $is = SpecialistModel::where('user_id', $list->user_id)->find();

            //整理要更新的数据
            $data = $list->toArray();
            $data['case_json'] = json_encode($data['case_json']);
            $data['certificate_json'] = json_encode($data['certificate_json']);
            $data['edu_json'] = json_encode($data['edu_json']);
            $data['feature_json'] = json_encode($data['feature_json']);
            $industry_ids = [];
            foreach ($data['industry_arr'] as $v) {
                $industry_ids[] = $v->id;
            }
            $data['industry_ids'] = implode(',', $industry_ids);

            $skill_ids = [];
            foreach ($data['skill_arr'] as $v) {
                $skill_ids[] = $v->id;
            }
            $data['skill_ids'] = implode(',', $skill_ids);

            $area_ids = [];
            foreach ($data['area_arr'] as $v) {
                $area_ids[] = $v->id;
            }
            $data['area_ids'] = implode(',', $area_ids);
            unset($data['industry_arr']);
            unset($data['skill_arr']);
            unset($data['area_arr']);
            unset($data['level_ids']);
            unset($data['id']);
            unset($data['status_text']);
            unset($data['createtime_text']);
            unset($data['updatetime_text']);

            if ($status == '1') {
                //更新
                if(isset($_POST['row'])){
                    $res = $_POST['row'];
                    $data['level_ids'] = $res['level_ids'];
                    $data['intro'] = $res['intro'];
                    $data['case_json'] = $res['case_json'];
                    $data['feature_json'] = $res['feature_json'];
                }
                $data['case_json'] = trim($data['case_json']);
                $data['feature_json'] = trim($data['feature_json']);
                $industry_ids = explode(',',$data['industry_ids']);
                $area_ids = explode(',',$data['area_ids']);
                $skill_ids = explode(',',$data['skill_ids']);
                if ($is && $is->status == '1') {
                    setPostMessage(1, $list->user_id, '您提交专家信息修改审核已通过', '');
                } else {
                    setPostMessage(1, $list->user_id, '您提交认证专家身份审核已通过', '');
                }
                SpecialistAuthModel::where('user_id = ' . $list->user_id . ' and id <> ' . $ids)->delete();

                if ($is) {
                    SpecialistModel::where(['user_id' => $list->user_id])->update($data);
                    $data['status'] = $status;
                    //更新标签
                    TagSpecialist::where('specialist_id', $is->id)->delete();
                    foreach (array_merge($industry_ids, $area_ids, $skill_ids) as $v) {
                        $TagSpecialistModel = new TagSpecialist();
                        $TagSpecialistModel->specialist_id = $is->id;
                        $TagSpecialistModel->tag_id = $v;
                        $TagSpecialistModel->save();
                    }
                } else {
                    $SpecialistModel = new SpecialistModel($data);
                    $SpecialistModel->save();

                    //更新标签
                    TagSpecialist::where('specialist_id', $SpecialistModel->id)->delete();
                    foreach (array_merge($industry_ids, $area_ids, $skill_ids) as $v) {
                        $TagSpecialistModel = new TagSpecialist();
                        $TagSpecialistModel->specialist_id = $SpecialistModel->id;
                        $TagSpecialistModel->tag_id = $v;
                        $TagSpecialistModel->save();
                    }
                    $list->allowField(true)->isUpdate(true)->save(['status' => $status]);
                }
                SpecialistAuthModel::where(['user_id' => $list->user_id])->update($data);
                //更新user表身份证姓名
                \app\common\model\User::where('id', $list->user_id)->update(['id_no_name' => $data['name'], 'avatar' => $data['avatar'], 'nickname' => $data['nickname'], 'verify_status' => $status]);
            } else {
                if ($is == '1') {
                    setPostMessage(1, $list->user_id, '您提交专家信息修改审核未通过', '/exportPersonCenter/myMsg/editInfo');
                } else {
                    setPostMessage(1, $list->user_id, '您提交认证专家身份审核未通过', '/exportPersonCenter/myMsg/editInfo');
                }
                //setPostMessage(1, $list->user_id, '您提交认证专家身份审核未通过', '/exportPersonCenter/myMsg/editInfo');
                $list->allowField(true)->isUpdate(true)->save(['status' => $status,'reason'=>$reason]);
            }

            //更新user表verify_status 和 身份证姓名
            //\app\common\model\User::where('id',$item->user_id)->update(['verify_status'=>$status]);

            Db::commit();
            $this->success();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }
}
