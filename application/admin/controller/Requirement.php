<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\service\RequirementService;
use app\common\model\Config as ConfigModel;
use app\common\model\RequirementSpecialist as RequirementSpecialistModel;
use app\common\model\Meeting as MeetingModel;
use app\common\model\Tag;
use app\common\model\TagRequirement as TagRequirementModel;
use fast\Tree;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Loader;

/**
 * 需求管理
 *
 * @icon fa fa-circle-o
 */
class Requirement extends Backend
{

    /**
     * Requirement模型对象
     * @var \app\admin\model\Requirement
     */
    protected $model = null;
    protected $config = null;
    protected $noNeedRight = ['pass','export','nopass','novsclick','specialist_pass','specialist_nopass','meetingpass','meetingnopass','meetingdetail','vs','indexs','indexspec'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Requirement;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("userTypeList", $this->model->getUserTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("openPriceDataList", $this->model->getOpenPriceDataList());
        $this->view->assign("cdnurl", \think\Config::get('upload.cdnurl'));
        $this->config = ConfigModel::getByName('automatic_matching');
        $this->assignconfig('automatic_matching', $this->config->value);
        $this->assign('automatic_matching', $this->config->value);
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
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
            $where = [];
            $model = $this->model;
            // $where['type'] = $param['type'];
            //print_r($filter);exit;
            if(isset($filter['status'])){
                // $where[] = ['status','in',$filter['status']];
                $model = $model->whereIn('status',$filter['status']);
                if(isset($filter['tab'])){
                    $model = $model->where('status',$filter['tab']);
                }
            }else{
                if(isset($filter['tab'])){
                    $model = $model->where('status',$filter['tab']);
                }
            }
            if(isset($filter['title'])){
                $model = $model->where('title|sn','like','%'.$filter['title'].'%');
            }
            if(isset($filter['createtime'])){
                $timeArr = explode(' - ', $filter['createtime']);
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $model = $model->whereTime('createtime','between',[$begin,$end]);
                }
            }


            if(isset($filter['tag'])){
                $filter['tag'] = str_replace('(全部)', '', $filter['tag']);
                $tag_name = explode(",",$filter['tag']);
                $tag = Db::name("tag")->where('name','in',$tag_name)->select();
                $industry_ids = [];
                $skill_ids = [];
                $area_ids = [];

                foreach ($tag as $key=>$val){

//                echo "<pre>";
//                var_dump($val);die;
                    if($val['type'] === '1'){
                        $industry_ids[] = $val['id'];
                        foreach ($industry_ids as $parent_id) {
                            getAllChildTags($parent_id, $industry_ids);
                        }
                        $industry_ids = array_unique(array_merge($industry_ids,getAllParentTagsId($val['id'])));
                    }else if($val['type'] === '2'){
                        $skill_ids[] = $val['id'];
                        foreach ($skill_ids as $parent_id) {
                            getAllChildTags($parent_id, $skill_ids);
                        }
                        $skill_ids = array_unique(array_merge($skill_ids,getAllParentTagsId($val['id'])));
                    }else if($val['type'] === '3'){
                        $area_ids[] = $val['id'];
                        foreach ($area_ids as $parent_id) {
                            getAllChildTags($parent_id, $area_ids);
                        }
                        $area_ids = array_unique(array_merge($area_ids,getAllParentTagsId($val['id'])));
                    }
                }
                if(!empty($industry_ids)){
                    $filter['industry_ids'] = $industry_ids;
                }
                if(!empty($skill_ids)){
                    $filter['skill_ids'] = $skill_ids;
                }
                if(!empty($area_ids)){
                    $filter['area_ids'] = $area_ids;
                }

            }


            if(isset($filter['industry_ids']) and $filter['industry_ids'][0] > 0){
                $files = $filter['industry_ids'];
                $model = $model->where(function($query) use ($files) {
                    foreach ($files as $val) {
                        $query->whereOr("FIND_IN_SET('{$val}', industry_ids)");
                    }
                });
            }

            if(isset($filter['skill_ids']) and $filter['skill_ids'][0] > 0){
                $files = $filter['skill_ids'];
                $model = $model->where(function($query) use ($files) {
                    foreach ($files as $val) {
                        $query->whereOr("FIND_IN_SET('{$val}', skill_ids)");
                    }
                });
            }

            if(isset($filter['area_ids']) and $filter['area_ids'][0] > 0){
                $files = $filter['area_ids'];
                $model = $model->where(function($query) use ($files) {
                    foreach ($files as $val) {
                        $query->whereOr("FIND_IN_SET('{$val}', area_ids)");
                    }
                });
            }

            if(isset($filter['statusa'])){
                $files = $filter['statusa'];
                $model = $model->where('status','=',$files);
            }
            $list = $model
                ->where($where)
                ->where('type',$param['type'])
                ->order($sort, $order)
                ->paginate($limit);
            foreach($list as $k=>$v){
                if(strlen($v->title)/3>20){
                    $v->title = mb_substr($v->title,0,20).'...';
                }
                if(strlen($v->content)/3>30){
                    $v->content = mb_substr($v->content,0,30).'...';
                }
                $v->sn = substr($v->sn,0,12).'...';
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $param = $this->request->param();
        // dump($param);die;


        $this->view->assign("industry", $this->getTagList(1));
        $this->view->assign("skill", $this->getTagList(2));
        $this->view->assign("area", $this->getTagList(3));
        $this->assignconfig('type', $param['type']);
        if(intval($param['type']) == 1){
            return $this->view->fetch('index2');
        }else{
            return $this->view->fetch('index');
        }
        return $this->view->fetch();
    }

    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');

        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        /**
        [type] => 2
    [user_name] =>
    [user_type] => 1
    [title] =>
    [sn] =>
    [content] =>
    [status] => 2
    [industry_ids] =>
    [skill_ids] =>
    [area_ids] =>
    [begin] =>
    [end] =>
    [files] =>
    [open_price_data] => 0
    [price] =>
    [publishtime] => 2024-05-27 17:57:28
)**/
        if(!$params['user_name']){
            $this->error(__('用户昵称不能为空', ''));
        }
        if(!$params['title']){
            $this->error(__('标题不能为空', ''));
        }
        if(!$params['content']){
            $this->error(__('需求描述不能为空', ''));
        }
        if(!$params['industry_ids']){
            $this->error(__('请选择行业标签', ''));
        }

        if(!$params['skill_ids']){
            $this->error(__('请选择技能标签', ''));
        }

        if(!$params['area_ids']){
            $this->error(__('请选择地区标签', ''));
        }

        if(!$params['price']){
            $this->error(__('服务费起点不能为空', ''));
        }

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {

                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }

            $result = $this->model->allowField(true)->save($params);

            $industry_arr = explode(",", $params['industry_ids']);
            $skill_arr = explode(",",$params['skill_ids']);
            $area_arr = explode(",", $params['area_ids']);
            foreach ($industry_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $this->model->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($skill_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $this->model->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($area_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $this->model->id;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }

            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }



    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $files = [];
        if($row->files){
            foreach(json_decode($row->files,true) as $v){
                $files[] = $v['url'];
            }
        }
        $files = implode(',',$files);
        $row->files2 = $files;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $tagids = [];
            $tagids[] = $row->industry_ids;
            $tagids[] = $row->skill_ids;
            $tagids[] = $row->area_ids;
//            dump(implode(",",$tagids));die;

            $tagidsStr = implode(",",$tagids);
            $tag = Tag::whereIn('id',$tagidsStr)->select();
            $this->assignconfig('tag', $tag);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }

            $result = $row->allowField(true)->save($params);

            $industry_arr = explode(",", $params['industry_ids']);
            $skill_arr = explode(",",$params['skill_ids']);
            $area_arr = explode(",", $params['area_ids']);
            Db::name("tag_requirement")->where(['requirement_id' => $ids])->delete();
            foreach ($industry_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $ids;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($skill_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $ids;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }
            foreach ($area_arr as $key => $value) {
                $TagSpecialistModel = new TagRequirementModel();
                $TagSpecialistModel->requirement_id = $ids;
                $TagSpecialistModel->tag_id = $value;
                $TagSpecialistModel->save();
            }

            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    public function export()
    {
        if ($this->request->isPost()) {
            set_time_limit(0);
            $columns = "id,sn,title,content,status,createtime,keywords_tags";
            $search = $this->request->post('search');
            $ids = $this->request->post('ids');
            $filter = $this->request->post('filter');
            $op = $this->request->post('op');
            $sort = $this->request->post('sort');
            $order = $this->request->post('order');

            $searchList = $this->request->post('searchList');
            $searchList = json_decode($searchList,true);

            $spreadsheet = new Spreadsheet();
            $spreadsheet->getProperties()
                ->setCreator("导出")
                ->setLastModifiedBy("导出")
                ->setTitle("导出")
                ->setSubject("Subject");
            $spreadsheet->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
            // 单元格格式 文本
            $spreadsheet->getDefaultStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

            $worksheet = $spreadsheet->setActiveSheetIndex(0);
            $whereIds = $ids == 'all' ? '1=1' : ['id' => ['in', explode(',', $ids)]];
            $this->request->get(['search' => $search, 'id' => $ids, 'filter' => '', 'op' => $op, 'sort' => $sort, 'op' => $order]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

//
            $columns_arr = explode(',',$columns);

            $line = 1;
            $list = [];
            $this->model
                ->field($columns)
                ->where($where)
                ->where($whereIds)
                ->chunk(100, function ($items) use (&$list, &$line, &$worksheet,&$columns_arr,&$searchList) {
                    $list = $items = collection($items)->toArray();
                    foreach ($items as $index => $item) {
                        $item['status'] = $item['status_text'];
                        $item['createtime'] = date("Y/m/d",$item['createtime']);
                        $line++;
                        $col = 1;
                        foreach ($item as $field => $value) {
                            // 只导出传递的字段 过滤 createtime_text 等 modeld 中的附加字段
                            if (!in_array($field,$columns_arr)) continue;

                            // 根据前端传递的 $searchList 处理状态等
                            if (isset($searchList[$field])){
                                $value = $searchList[$field][$value] ?? $value;
                            }

                            if (strlen($value) < 10){
                                $worksheet->setCellValueByColumnAndRow($col, $line, $value);
                            }else{
                                // 防止长数字科学计数
                                $worksheet->setCellValueExplicitByColumnAndRow($col, $line, $value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            }
                            $col++;
                        }
                    }
                },$sort,$order);
            $first = array_keys($list[0]);
            foreach ($first as $index => $item) {
                // 只导出传递的字段
                if (!in_array($item,$columns_arr)) continue;
                $worksheet->setCellValueByColumnAndRow($index + 1, 1, __($item));
                // 单元格自适应宽度
                $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($index + 1)->setAutoSize(true);
                // 首行加粗
                $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($index + 1, 1)->getFont()->setBold(true);
                // 水平居中
                $styleArray = [
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ];
                $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($index + 1, 1)->applyFromArray($styleArray);
            }

            $spreadsheet->createSheet();
            // Redirect output to a client’s web browser (Excel2007)
            $title = date("YmdHis");
            //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            //header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
            //header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            //header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            //header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            //header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            //header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            //header('Pragma: public'); // HTTP/1.0

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            // 下载文档
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            return;
        }
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


    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", !empty($this->model) && $this->model->getPk() ? $this->model->getPk() : 'id');
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset/d", 0);
        $limit = $this->request->get("limit/d", 999999);
        //新增自动计算页码
        $page = $limit ? intval($offset / $limit) + 1 : 1;
        if ($this->request->has("page")) {
            $page = $this->request->get("page/d", 1);
        }
        $this->request->get([config('paginate.var_page') => $page]);
        $filter = (array)json_decode($filter, true);
        $op = (array)json_decode($op, true);
        $filter = $filter ? $filter : [];
        $where = [];
        $alias = [];
        $bind = [];
        $name = '';
        $aliasName = '';
        if (!empty($this->model) && $relationSearch) {
            $name = $this->model->getTable();
            $alias[$name] = Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
            $aliasName = $alias[$name] . '.';
        }
        $sortArr = explode(',', $sort);
        foreach ($sortArr as $index => & $item) {
            $item = stripos($item, ".") === false ? $aliasName . trim($item) : $item;
        }
        unset($item);
        $sort = implode(',', $sortArr);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$aliasName . $this->dataLimitField, 'in', $adminIds];
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $aliasName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        $index = 0;

        foreach ($filter as $key=>$value){
            if($key=='industry_ids'){
//                var_dump($value);die;
                //获取所有字分类id的方法，可下面的获取子分类方法，自己实现
//                $tag = new Tag();
                $filter[$key] = $value;
                //筛选方式改成in
                $op['industry_ids'] = 'in';
            }
        }

        foreach ($filter as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $k)) {
                continue;
            }

            $sym = $op[$k] ?? '=';
            if (stripos($k, ".") === false) {
                $k = $aliasName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper($op[$k] ?? $sym);
            //null和空字符串特殊处理
            if (!is_array($v)) {
                if (in_array(strtoupper($v), ['NULL', 'NOT NULL'])) {
                    $sym = strtoupper($v);
                }
                if (in_array($v, ['""', "''"])) {
                    $v = '';
                    $sym = '=';
                }
            }

            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $v = is_array($v) ? $v : explode(',', str_replace(' ', ',', $v));
                    $findArr = array_values($v);
                    foreach ($findArr as $idx => $item) {
                        $bindName = "item_" . $index . "_" . $idx;
                        $bind[$bindName] = $item;
                        $where[] = "FIND_IN_SET(:{$bindName}, `" . str_replace('.', '`.`', $k) . "`)";
                    }
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr, function ($v) {
                            return $v != '' && $v !== false && $v !== null;
                        })) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $tableArr = explode('.', $k);
                    if (count($tableArr) > 1 && $tableArr[0] != $name && !in_array($tableArr[0], $alias)
                        && !empty($this->model) && $this->relationSearch) {
                        //修复关联模型下时间无法搜索的BUG
                        $relation = Loader::parseName($tableArr[0], 1, false);
                        $alias[$this->model->$relation()->getTable()] = $tableArr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' TIME', $arr];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
            $index++;
        }
        if (!empty($this->model)) {
            $this->model->alias($alias);
        }
        $model = $this->model;
        $where = function ($query) use ($where, $alias, $bind, &$model) {
            if (!empty($model)) {
                $model->alias($alias);
                $model->bind($bind);
            }
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit, $page, $alias, $bind];
    }


