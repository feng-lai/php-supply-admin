<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Invoice as InvoiceModel;
use app\common\model\InvoiceLog as InvoiceLogModel;
use think\Db;
use app\common\model\SpecialistFav as SpecialistFavModel;
use app\common\model\Specialist as SpecialistModel;
use app\common\model\TagSpecialist as TagSpecialistModel;
use app\common\model\UserArchive;
use app\common\model\Tag as TagModel;
use app\common\model\Message as MessageModel;
use app\common\model\OrderBill as OrderBillModel;


/**
 * 发票、收藏、消息、账单
 */
class Center extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 我的发票抬头
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param int $type          发票种类:1=普通发票,2=专用发票
     * @param string $keyword   页数：默认1
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     */
    public function invoice()
    {
        $type = $this->request->post('type','');
        $keyword = $this->request->post('keyword','');
        $limit = $this->request->post('limit');
        $where['user_id'] = $this->auth->id;
        $type?$where['type'] = $type:'';
        $keyword?$where['title'] = ['like','%'.$keyword.'%']:'';
        $data = InvoiceModel::where($where)->order('createtime','desc')->paginate($limit);
        $this->success('请求成功', $data);
    }

    /**
     * 设为默认发票
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $invoice_id     发票ID
     */
    public function invoice_default()
    {
        $invoice_id = $this->request->post('invoice_id');
        $is_default = $this->request->post('is_default')?$this->request->post('is_default'):0;
        if(!$invoice_id){
            $this->error("invoice_id不能为空");
        }
        $data = InvoiceModel::where('id',$invoice_id)->where('user_id', $this->auth->id)->find();
        if(!$data){
            $this->error("找不到发票信息");
        }
        InvoiceModel::where('user_id', $this->auth->id)->where('type',$data->type)->update(['is_default' => 0]);
        if($is_default){
            InvoiceModel::where('user_id', $this->auth->id)->where('id',$invoice_id)->update(['is_default' => 1]);
        }
        $this->success('请求成功', $invoice_id);
    }

    /**
     * 获取默认发票
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     */
    public function my_default_invoice()
    {
        $invoice = InvoiceModel::where('user_id', $this->auth->id)->where('is_default',1)->find();
        $this->success('请求成功', $invoice);
    }

    /**
     * 获取发票详情
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $invoice_id     发票ID
     */
    public function invoice_detail()
    {
        $invoice_id = $this->request->post('invoice_id');
        $invoice = InvoiceModel::where('user_id', $this->auth->id)->where('id',$invoice_id)->find();
        $this->success('请求成功', $invoice);
    }

    /**
     * 获取历史发票详情
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $invoice_id     发票ID
     */
    public function invoice_log_detail()
    {
        $invoice_id = $this->request->post('invoice_id');
        $invoice = InvoiceLogModel::where('user_id', $this->auth->id)->where('id',$invoice_id)->find();
        $this->success('请求成功', $invoice);
    }

    /**
     * 删除发票
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $invoice_id     发票ID
     */
    public function invoice_del()
    {
        $invoice_id = $this->request->post('invoice_id');
        $invoice = InvoiceModel::where('user_id', $this->auth->id)->where('id',$invoice_id)->delete();
        $this->success('请求成功', $invoice);
    }
    

    /**
     * 我的发票抬头-新增
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $invoice_type 发票种类:1=普通发票,2=专用发票
     * @param string $invoice_title 发票抬头
     * @param string $invoice_company_number 发票税号
     * @param string $invoice_company_addr 发票详细地址
     * @param string $invoice_company_tel 发票联系电话
     * @param string $invoice_recepient_email 发票接收邮箱
     * @param string $invoice_recepient_addr 发票收票地址
     * @param string $invoice_recepient_tel 发票收票电话
     * @param string $invoice_recepient_name 发票收票人
     * @param string $invoice_bank_name 专票:开户行
     * @param string $invoice_bank_account 专票:开户账号
     */
    public function add_invoice()
    {
        $invoice = new InvoiceModel();

        $invoice_type = $this->request->post('invoice_type','');// 发票种类:1=普通发票,2=专用发票
        $invoice_title = $this->request->post('invoice_title','');// 发票抬头
        $invoice_company_number = $this->request->post('invoice_company_number','');// 发票税号
        $invoice_company_addr = $this->request->post('invoice_company_addr','');// 发票详细地址
        $invoice_company_tel = $this->request->post('invoice_company_tel','');// 发票电话
        $invoice_recepient_email = $this->request->post('invoice_recepient_email','');// 发票接收邮箱
        $invoice_recepient_name = $this->request->post('invoice_recepient_name','');// 发票接收人
        $invoice_recepient_addr = $this->request->post('invoice_recepient_addr','');// 发票接收地址
        $invoice_recepient_tel = $this->request->post('invoice_recepient_tel','');// 发票接收电话
        $invoice_bank_name = $this->request->post('invoice_bank_name','');// 专票:开户行
        $invoice_bank_account = $this->request->post('invoice_bank_account','');// 专票:银行账户

        if(!$invoice_type || !$invoice_title || !$invoice_company_number || !$invoice_company_addr || !$invoice_company_tel || !$invoice_recepient_email || !$invoice_recepient_name || !$invoice_recepient_addr || !$invoice_recepient_tel)
        {
            $this->error("发票信息不能为空");
        }
        if($invoice_type == '2' && (!$invoice_bank_name || !$invoice_bank_account)){
            $this->error("专票信息不能为空");
        }
        $invoice->user_id = $this->auth->id;
        $invoice->type = $invoice_type;
        $invoice->title = $invoice_title;
        $invoice->company_number = $invoice_company_number;
        $invoice->company_addr = $invoice_company_addr;
        $invoice->company_tel = $invoice_company_tel;
        $invoice->recepient_email = $invoice_recepient_email;
        $invoice->recepient_name = $invoice_recepient_name;
        $invoice->recepient_addr = $invoice_recepient_addr;
        $invoice->recepient_tel = $invoice_recepient_tel;
        $invoice->bank_name = $invoice_bank_name;
        $invoice->bank_account = $invoice_bank_account;

        Db::startTrans();
        try {
            $invoice->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$invoice);
    }

    /**
     * 我的发票抬头-修改
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id 发票ID
     * @param string $invoice_type 发票种类:1=普通发票,2=专用发票
     * @param string $invoice_title 发票抬头
     * @param string $invoice_company_number 发票税号
     * @param string $invoice_company_addr 发票详细地址
     * @param string $invoice_company_tel 发票联系电话
     * @param string $invoice_recepient_email 发票接收邮箱
     * @param string $invoice_recepient_addr 发票收票地址
     * @param string $invoice_recepient_tel 发票收票电话
     * @param string $invoice_recepient_name 发票收票人
     * @param string $invoice_bank_name 专票:开户行
     * @param string $invoice_bank_account 专票:开户账号
     */
    public function edit_invoice()
    {
        $invoice = InvoiceModel::where('user_id',$this->auth->id)->find();

        $invoice_type = $this->request->post('invoice_type','');// 发票种类:1=普通发票,2=专用发票
        $invoice_title = $this->request->post('invoice_title','');// 发票抬头
        $invoice_company_number = $this->request->post('invoice_company_number','');// 发票税号
        $invoice_company_addr = $this->request->post('invoice_company_addr','');// 发票详细地址
        $invoice_company_tel = $this->request->post('invoice_company_tel','');// 发票电话
        $invoice_recepient_email = $this->request->post('invoice_recepient_email','');// 发票接收邮箱
        $invoice_recepient_name = $this->request->post('invoice_recepient_name','');// 发票接收人
        $invoice_recepient_addr = $this->request->post('invoice_recepient_addr','');// 发票接收地址
        $invoice_recepient_tel = $this->request->post('invoice_recepient_tel','');// 发票接收电话
        $invoice_bank_name = $this->request->post('invoice_bank_name','');// 专票:开户行
        $invoice_bank_account = $this->request->post('invoice_bank_account','');// 专票:银行账户

        if(!$invoice_type || !$invoice_title || !$invoice_company_number || !$invoice_company_addr || !$invoice_company_tel || !$invoice_recepient_email || !$invoice_recepient_name || !$invoice_recepient_addr || !$invoice_recepient_tel)
        {
            $this->error("发票信息不能为空");
        }
        if($invoice_type == '2' && (!$invoice_bank_name || !$invoice_bank_account)){
            $this->error("专票信息不能为空");
        }
        $invoice->user_id = $this->auth->id;
        $invoice->type = $invoice_type;
        $invoice->title = $invoice_title;
        $invoice->company_number = $invoice_company_number;
        $invoice->company_addr = $invoice_company_addr;
        $invoice->company_tel = $invoice_company_tel;
        $invoice->recepient_email = $invoice_recepient_email;
        $invoice->recepient_name = $invoice_recepient_name;
        $invoice->recepient_addr = $invoice_recepient_addr;
        $invoice->recepient_tel = $invoice_recepient_tel;
        $invoice->bank_name = $invoice_bank_name;
        $invoice->bank_account = $invoice_bank_account;

        Db::startTrans();
        try {
            $invoice->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success("操作成功",$invoice);
    }

    /**
     * 我的发票记录
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @param string $status  类型：0=未审核,1=已通过,2=已拒绝,''空为全部
     * @param string $sn  订单号
     */
    public function invoice_log()
    {
        $limit = $this->request->post('limit');
        $status = $this->request->post('status','');
        $sn = $this->request->post('sn','');
        $where = [];
        if(is_numeric($status)){
          $where['a.status'] = $status;
        }
        if($sn){
            $where['a.sn'] = $sn;
        }
        $where['b.status'] = '5';
        $data = InvoiceLogModel::alias("a")
            ->join("order b",'a.order_id = b.id','left')
            ->join("user c",'a.user_id = c.id','left')
            ->where('a.user_id', $this->auth->id)
            ->where($where)
            ->order('a.createtime','desc')
            ->field("a.*,b.title,b.status as order_status,a.title as company_name,a.bank_name as id_no_bank_name,a.bank_account as id_no_bank_id,a.bank_name as company_bank_name,a.bank_account as company_bank_id,FROM_UNIXTIME(a.createtime, '%Y-%m-%d %H:%i:%s') AS createtime")
            ->paginate($limit);
        $this->success('请求成功', $data);
    }

    /**
     * 我收藏的专家列表
     *
     * @ApiMethod (POST)
     * @ApiSummary  (测试描述信息)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="专家列表")
     * @ApiReturn   ({
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
    public function fav_specialist_list()
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
        $where = [];
        
        
        
        $industry_ids = $this->request->post('industry_ids','');
        $industry_arr = $industry_ids!=='' ? explode(',', $industry_ids) : [];

        $skill_ids = $this->request->post('skill_ids','');
        $skill_arr = $skill_ids!=='' ? explode(',', $skill_ids) : [];

        $area_ids = $this->request->post('area_ids','');
        $area_arr = $area_ids!=='' ? explode(',', $area_ids) : [];

        $tag_arr = array_merge($industry_arr,$skill_arr,$area_arr);

        $model = $model->alias('s')
        ->join('specialist_fav sf','sf.specialist_user_id = s.user_id')
        ->field('s.*,u.avatar,u.role_type,u.typedata,sf.user_id as sf_user_id')
        ->where('sf.user_id',$this->auth->id);
        // $model = $model->hidden(['s.createtime','s.updatetime','s.id_no_front_image','s.id_no_backend_image','s.id_no','s.status']);
        if(count($tag_arr)>0){
            $model = $model->join('tag_specialist ts','s.id = ts.specialist_id')
            ->where('ts.tag_id','in', $tag_arr);
        }
        if ($keyword) {
            $model = $model->where('s.name|s.nickname', 'like', "%$keyword%");
        }
        if($lowest_price){
            $model = $model->where('s.lowest_price','>=', $lowest_price);
        }
        // dump($where);die;
        $list = $model->join('user u','s.user_id = u.id','left')->where('s.status','1')->where($where)
        ->order('s.id', "desc")
        ->group('s.id')
        
        ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        $this->success('', $result);
    }

    /**
     * 我的消息记录
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $type     0-全部，1-已读，2-未读
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     */
    public function message()
    {
        $type = $this->request->post('type',0);
        $limit = $this->request->post('limit');
        $model = new MessageModel();
        if(intval($type) === 1){
            $model = $model->where('read', 1);
        }
        if(intval($type) === 2){
            $model = $model->where('read', 0);
        }
        $data = $model->where('user_id',$this->auth->id)->order("id desc")->paginate(100);
        foreach($data as $v){
            $v->url = str_replace('/#','',$v->url);
        }
        $this->success('请求成功', $data);
    }

    /**
     * 我的消息-标记已读
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id    消息ID
     */
    public function read_message()
    {
        $id = $this->request->post('id',0);
        $limit = $this->request->post('limit');
        $model = new MessageModel();
        $data = $model->where('user_id',$this->auth->id)->where('id',$id)->find();
        if(!$data){
            $this->error('消息不存在');
        }
        $data->read = 1;
        $data->save();
        $this->success('请求成功', $data);
    }

    /**
     * 我的账单
     *
     * @ApiMethod (POST)
     * @ApiSummary  (我的账单)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $status     //0-冻结中，1-已发放
     * @param string $type     //1-服务费,2-其他费用
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="专家列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "请求成功",
        "time": "1706760718",
        "data": [
            {
            "id": 1,
            "order_id": 5,//关联订单ID
            "total": "1000.00",//金额
            "rate_fee": "1.00",//税点金额
            "rate_fee_per": 10,//税点比例（万分之）
            "sys_fee": "1.00",//服务费金额
            "sys_fee_per": 10,//平台服务费比例（万分之）
            "real_total": "9920.00",//实收金额
            "bank_account": "123",//发放账号
            "bank_username": "1",//发放人
            "status": "0",//0-冻结中，1-已发放
            "createtime": 1705226635,
            "updatetime": 1705226635,
            "deletetime": null,
            "order_sn": "11",//关联订单号
            "user_id": 16,//用户ID
            "createtime_text": "2024-01-14 18:03:55",
            "updatetime_text": "2024-01-14 18:03:55",
            "status_text": "冻结中",
             "type": "1",//1-服务费，2-其他费用
            }
        ],
        "domain": "http://supply.test"
        })
     */
    public function bill(){
        $status = $this->request->post('status',0);
        $type = $this->request->post('type','1');
        $limit = $this->request->post('limit');


        //冻结
        $freeze = Db::name("requirement_specialist")
            ->alias("a")
            ->join("order b","a.id = b.requirement_specialist_id")
            ->where(['a.user_id' => $this->auth->id])

            ->where("b.status","<","5")
            ->where("b.status",">","0")
            ->SUM("b.total");
        //发放
        $grant = OrderBillModel::alias('a')
            ->join("order b","a.order_id = b.id","left")
            ->join("requirement_specialist c","b.requirement_specialist_id = c.id","left")
            ->where('c.user_id',$this->auth->id)
            ->where('a.status','1')
            ->where('a.type','1')->SUM("a.real_total");

        if($status == '0'){
            $data = Db::name("requirement_specialist")
                ->alias("a")
                ->join("order b","a.id = b.requirement_specialist_id")
                ->where(['a.user_id' => $this->auth->id])
                ->where("b.status",">","0")
                ->where("b.status","<","5")
                ->field("b.total,b.id as order_id,b.sn,b.createtime")
                ->order('b.createtime','desc')
                ->paginate($limit);
        }else{
            $data = OrderBillModel::alias('a')
                ->join("order b","a.order_id = b.id","left")
                ->join("requirement_specialist c","b.requirement_specialist_id = c.id","left")
                ->where('c.user_id',$this->auth->id)
                ->where('a.status',$status)
                ->where('a.type',$type)
                ->field("a.*,b.sn,b.createtime")
                ->order('b.createtime','desc')
                ->paginate($limit);
        }
        $data = $data->toArray();
        foreach($data['data'] as $k=>$v){
            $data['data'][$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }

        $this->success('请求成功', ['freeze' => $freeze,'grant' => $grant,'list' => $data]);

    }


    public function getNotice(){
        $id = $this->request->post('id',0);
        $info = Db::name("notice")->where(['id' => $id])->find();
        $info['createtime'] = date("Y-m-d H:i:s",$info['createtime']);
        //浏览人数+1
        Db::name("notice")->where(['id' => $id])->update(['num'=>$info['num']+1]);
        $this->success('请求成功',$info);
    }
}
