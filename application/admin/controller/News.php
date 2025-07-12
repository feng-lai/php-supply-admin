<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 新闻/案例
 *
 * @icon fa fa-circle-o
 */
class News extends Backend
{

    /**
     * News模型对象
     * @var \app\admin\model\News
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\News;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("typeList", $this->model->getTypeList());
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
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','title','image','content','weigh','status','type','createtime']);
                
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function add(){
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        $length = $params['type'] == 1?mb_strlen(strip_tags($params['content']), 'UTF-8'):mb_strlen(strip_tags($params['content2']), 'UTF-8');

        if($params['type'] == 1 && !$params['sub_title']){
            $this->error('请填写副标题');
        }
        if(!$params['image']){
            $this->error('请上传图片');
        }
        if(!$params['content'] && $params['type'] == 1 ){
            $this->error('请填写简介');
        }
        if(!$params['content2'] && $params['type'] == 2 ){
            $this->error('请填写描述');
        }
        $params['type'] == 2?$params['content'] = $params['content2']:'';

        if($params['type'] == 1 && $length > 300){
            //300以内
            $this->error('字数已超出');
        }
        if($params['type'] == 2 && $length > 240){
            //240以内
            $this->error('字数已超出');
        }
        $this->model->allowField(true)->save($params);
        $this->success();
    }

}