    /**
     * 需求详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['user' => function($query) {
            $query->field('username,nickname,mobile,id_no_name,avatar');
        }])->where('requirement.id',$ids)->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //返回平台推荐信息
        $row['requirement_specialist_1'] = RequirementSpecialistModel::alias('rs')
        ->join('specialist s','s.user_id = rs.user_id')
        ->join('user u','u.id = rs.user_id')
        ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
        ->field('s.id as spec_id,rs.*,s.nickname,u.id_no_name,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm,m.refuse_desc')
        ->where('rs.type', '1')
        ->where("s.status = '1'")
        ->where('rs.requirement_id',$ids)
        ->group('rs.id')
        ->order('rs.id','desc')
        ->select();


//        dump($row['requirement_specialist_1']);die;
        $row['requirement_specialist_2'] = RequirementSpecialistModel::alias('rs')
        ->join('user u','u.id = rs.user_id')
        ->join('specialist s','s.user_id = rs.user_id')
        ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
        ->field('s.id as spec_id,rs.*,s.nickname,u.id_no_name,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm,m.refuse_desc')
        ->where('rs.type', '2')
        ->where("s.status = '1'")
        ->where('rs.requirement_id',$ids)
        ->group('rs.id')
        ->order('rs.id','desc')
        ->select();

        $row['requirement_specialist_3'] = RequirementSpecialistModel::alias('rs')
        ->join('user u','u.id = rs.user_id')
        ->join('specialist s','s.user_id = rs.user_id')
        ->join('meeting m','m.requirenment_specialist_id = rs.id','left')
        ->field('s.id as spec_id,rs.*,s.nickname,u.id_no_name,m.id as meeting_id,m.desc as meeting_desc,m.status as meeting_status,m.info as meeting_info,m.confirm as meeting_confirm,m.refuse_desc')
        ->where("s.status = '1'")
        ->where('rs.type', '3')
        ->where('rs.requirement_id',$ids)
        ->group('rs.id')
        ->order('rs.id','desc')
        ->select();



        $order = Db::name("requirement")
            ->alias("a")
            ->join("order b","b.rid = a.id","left")
            ->join("user c","c.id = b.specialist_id","left")
            ->join("requirement_specialist e","e.id = b.requirement_specialist_id","left")
            ->where(['a.id' => $row->id])
            ->field("b.*,c.nickname,e.desc as apply_desc,e.files,e.status as apply_status")
            ->find();

//        var_dump($order);die;

        if($order){
            $order['files'] = $order['files'] ? json_decode($order['files'],true) : [];
            $order['pay'] = Db::name("order_pay")->where(['order_id' => $order['id']])->order("idx asc")->select();
        }

        $this->view->assign("order", $order);

//        echo "<pre>";
//        dump($row['order']);die;
        if ($this->request->isAjax()) {
            $this->success("Ajax请求成功", null, ['id' => $ids]);
        }

        $industry = Db::name("tag")->where('id','in',$row->industry_ids)->field("name,id")->select();
        $skill = Db::name("tag")->where('id','in',$row->skill_ids)->field("name,id")->select();
        $area = Db::name("tag")->where('id','in',$row->area_ids)->field("name,id")->select();

        $this->view->assign("industry",$industry);
        $this->view->assign("skill", $skill);
        $this->view->assign("area", $area);

        $this->view->assign("industryData", $this->getTagList(1));
        $this->view->assign("skillData", $this->getTagList(2));
        $this->view->assign("areaData", $this->getTagList(3));
        $this->view->assign("row", $row->toArray());

        return $this->view->fetch();
    }

    /**
     * 审核-通过
     */
    public function pass($ids = null)
    {
        $row = $this->model->get(['id' => $ids]);

        if ($this->request->isPost()) {
            $par = $this->request->param();
            unset($par['ids']);
            $this->model->where('id',$ids)->update($par);

            if($this->config->value === '1'){
                $data = Db::name('specialist')
                    ->where("FIND_ALL_PART_IN_SET('{$row->skill_ids}',skill_ids)")
                    ->where("FIND_ALL_PART_IN_SET('{$row->area_ids}',area_ids)")
                    ->where("FIND_ALL_PART_IN_SET('{$row->industry_ids}',industry_ids)")
                    ->limit(10)
                    ->select();

                if(empty($data)){
                    $data = Db::name('specialist')
                        ->alias('a')
                        ->join('fa_order_comment b', 'a.id = b.to_user_id', 'left')
                        ->join('fa_order c', 'a.id = c.specialist_id', 'left')
                        ->field('a.id,a.user_id, a.name, AVG(b.points) as avg_score, COUNT(c.id) as order_count')
                        ->group('a.id')
                        ->order('avg_score', 'desc')
                        ->order('order_count', 'desc')
                        ->limit(10)
                        ->select();
                }

                foreach ($data as $key => $value) {
                    $dataArr[] = [
                        'user_id' => $value['user_id'],
                        'requirement_id' => $ids,
                        'status' => '0',//状态:0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；
                        'type' => '1'
                    ];
                }
                $row->status = '2';
                Db::startTrans();
                try {
                    setPostMessage(3,$row->user_id,'您提交的需求已审核通过，点击查看详情','/myNeed/detail?status=2&id='.$row->id);
                    $row->save();
                    $model = new RequirementSpecialistModel();
                    $model->saveAll($dataArr);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error("操作失败",$e->getMessage());
                }
                $res = [
                    'code' => 1,
                    'msg' => '操作成功'
                ];
            }else{
                $service = new RequirementService();
                $res = $service->vertifyPass($ids);
            }

            return json($res);
            $this->success("Ajax请求成功", null, ['id' => $ids]);

        }

    }
    /**
     * 审核-不通过
     */
    public function nopass($ids = null)
    {
        // $row = $this->model->get(['id' => $ids]);
       

        if ($this->request->isPost()) {
            
            $refuse_reason = $this->request->post('refuse_reason','');
            $service = new RequirementService();
            $res = $service->vertifyNopass($ids,$refuse_reason);

            return json($res);
            $this->success("Ajax请求成功", null, ['id' => $ids]);

        }

    }

