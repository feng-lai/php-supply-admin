<?php

namespace app\admin\controller;

use app\admin\library\Auth;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\exception\PDOException;

/**
 * 标签管理
 *
 * @icon fa fa-tag
 */
class Tag extends Backend
{

    /**
     * Tag模型对象
     * @var \app\admin\model\Tag
     */
    protected $model = null;

    protected $noNeedRight = ['options','sel','import'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Tag;
        $this->view->assign("levelList", $this->model->getLevelList());
        $this->view->assign("typeList", $this->model->getTypeList());
    }

    /**
     * 导入
     */
    public function import(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, 'w');
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding !== 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();

        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $v['COLUMN_COMMENT'] = explode(':', $v['COLUMN_COMMENT'])[0]; //字段备注有:时截取
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }

        //加载文件
        $insert = [];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(2);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
            $first = [];
            $second = [];
            $three = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $name = '';
                $name2 = '';
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $values = is_null($val) ? '' : $val;
                    switch ($currentColumn){
                        case 1:
                            if(!is_null($val)){
                                $first[] = ['name'=>$val,'level'=>1,'type'=>2];
                                $name = $val;
                            }
                            break;
                        case 2:
                            if(!is_null($val)){
                                $second[] = ['name'=>$val,'level'=>2,'type'=>2,'first_name'=>$name];
                                $name2 = $val;
                            }
                            break;
                        case 3:
                            if(!is_null($val)){
                                $three[] = ['name'=>$val,'level'=>3,'type'=>2,'first_name'=>$name,'second_name'=>$name2];
                            }
                            break;
                    }
                }
            }
            $first = array_unique($first,SORT_REGULAR);
            $second = array_unique($second,SORT_REGULAR);
            $three = array_unique($three,SORT_REGULAR);

            //$this->model->saveAll($first);
            //$this->model->saveAll($second);

            $chunks = array_chunk($three, 1000); // 将数据分成500条一组的小数组
            $this->model->saveAll($chunks[0]);

        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }


        $this->success();
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
            $list = $this->model
                // ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach($list as $v){

                    $v->pid = $this->model->where('id',$v->pid)->value('name');

            }
            
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 获取标签列表
     * @param int level
     */
    public function options($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $level = $this->request->post('level','1');
            $type = $this->request->post('type','1');
            $id = $this->request->post('id',0);
            if($level > 1) {
                $level--;
                $where = [
                    'level' => $level,
                    'type'  => $type
                ];
            }
            if($id > 0){
                $where['id'] = ['<>',$id] ;
            }
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->select();
            $result = [
                'msg'=>'获取成功',
                'data'=>$list,
                'code'=>1
            ];

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
            $post = $this->request->post();
            $row = $post['row'];
            $name = isset($row['name']) ? $row['name'] :'';
            $level = isset($row['level']) ? $row['level']:'';
            $type = isset($row['type']) ? $row['type'] : '1';
            $pid = isset($row['pid']) ? $row['pid'] : '';
            if($pid == '' && $level !== '1'){
                $result = [
                    'code' => 0,
                    'msg' => '请选择上级菜单',
                    'data' => ""
                ];
                return json($result);
            }

            $tag = $this->model;
            $tag->name = $name;
            $tag->level = $level;
            $tag->type = $type;
            // $tag->createtime = date('Y-m-d H:i:s');
            // $tag->updatetime = date('Y-m-d H:i:s');

            if($level === '1'){
                
                $tag->pid = 0;
                $tag->path = '-';
                
            }else{
                $top = $this->model->where('id',$pid)->find();
                if(!$top){
                    $result = [
                        'code' => 0,
                        'msg' => '上级菜单不存在',
                        'data' => ""
                    ];
                    return json($result);
                }
                $tag->pid = $pid;
                $tag->path = $top->path.$top->id.'-';
            }
            // 保存数据
            $res = $tag->save();
            if($res){
                $result = [
                    'code' => 1,
                    'msg' => '操作成功',
                    'data' => $tag
                ];
                return json($result);
            }else{
                $result = [
                    'code' => 0,
                    'msg' => '操作失败',
                    'data' => ''
                ];
                return json($result);
            }
            
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $tag = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$tag) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $this->token();
            $post = $this->request->post();
            $row = $post['row'];
            $name = isset($row['name']) ? $row['name'] :'';
            $level = isset($row['level']) ? $row['level']:'';
            $type = isset($row['type']) ? $row['type'] : '1';
            $pid = isset($row['pid']) ? $row['pid'] : '';
            if($pid == '' && $level !== '1'){
                $result = [
                    'code' => 0,
                    'msg' => '请选择上级菜单',
                    'data' => ""
                ];
                return json($result);
            }
            $tag->name = $name;
            $tag->level = $level;
            $tag->type = $type;

            if($level === '1'){
                
                $tag->pid = 0;
                $tag->path = '-';
                
            }else{
                $top = $this->model->where('id',$pid)->find();
                if(!$top){
                    $result = [
                        'code' => 0,
                        'msg' => '上级菜单不存在',
                        'data' => ""
                    ];
                    return json($result);
                }
                $tag->pid = $pid;
                $tag->path = $top->path.$top->id.'-';
            }
            // 保存数据
            $res = $tag->save();
            if($res){
                $result = [
                    'code' => 1,
                    'msg' => '操作成功',
                    'data' => $tag
                ];
                return json($result);
            }else{
                $result = [
                    'code' => 0,
                    'msg' => '操作失败',
                    'data' => ''
                ];
                return json($result);
            }
        }
        // 上级菜单
        $options = [];

        if($tag->level > 1) {
            $level = $tag->level - 1;
            $where = [
                'level' => $level,
                'type'  => $tag->type
            ];
            $options = $this->model
            ->where($where)
            ->order('id', 'desc')
            ->select();
        }
        
        $this->view->assign("options", $options);
        return parent::edit($ids);
    }

    /**
     * 选择标签
     */
    public function sel()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                // ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
}