    public function novsclick($ids=null){

        $row = $this->model->get(['id' => $ids]);
        $row->status = 2;
        $row->save();
        $this->success("操作成功", null, ['id' => $ids]);
    }

    
    /**
     * 专家申请审核-通过
     */
    public function specialist_pass($ids = null)
    {
        //$row = $this->model->get(['id' => $ids]);

        if ($this->request->isPost()) {
            $model = new RequirementSpecialistModel();
            $rs = $model->where('id',$ids)->find();
            $row = $this->model->get(['id' => $rs->requirement_id]);
            $service = new RequirementService();
            // $res = $service->vertifyPass($rs->requirement_id);
            
            //状态:0-专家待参与 1-待需求方确认 2-需求方确认 3-已拒绝 4-已取消；
            ////状态:0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；
            //如果是需求方预约，则无需确认
            $rs->status = '2';
            if($rs->type == '2'){
                $rs->status = '3';
            }
            $rs->vertify_status = 1;
            $rs->save();
            setPostMessage(3,$rs->user_id,'您提交参与'.$row->title.'需求时，参与信息审核通过，点击查看详情','/myProject/detail?status='.$row->status.'&id='.$row->id);
            // return json($res);
            $this->success("操作成功", null, ['id' => $ids]);

        }

    }
    /**
     * 专家申请审核-通过
     */
    public function specialist_nopass($ids = null)
    {



        if ($this->request->isPost()) {
            $service = new RequirementService();
            // $res = $service->vertifyPass($ids);
            $model = new RequirementSpecialistModel();
            $rs = $model->where('id',$ids)->find();
            $row = $this->model->get(['id' => $rs->requirement_id]);
            $rs->status = '1';
            $rs->vertify_status = 2;
            $rs->reason = $this->request->post('reason');
            $rs->save();

            setPostMessage(3,$rs->user_id,'您提交参与'.$row->title.'需求时，参与信息审核未通过，点击查看详情','/myProject/detail?status='.$row->status.'&id='.$row->id);
            // return json($res);
            $this->success("操作成功", null, ['id' => $ids]);

        }

    }

    /**
     * 沟通审核-通过
     */
    public function meetingpass($ids = null)
    {
        $row = $this->model->get(['id' => $ids]);

        if ($this->request->isPost()) {
            $model = new MeetingModel();
            $meeting = $model->where('id',$ids)->find();
            $meeting->status = '1';
            $meeting->save();
            $row = $this->model->get(['id' => $meeting->requirement_id]);
            setPostMessage(2,$row->user_id,'您发起的沟通申请审核已通过，点击查看详情','/myNeed/detail?status='.$row->status.'&id='.$row->id);
            setPostMessage(2,$meeting->specialist_user_id,'您有一条新的沟通申请，点击查看详情','/myProject/detail?status='.$row->status.'&id='.$row->id);
            // return json($res);
            $this->success("操作成功", null, ['id' => $ids]);

        }

    }

    /**
     * 沟通审核-不通过
     */
    public function meetingnopass($ids = null)
    {



        if ($this->request->isPost()) {
            $row = MeetingModel::field('r.user_id,r.status,r.id')->alias('m')->join('requirement r','r.id = m.requirement_id')->find();
            $reason = $this->request->post('reason','');
            $model = new MeetingModel();
            $meeting = $model->where('id',$ids)->find();
            $meeting->status = '2';
            $meeting->failed_desc = $reason;
            $meeting->save();
            setPostMessage(2,$row->user_id,'您发起的沟通申请审核未通过，点击查看详情','/myNeed/detail?status='.$row->status.'&id='.$row->id);
            // return json($res);
            $this->success("操作成功", null, ['id' => $ids]);

        }

    }

    /**
     * 沟通信息填写
     */
    public function meetingdetail($ids = null){
        $row = $this->model->get(['id' => $ids]);

        if ($this->request->isPost()) {
            $info = $this->request->post('info');
            $model = new MeetingModel();
            $meeting = $model->where('id',$ids)->find();
            $meeting->info = $info;
            $meeting->save();
            // return json($res);
            $this->success("操作成功", null, ['id' => $ids]);

        }
    }

    /**
     * 推荐-通过
     */
    public function vs($ids = null)
    {
        $row = $this->model->get(['id' => $ids]);
        $vsids = $this->request->post('vsids');
        // dump($vsids);die;
        $arr = explode(',',$vsids);
        // dump($arr);die;
        if ($this->request->isPost()) {
            
            $dataArr = [];
            foreach ($arr as $key => $value) {
                $dataArr[] = [
                    'user_id' => $value,
                    'requirement_id' => $ids,
                    'status' => '0',//状态:0-专家待确认参与1-专家已申请参与 2-待需求方确认 3-需求方确认 4-已拒绝 5-已取消；
                    'type' => '1'
                ];
            }
            $row->status = '2';
            Db::startTrans();
            try {
                $row->save();
                $model = new RequirementSpecialistModel();
                $model->saveAll($dataArr);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error("操作失败",$e->getMessage());
            }
            $this->success("请求成功", null, ['id' => $ids]);

        }

    }

    /**
     * 查看-某用户的
     */
    public function indexs()
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
            $filter = isset($param['filter'])?json_decode($param['filter'],true):[];
            // dump($filter );die;
            $user_id = $param['uid'];
            $where = [];
            $model = $this->model;
            if(isset($filter['status'])){
                $model = $model->whereIn('status',$filter['status']);
            }
            if(isset($filter['title'])){
                $model = $model->where('title|sn|id','like','%'.$filter['title'].'%');
            }
            if(isset($filter['createtime'])){
                $timeArr = explode(' - ', $filter['createtime']);
                // dump($timeArr);die;
                // $where[] = ['createtime','between',[$timeArr[0],$timeArr[1]]];
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $model = $model->whereTime('createtime','between',[$begin,$end]);
                }
            }


            if(isset($filter['tag'])){
                $filter['tag'] = str_replace('(全部)', '', $filter['tag']);
                $tag_name = explode(",",$filter['tag']);

                $tag = Db::name("tag")->where('name','in',$tag_name)->select();
                $industry_ids = [];
                $skill_ids = [];
                $area_ids = [];

                foreach ($tag as $key=>$val){
//                echo "<pre>";
//                var_dump($val);die;
                    if($val['type'] === '1'){
                        $industry_ids[] = $val['id'];
                        foreach ($industry_ids as $parent_id) {
                            getAllChildTags($parent_id, $industry_ids);
                        }
                    }else if($val['type'] === '2'){
                        $skill_ids[] = $val['id'];
                        foreach ($skill_ids as $parent_id) {
                            getAllChildTags($parent_id, $skill_ids);
                        }
                    }else if($val['type'] === '3'){
                        $area_ids[] = $val['id'];
                        foreach ($area_ids as $parent_id) {
                            getAllChildTags($parent_id, $area_ids);
                        }
                    }
                }


                if(!empty($industry_ids)){
                    $filter['industry_ids'] = $industry_ids;
                }
                if(!empty($skill_ids)){
                    $filter['skill_ids'] = $skill_ids;
                }
                if(!empty($area_ids)){
                    $filter['area_ids'] = $area_ids;
                }

            }


            if(isset($filter['industry_ids']) and $filter['industry_ids'][0] > 0){
                $files = $filter['industry_ids'];
                $model = $model->where(function($query) use ($files) {
                    foreach ($files as $val) {
                        $query->whereOr("FIND_IN_SET('{$val}', industry_ids)");
                    }
                });
            }

            if(isset($filter['skill_ids']) and $filter['skill_ids'][0] > 0){
                $files = $filter['skill_ids'];
                $model = $model->where(function($query) use ($files) {
                    foreach ($files as $val) {
                        $query->whereOr("FIND_IN_SET('{$val}', skill_ids)");
                    }
                });
            }

            if(isset($filter['area_ids']) and $filter['area_ids'][0] > 0){
                $files = $filter['area_ids'];
                $model = $model->where(function($query) use ($files) {
                    foreach ($files as $val) {
                        $query->whereOr("FIND_IN_SET('{$val}', area_ids)");
                    }
                });
            }

            $list = $model
                // ->with('group')
                ->where($where)
                ->where('user_id',$user_id)
                ->where('type',$param['type'])
                ->order($sort, $order)
//                 ->buildSql();
                ->paginate(10);
//                 dump($list);die;
            foreach($list as $v){
                if(strlen($v->content)/3>30){
                    $v->content = mb_substr($v->content,0,30).'...';
                }
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $param = $this->request->param();
        // dump($param);die;
        
        if(intval($param['type']) == 1){
            return $this->view->fetch('index2');
        }else{
            return $this->view->fetch('index');
        }
        return $this->view->fetch();
    }
    /**
     * 查看-某专家的的
     */
    public function indexspec()
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
            $filter = isset($param['filter'])?json_decode($param['filter'],true):[];

            $user_id = $param['uid'];
            $where = [];
            $model = $this->model;
            // $where['type'] = $param['type'];
            if(isset($filter['status'])){
                // $where[] = ['status','in',$filter['status']];
                $model = $model->whereIn('r.status',$filter['status']);
                
            }
            if(isset($filter['title'])){
                $model = $model->where('r.title|r.sn|r.id','like','%'.$filter['title'].'%');
            }
            if(isset($filter['createtime'])){
                $timeArr = explode(' - ', $filter['createtime']);
                // dump($timeArr);die;
                // $where[] = ['createtime','between',[$timeArr[0],$timeArr[1]]];
                if(count($timeArr) == 2){
                    $begin = strtotime($timeArr[0]);
                    $end = strtotime($timeArr[1]);
                    $model = $model->whereTime('r.createtime','between',[$begin,$end]);
                }
            }
            if(isset($filter['tag'])){
                $filter['tag'] = str_replace('(全部)', '', $filter['tag']);
                $tag_name = explode(",",$filter['tag']);
                $tag = Db::name("tag")->where('name','in',$tag_name)->select();
                $industry_ids = [];
                $skill_ids = [];
                $area_ids = [];

                foreach ($tag as $key=>$val){
                    if($val['type'] === '1'){
                        $industry_ids[] = $val['id'];
                        foreach ($industry_ids as $parent_id) {
                            getAllChildTags($parent_id, $industry_ids);
                        }
                    }else if($val['type'] === '2'){
                        $skill_ids[] = $val['id'];
                        foreach ($skill_ids as $parent_id) {
                            getAllChildTags($parent_id, $skill_ids);
                        }
                    }else if($val['type'] === '3'){
                        $area_ids[] = $val['id'];
                        foreach ($area_ids as $parent_id) {
                            getAllChildTags($parent_id, $area_ids);
                        }
                    }
                }
                if(!empty($industry_ids)){
                    $model = $model->where('r.industry_ids','in',$industry_ids);
                }
                if(!empty($skill_ids)){
                    $model = $model->where('r.skill_ids','in',$skill_ids);
                }
                if(!empty($area_ids)){
                    $model = $model->where('r.area_ids','in',$area_ids);
                }

            }
            
            // dump($where);die;
            $list = $model->alias('r')
                ->join('requirement_specialist rs','rs.requirement_id = r.id')
                ->field('r.*')
                // ->with('group')
                ->where($where)
                ->where('rs.user_id',$user_id)
                ->where('r.type',$param['type'])
                ->order($sort, $order)
                // ->buildSql();
                ->paginate(10);
                // dump($list);die;
            foreach ($list as &$v){
                $ids = array_merge(explode(',',$v->skill_ids),explode(',',$v->area_ids),explode(',',$v->industry_ids));
                $tags = Tag::where('id','in',$ids)->column('name');
                $v->tag = implode(',',$tags);
                if(strlen($v->content)/3>30){
                    $v->content = mb_substr($v->content,0,30).'...';
                }
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $param = $this->request->param();
        // dump($param);die;
        
        if(intval($param['type']) == 1){
            return $this->view->fetch('index2');
        }else{
            return $this->view->fetch('index');
        }
        return $this->view->fetch();
    }

}
