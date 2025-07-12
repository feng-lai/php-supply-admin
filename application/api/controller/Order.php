<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Order as OrderModel;
use app\common\model\OrderPay;
use app\common\model\OrderPay as OrderPayModel;
use app\common\model\OrderPayLog as OrderPayLogModel;
use app\common\model\OrderPayDetail as OrderPayDetailModel;
use app\common\model\OrderExcp as OrderExcpModel;
use app\common\model\OrderPayOffline as OrderPayOfflineModel;
use app\common\model\Config as ConfigModel;
use app\common\model\OrderAcceptance as OrderAcceptanceModel;
use app\common\model\RequirementSpecialist as RequirementSpecialistModel;
use app\common\model\Specialist as SpecialistModel;
use app\common\model\Requirement as RequirementModel;
use app\common\model\Pay as PayModel;
use app\common\model\User as UserModel;
use app\common\model\OrderComment as OrderCommentModel;
use app\common\model\Invoice as InvoiceModel;
use app\common\model\InvoiceLog as InvoiceLogModel;

use think\Request;
use addons\epay\library\Service;
use Exception;
use think\addons\Controller;
use think\Response;
use think\Session;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Pay;
use EasyWeChat\Factory;
use think\Config;

use think\Db;



/**
 * 订单
 *
 * @icon fa fa-circle-o
 */
class Order extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];
    /**
     * Home模型对象
     * @var \app\admin\model\order\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\Order;
    }



    /**
     * 个人中心(需求方)-我的订单
     *
     * @ApiMethod (POST)
     * @ApiSummary  (需求方-我发出的订单)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $type 状态(不传获取全部):1-服务中,2-已完成,3-已取消,4-异常
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="需求列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1705219254",
        "data": {
            "total": 1,
            "rows": [
            {
                "id": 5,
                "sn": "2024011216191248481014",
                "rid": 18,
                 "is_invoice": null, //是否有发票
                 "is_comment": null, //是否有评价
                "requirement_specialist_id": 2,
                "title": "就你了",//订单标题
                "desc": "快点开始",//订单说明
                "user_id": 16,//发布用户id
                "specialist_id": 18,//服务专家id
                "total": "3000.00",//订单金额
                "num": 2,//付款总期次
                "now_point": 1,//当前节点
                "status": "0",//status 状态:0-服务中-待付款,1-服务中-待审核,2-服务中-服务中,3-服务中-待验收,4-服务中-待跟进,5-已完成,6-已取消,8-异常待处理 9-正常进行 10-已终止
                "specialist_source": 3,//专家来源:0-未匹配,1-推荐,2-预约申请,3-专家主动申请
                "need_acceptance": "0",//是否需要验收:0-无需验收,1-需要验收
                "need_invoice": "1",//是否开票:0-不需要,1-需要
                "createtime": 1705047552,
                "updatetime": 1705047552,
                "deletetime": null,
                 "pay_amount":1000,
                "finishtime": 1705047552,
                "confirm_day": 0,//确认时效：（天数）
                "confirm": "1",//确认状态:0=未确认,1=已确认,2=已超时
                "cancel_reason": null,//取消原因
                "cancel_user_id": null,//取消发起用户
                "status_text": "待收款",
                "need_acceptance_text": "无需验收",
                "finishtime_text": "2024-01-12 16:19:12",
                "createtime_text": "2024-01-12 16:19:12",
                "updatetime_text": "2024-01-12 16:19:12",
                "confirm_text": "专家已确认",
                "invoice_id": 0,//关联的发票ID
                "is_excp": 0,//是否存在异常:0-正常,1-异常,
                "excp": [//异常信息
                {
                    "id": 1,//异常ID
                    "order_id": 5,//关联的订单ID
                    "order_pay_id": 1,//关联的付款期次ID
                    "cate": "1",//发起方:1=需求方,2=专家
                    "desc": "还未付款",//描述
                    "status": "0",//处理状态:0=待处理,1=正常进行,2=已终止
                    "user_id": 16,//发起人ID
                    "deal_info": null,//处理结果
                    "createtime": 1705226635,
                    "updatetime": 1705226635,
                    "deletetime": null,
                    "createtime_text": "2024-01-14 18:03:55",
                    "updatetime_text": "2024-01-14 18:03:55",
                    "status_text": "未处理",
                    "cate_text": "需求方发起"
                }
                ],
            }
            ]
        },
        "domain": "http://supply.test"
        })
     */
    public function index(){
        $limit = $this->request->post('limit',10);
        $keyword = $this->request->post('keyword','');
        $status = $this->request->post('type','');

        if($this->auth->role_type === '2'){
            // 需求方 数据隔离
            $this->error("只有需求方才能获取");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证需求方才能获取");
        }
        //status 状态:0-服务中-待付款,1-服务中-待审核,2-服务中-服务中,3-服务中-待验收,4-服务中-待跟进,5-已完成,6-已取消 
        //confirm 确认状态:0=未确认,1=已确认,2=已超时
        $where = [];
        $model = new OrderModel();

        if($status==''){
            
        }else if(intval($status)===1){
            //服务中
            $model = $model->whereIn('a.status',['0','1','2','3','4']);
        }else if(intval($status)===2){
            //已完成
            $model = $model->whereIn('a.status',['5']);
        }else if(intval($status)===3){
            //已取消
            $model = $model->whereIn('a.status',['6']);
        }else if(intval($status)===4){
            //异常
            $model = $model->where('a.is_excp',1);
        }
        
        $list = $model
            ->alias("a")
            ->join("requirement_specialist b","a.requirement_specialist_id = b.id",'left')
            //->join('order_pay p','p.order_id = a.id and a.now_point = p.idx')
            ->join('requirement r','r.id = a.rid')
            ->where("b.status <> '0' and b.status <> '4'")
            ->where('a.user_id',$this->auth->id)
            ->where("a.confirm = '1'")
            ->field("a.*,r.begin,r.end")
            ->order('a.id', "desc")
        ->paginate($limit)->each(function($item, $key) use ($status){
            $item['pay_amount'] = OrderPay::where('order_id',$item->id)->where('idx',$item['now_point'])->value('total');
            $item['is_invoice'] = InvoiceLogModel::where(['order_id' => $item['id']])->whereIn('status',[0,1])->find() ? 1 : 0;
            $item['invoice_id'] = InvoiceLogModel::where(['order_id' => $item['id']])->value("id");
            $item['is_comment'] = OrderCommentModel::where(['order_id' => $item->id])->find();
            $item['excp'] = OrderExcpModel::whereIn('order_id',$item->id)->order('id','desc')->select();
//            if(intval($status)===4){
//                $item['status_text'] = "异常待处理";
//            }
//            if(!empty($item['excp'])){
//                $item['status'] = 9;
//            }
            $excp_0 = OrderExcpModel::whereIn('order_id',$item->id)->where(['status' => '0'])->order('id','desc')->find();

            if($excp_0){
                $item['status'] = 8; //异常待处理
                $item['status_text'] = "异常待处理";
            }

            $excp_2 = OrderExcpModel::whereIn('order_id',$item->id)->where(['status' => '2'])->order('id','desc')->find();

            if($excp_2){
                $item['status'] = 10; //异常待处理
                $item['status_text'] = "已终止";
            }

//            if(!empty($excp)){
//                if($excp['status'] == '0'){
//                    $item['status'] = 8; //异常待处理
//                    $item['status_text'] = "异常待处理";
//                }
//                if($excp['status'] == '1'){
//                    $item['status'] = 9; //正常进行
//                    $item['status_text'] = "正常进行";
//                }
//                if($excp['status'] == '2'){
//                    $item['status'] = 10; //已终止
//                    $item['status_text'] = "已终止";
//                }
//            }
            return $item;
        });
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        $this->success('', $result);
    }

    /**
     * 个人中心(专家)-我的订单
     *
     * @ApiMethod (POST)
     * @ApiSummary  (专家-我收到的订单)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $type 状态(不传获取全部):1-服务中,2-已完成,3-已取消,4-异常
     * @param string $page     页数：默认1
     * @param string $limit     条数：默认10
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="需求列表")
     * @ApiReturn   ({
         "code": 1,
        "msg": "",
        "time": "1705219254",
        "data": {
            "total": 1,
            "rows": [
            {
                "id": 5,
                "sn": "2024011216191248481014",
                "rid": 18,
                "requirement_specialist_id": 2,
                "title": "就你了",//订单标题
                "desc": "快点开始",//订单说明
                "user_id": 16,//发布用户id
                "specialist_id": 18,//服务专家id
                "total": "3000.00",//订单金额
                "num": 2,//付款总期次
                "now_point": 1,//当前节点
                "status": "0",//status 状态:0-服务中-待收款,1-服务中-待审核,2-服务中-服务中,3-服务中-待验收,4-服务中-待跟进,5-已完成,6-已取消
                "specialist_source": 3,//专家来源:0-未匹配,1-推荐,2-预约申请,3-专家主动申请
                "need_acceptance": "0",//是否需要验收:0-无需验收,1-需要验收
                "need_invoice": "1",//是否开票:0-不需要,1-需要
                "createtime": 1705047552,
                "updatetime": 1705047552,
                "deletetime": null,
                "finishtime": 1705047552,
                "confirm_day": 0,//确认时效：（天数）
                "confirm": "1",//确认状态:0=未确认,1=已确认,2=已超时
                "cancel_reason": null,//取消原因
                "cancel_user_id": null,//取消发起用户
                "status_text": "待收款",
                "need_acceptance_text": "无需验收",
                "finishtime_text": "2024-01-12 16:19:12",
                "createtime_text": "2024-01-12 16:19:12",
                "updatetime_text": "2024-01-12 16:19:12",
                "confirm_text": "专家已确认",
                "invoice_id": 0,//关联的发票ID
                "is_excp": 0,//是否存在异常:0-正常,1-异常,
                "excp": [//异常信息
                {
                    "id": 1,//异常ID
                    "order_id": 5,//关联的订单ID
                    "order_pay_id": 1,//关联的付款期次ID
                    "cate": "1",//发起方:1=需求方,2=专家
                    "desc": "还未付款",//描述
                    "status": "0",//处理状态:0=待处理,1=正常进行,2=已终止
                    "user_id": 16,//发起人ID
                    "deal_info": null,//处理结果
                    "createtime": 1705226635,
                    "updatetime": 1705226635,
                    "deletetime": null,
                    "createtime_text": "2024-01-14 18:03:55",
                    "updatetime_text": "2024-01-14 18:03:55",
                    "status_text": "未处理",
                    "cate_text": "需求方发起"
                }
                ],
            }
            ]
        },
        "domain": "http://supply.test"
        })
     */
    public function specialist_index(){
        $limit = $this->request->post('limit',10);
        $keyword = $this->request->post('keyword','');
        $status = $this->request->post('type','');

        if($this->auth->role_type === '1'){
            // 专家 数据隔离
            $this->error("只有专家才能申请");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证专家才能获取");
        }

        $where = [];
        $model = new OrderModel();
        if($status==''){
            
        }else if(intval($status)===1){
            //服务中
            $model = $model->whereIn('status',['0','1','2','3','4'])->where('is_excp',0);
        }else if(intval($status)===2){
            //已完成
            $model = $model->whereIn('status',['5'])->where('is_excp',0);
        }else if(intval($status)===3){
            //已取消
            $model = $model->whereIn('status',['6'])->where('is_excp',0);
        }else if(intval($status)===4){
            //异常
            //异常
            $model = $model->where('is_excp',1);
        }
        
        $list = $model
        ->where('specialist_id',$this->auth->id)
        // ->where($where)
        ->where('confirm','1')
        ->order('id', "desc")
        ->paginate($limit)->each(function($item, $key){
            //$order_pay = OrderPay::where(['idx'=>$item->now_point,'order_id'=>$item->id])->find();
            //$item['begin'] = $order_pay->begin;
            //$item['end'] = $order_pay->end;
                $times = RequirementModel::where('id',$item->rid)->find();
                $item['begin'] = $times->begin;
                $item['end'] = $times->end;
            $item['is_comment'] = OrderCommentModel::whereIn('order_id',$item->id)->find() ? 1 : 2;
            $item['excp'] = OrderExcpModel::whereIn('order_id',$item->id)->order('id','desc')->select();

                $excp_0 = OrderExcpModel::whereIn('order_id',$item->id)->where(['status' => '0'])->order('id','desc')->find();

                if($excp_0){
                    $item['status'] = 8; //异常待处理
                    $item['status_text'] = "异常待处理";
                }

                $excp_2 = OrderExcpModel::whereIn('order_id',$item->id)->where(['status' => '2'])->order('id','desc')->find();

                if($excp_2){
                    $item['status'] = 10; //异常待处理
                    $item['status_text'] = "已终止";
                }

            return $item;
        });
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        $this->success('', $result);
    }

    /**
     * 订单详情
     *
     * @ApiMethod (POST)
     * @ApiSummary  (获取订单详情)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id     订单ID
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="需求列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1706595008",
        "data": {
            "user_nickname": "188****8444",//需求方昵称
            "specialist_nickname": "130****9999",//专家昵称
            "user_id_no_name": "陈",//需求方姓名
            "specialist_id_no_name": "周",//专家姓名
            "id": 5,//订单id 
            "sn": "2024011216191248481014",//订单编号
            "rid": 18,//关联需求ID
            "requirement_specialist_id": 2,//关联的需求参与ID
            "title": "就你了",//订单标题
            "desc": "快点开始",//订单描述
            "user_id": 16,//用户ID
            "specialist_id": 18,//专家ID
            "total": "3000.00",//总金额
            "num": 2,//期次
            "now_point": 2,//当前期次
            "status": "3",//状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 ,8-异常待处理 9-正常进行 10-已终止
            "specialist_source": 3,//专家来源:0-未匹配,1-推荐,2-预约申请,3-专家主动申请
            "need_acceptance": "1",//是否需要验收:0-无需验收,1-需要验收
            "need_invoice": "0",//是否开票:0-不需要,1-需要
            "createtime": 1705047552,
            "updatetime": 1706532749,
            "deletetime": null,
            "finishtime": 1737978655,
            "confirm_day": 365,//确认时效（天数）
            "confirm": "1",//确认状态:0=未确认,1=已确认,2=已超时
            "cancel_reason": null,//订单取消原因
            "cancel_user_id": null,//取消发起用户
            "is_excp": 0,是否存在异常:0-正常,1-异常,
            "invoice_id": 0,
            "pay": [{//付款期次
                "id": 1,
                "order_id": 5,
                "idx": 1,
                "desc": "预付款",
                "begin": "2023年9月18日",
                "end": "2023年10月18日",
                "total": 1000,//付款金额
                "is_pay": "5",//状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 
                "createtime": 1705047552,
                "updatetime": 1706531312,
                "deletetime": null,
                "is_excp": 0,//异常状态:0-正常,1-异常
                "pay_type": "0",//支付方式:0-未选择,1-微信,2-支付宝,3-线下
                "detail": [{//当前付款期次节点下的明细大节点
                    "id": 2,
                    "order_id": 5,//所属订单ID
                    "idx": 1,//所属付款期次
                    "createtime": 1706526864,
                    "updatetime": 1706526864,
                    "deletetime": null,
                    "type": "0",
                    "tip_1": "待用户托管支付金额1000元",//描述（平台用）
                    "tip_2": "1000元",//描述（需求方用）
                    "tip_3": "1000元",//描述（专家用）
                    "status_1": "待收款",//状态（平台用）
                    "status_2": "待支付",//状态（需求方用）
                    "status_3": "待收款",//状态（专家用）
                    "log": [//当前明细大节点下的小节点
                        {
                        "id": 1,
                        "order_id": 5,
                        "order_pay_id": 1,
                        "tpye": 0,
                        "order_pay_detail_id": 2,//关联的明细大节点ID
                        "idx": 1,//所属付款期次
                        "createtime": 1706527694,
                        "updatetime": 1706527694,
                        "deletetime": null,
                        "tip_1": "2024-01-29 19:28用户通过线下转账支付托管金额1000元",//描述（平台用）
                        "tip_2": "通过线下转账支付托管金额1000元，等待平台审核",//描述（需求方用）
                        "tip_3": "用户通过线下转账支付托管金额1000元，等待平台审核",//描述（专家用）
                        "msg_files": null,//上传文件,多个隔开
                        "msg_user_id": null,//留言用户ID
                        "msg_user_type": "0",//用户类型:0-未知,1-需求方,2-专家,3-平台
                        "msg": null,//用户留言
                        "createtime_text": "2024-01-29 19:28:14",
                        "updatetime_text": "2024-01-29 19:28:14",
                        "type_text": ""
                        }
                    ]
                }],
                "is_pay_text": "已完成"
            }],
            "excp": [{//异常信息
                "id": 1,//异常ID
                "order_id": 5,//关联的订单ID
                "order_pay_id": 1,//关联的付款期次ID
                "cate": "1",//发起方:1=需求方,2=专家
                "desc": "还未付款",//描述
                "status": "0",//处理状态:0=待处理,1=正常进行,2=已终止
                "user_id": 16,//发起人ID
                "deal_info": null,//处理结果
                "createtime": 1705226635,
                "updatetime": 1705226635,
                "deletetime": null,
                "createtime_text": "2024-01-14 18:03:55",
                "updatetime_text": "2024-01-14 18:03:55",
                "status_text": "未处理",
                "cate_text": "需求方发起"
            }],
            "status_text": "待验收",
            "need_acceptance_text": "需要验收",
            "finishtime_text": "2025-01-27 19:50:55",
            "createtime_text": "2024-01-12 16:19:12",
            "updatetime_text": "2024-01-29 20:52:29",
            "confirm_text": "专家已确认"
        },
        "domain": "http://supply.test"
        })
     */
    public function detail(){
        $id = $this->request->post('id','');
        if($id==''){
            return $this->error('参数错误');
        }
        $where = [];
        $model = new OrderModel();
        $data = $model
        ->where('user_id|specialist_id',$this->auth->id)
        ->where('id',$id)
        ->order('id', "desc")
        ->find();
        $data['invoice_id'] = Db::name("invoice_log")->where(['order_id' => $data['id']])->order("id desc")->value("id");
        $user = UserModel::where('id',$data->user_id)->find();
        $specialist = UserModel::where('id',$data->specialist_id)->find();
        $data['user_nickname'] = $user->nickname;
        $data['specialist_nickname'] = $specialist->nickname;
        $data['user_id_no_name'] = $user->id_no_name;
        $data['specialist_id_no_name'] = $specialist->id_no_name;
        //是否有评价
        $data['has_comment'] = OrderCommentModel::where('order_id',$id)
        ->where('user_id',$this->auth->id)->order('id','desc')
        ->count();
        //付款期次
        $pays = OrderPayModel::where('order_id',$id)->order('idx','asc')->select();
        foreach ($pays as $k => &$v) {
            //
            $detail = OrderPayDetailModel::where('order_id',$id)->where('idx',$v->idx)->select();
            foreach ($detail as $kk => &$vv) {
                $vv['log'] = OrderPayLogModel::where('order_pay_detail_id',$vv->id)->select();
            }
            $v['detail'] = $detail;

        }

        $data['pay'] = $pays;
        // $data['log'] = OrderPayLogModel::where('order_id',$id)->order('id','desc')->select();
        $data['excp'] = OrderExcpModel::alias('e')->join('user u','e.user_id = u.id')->field('e.*,u.nickname as user_nickname,u.id_no_name as user_id_no_name')->where('e.order_id',$id)->order('e.id','desc')->select();

//        if(intval($data['status'])===4){
//            $data['status_text'] = "异常待处理";
//        }
//->where("status != '0'")
        $excp = OrderExcpModel::where('order_id',$data['id'])->where(['status' => '0'])->order('id','desc')->find();
        if(!empty($excp)){
            $data['status'] = 8; //异常待处理
        }
        $excp_2 = OrderExcpModel::where('order_id',$data['id'])->where(['status' => '2'])->order('id','desc')->find();
        if(!empty($excp_2)){
            $data['status'] = 10; //已终止
        }


        $data['invoice_type'] = $data['invoice_id'] ? Db::name("invoice")->where(['id' => $data['invoice_id']])->value("type") : '';

        $data['requirement_specialist'] = Db::name("requirement_specialist")->where(['id' => $data['requirement_specialist_id']])->find();

        $this->success('', $data);
    }

    /**
     * 发起异常
     *
     * @ApiMethod (POST)
     * @ApiSummary  (针对某节点发起异常)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $order_id     订单ID
     * @param string $desc     异常说明
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="需求列表")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1705219254",
        "data": {
            "id": 5,
            "sn": "2024011216191248481014",//订单编号
            "rid": 18,//关联匹配申请的ID
            "requirement_specialist_id": 2,//关联需求的ID
            "title": "就你了",//订单标题
            "desc": "快点开始",//订单描述
            "user_id": 16,//所属用户
            "specialist_id": 18,//关联专家的user_id
            "total": "3000.00",//订单总额
            "num": 2,//付款期次
            "now_point": 1,//当前付款期次
            "status": "0",//0-待审核,1-匹配中,2-待确认,3-已匹配,4-已取消,5-已失效
            "specialist_source": 3,//专家来源:1-系统推荐,2-需求方预约,3-专家主动申请
            "need_acceptance": "0",//0-无需验收,1-需要验收
            "need_invoice": "1",//0-无需发票,1-需要发票
            "createtime": 1705047552,
            "updatetime": 1705047552,
            "deletetime": null,
            "finishtime": 1705047552,//订单确认时效截止时间
            "confirm_day": 0,//订单确认时效天数
            "confirm": "0",//0-专家未确认,1-专家已确认
            "status_text": "待收款",
            "need_acceptance_text": "无需验收",
            "finishtime_text": "2024-01-12 16:19:12",
            "createtime_text": "2024-01-12 16:19:12",
            "updatetime_text": "2024-01-12 16:19:12",
            "confirm_text": "专家确认订单",
            "pay": [//付款信息
                {
                    "id": 1,
                    "order_id": 5,
                    "idx": 1,
                    "desc": "预付款",
                    "begin": "2023年9月18日",
                    "end": "2023年10月18日",
                    "total": 1000,
                    "is_pay": "0",//付款状态:0-未付款,1-待审核,2-服务中,3-已完成,4-已取消,5-异常
                    "createtime": 1705047552,
                    "updatetime": 1705047552,
                    "deletetime": null,
                    "is_pay_text": "未付款"
                },
                {
                    "id": 2,
                    "order_id": 5,
                    "idx": 2,
                    "desc": "尾款",
                    "begin": "2023年10月18日",
                    "end": "2023年11月18日",
                    "total": 2000,
                    "is_pay": "0",
                    "createtime": 1705047553,
                    "updatetime": 1705047553,
                    "deletetime": null,
                    "is_pay_text": "未付款"
                }
            ],
            "log": []//节点明细
        },
        "domain": "http://supply.test"
        })
     */
    public function createExcp(){
        
        $order_id = $this->request->post('order_id','');
        $order = OrderModel::where('id',$order_id)->find();
        
        if(!$order){
            return $this->error('订单不存在');
        }

//        if($order->status == '5'){
//            return $this->error('操作失败：订单已完成');
//        }
        if($order->status == '6'){
            return $this->error('操作失败：订单已取消');
        }
        $orderPay = OrderPayModel::where('order_id',$order_id)->where('idx',$order->now_point)->find();;
        if(!$orderPay){
            return $this->error('节点不存在');
        }
        $order->is_excp = 1;
        $orderPay->is_excp = 1;
        $desc = $this->request->post('desc','');
        if($desc==''){
            return $this->error('请填写异常说明');
        }
        //判断当前用户类型
        $cate = $this->auth->role_type === '1'?'1':'2';

        $model = new OrderExcpModel();
        $model->order_pay_id = $orderPay->id;
        $model->order_id = $order->id;
        $model->desc = $desc;
        $model->cate = $cate;
        $model->user_id = $this->auth->id;
        $model->status = '0';
        $model->is_pay = $order->status;

        //获取当前大节点
        $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
        if(!$points_detail){
            $this->error("找不到节点详细信息");
        }
        //节点明细小节点
        $orderPayLogModel = new OrderPayLogModel();
        $orderPayLogModel->order_id = $order->id;
        $orderPayLogModel->order_pay_id = $orderPay->id;
        $orderPayLogModel->tpye = $orderPay->is_pay;
        $orderPayLogModel->order_pay_detail_id = $points_detail->id;
        $orderPayLogModel->idx = $order->now_point;
        $role_type = $this->auth->role_type=='1'?'需求方':'专家';
        $orderPayLogModel->tip_1 = date('Y-m-d H:i')."{$role_type}{$this->auth->nickname}发起异常";
        $orderPayLogModel->tip_2 = date('Y-m-d H:i')."{$role_type}{$this->auth->nickname}发起异常";
        $orderPayLogModel->tip_3 = date('Y-m-d H:i')."{$role_type}{$this->auth->nickname}发起异常";

        Db::startTrans();
        try {

            setPostMessage(2,$order->specialist_id,'编号'.$order->sn.'订单已异常，点击查看详情','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);
            setPostMessage(2,$order->user_id,'编号'.$order->sn.'订单已异常，点击查看详情','/myOrder/detail?status='.$order->status.'&id='.$order->id);
            $model->save();
            $order->save();
            $orderPay->save();
            $orderPayLogModel->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success('操作成功');
    }

    /**
     * 需求方-取消订单
     * @ApiMethod (POST)
     * @ApiSummary  (未支付前随时取消;支付后走异常申请流程)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   订单ID
     * @param string $reason 取消原因
     */
    public function cancel_by_user(){

        $id = $this->request->post('id');
        $reason = $this->request->post('reason','');
        if($reason == ''){
            $this->error("请填写取消原因");
        }
        $model = new OrderModel();
        $order = $model->where('id', $id)->where('user_id',$this->auth->id)->order('id','desc')->find();
        
        if(!$order){
            $this->error("订单不存在",'');
        }
        // 状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消
        if($order->status === '6'){
            $this->error("订单已取消");
        }
//        if($order->status === '0'){
            ### 正常取消
            Db::name("requirement")->where(['id' => $order->rid])->update(['status' => 2]);
            $order->status = '6';
            $order->cancel_reason = $reason;
            $order->user_id = $this->auth->id;
            Db::startTrans();
            try {

                $order->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error("操作失败",$e->getMessage());
            }
//        }else{
//            ### 走异常申请
            $orderPay = Db::name("order_pay")->where('order_id',$id)->where("is_pay <> '5'")->select();
            foreach($orderPay as $key=>$val){
                $points_detail_id = Db::name("order_pay_detail")->insertGetId([
                    'order_id' => $order->id,
                    'idx' => $val['idx'],
                    'createtime' => time(),
                    'updatetime' => time(),
                    'type' => '6',
                    'tip_1' => '用户取消订单',
                    'tip_2' => '用户取消订单',
                    'tip_3' => '',
                    'status_1' => '取消订单',
                    'status_2' => '取消订单',
                    'status_3' => '取消订单'
                ]);
                Db::name("order_pay")->where(['id' => $val['id']])->update(['is_pay' => 6]);
                $orderPayLogModel = new OrderPayLogModel();
                $orderPayLogModel->order_id = $order->id;
                $orderPayLogModel->order_pay_id = $val['id'];
                $orderPayLogModel->tpye = 4;
                $orderPayLogModel->order_pay_detail_id = $points_detail_id;
                $orderPayLogModel->idx = $val['idx'];
                $orderPayLogModel->tip_1 = date('Y-m-d H:i')."用户取消订单  取消说明：".$reason;
                $orderPayLogModel->tip_2 = "取消说明：".$reason;
                $orderPayLogModel->tip_3 = "取消说明：".$reason;
                $orderPayLogModel->save();
            }


//            //判断当前用户类型
//            $cate = '1';
//            $model = new OrderExcpModel();
//            $model->order_pay_id = $orderPay->id;
//            $model->order_id = $order->id;
//            $model->desc = $reason;
//            $model->cate = $cate;
//            $model->user_id = $this->auth->id;
//            $model->status = '0';
//            Db::startTrans();
//            try {
//                $model->save();
//
//                Db::commit();
//            } catch (\Exception $e) {
//                Db::rollback();
//                $this->error("操作失败",$e->getMessage());
//            }
//        }

        $this->success("操作成功");

    }

    /**
     * 专家-取消订单
     * @ApiMethod (POST)
     * @ApiSummary  (专家随时取消)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   订单ID
     * @param string $reason 取消原因
     */
    public function cancel_by_specialist(){

        $id = $this->request->post('id');
        $reason = $this->request->post('reason','');
        if($reason == ''){
            $this->error("请填写取消原因");
        }
        $model = new OrderModel();
        $order = $model->where('id', $id)->where('specialist_id',$this->auth->id)->order('id','desc')->find();
        
        if(!$order){
            $this->error("订单不存在",'');
        }
        // 状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消
        if($order->status === '6'){
            $this->error("订单已取消");
        }
        ### 正常取消
        $order->status = '6';
        $order->cancel_reason = $reason;
        //$order->user_id = $this->auth->id;
        $order->cancel_type = 2;
        Db::startTrans();
        try {
            // 添加详细日志
            $pay_detail = new OrderPayDetailModel;
            $pay_detail->order_id = $id;
            $pay_detail->idx = $order->now_point;
            $pay_detail->tip_1 = '专家取消订单';
            $pay_detail->tip_2 = '专家取消订单';
            $pay_detail->tip_3 = '成功取消订单';
            $pay_detail->status_1 = '已取消';
            $pay_detail->status_2 = '已取消';
            $pay_detail->status_3 = '已取消';
            $pay_detail->save();


            $pay_log = new OrderPayLogModel;
            $pay_log->order_id = $id;
            $pay_log->order_pay_id = $id;
            $pay_log->order_pay_detail_id = $pay_detail->id;
            $pay_log->idx = $order->now_point;
            $pay_log->tip_1 = '取消原因：'.$reason;
            $pay_log->tip_2 = '取消原因：'.$reason;
            $pay_log->tip_3 = '取消原因：'.$reason;
            $pay_log->save();
            #1.退款

            #2.更新订单状态
            $order->save();
            //Db::name("requirement")->where(['id' => $order->rid])->update(['status' => 2]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }

        $this->success("操作成功");

    }


    /**
     * 获取平台第三方收款账户信息
     *
     * @ApiMethod (POST)
     * @ApiSummary  (无分页)
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'company_img':'string','company_info':'string'}", description="列表")
     * @ApiReturnParams   (name="domain", type="string", required=true, sample="http://xxx", description="域名")
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1704638976",
        "data": {
            "pay_name": "xx公司",//收款主体名称
            "pay_bank_name": "123"//收款银行
            "pay_bank_account": "xxx"//收款账号
        },
        "domain": "http://supply.test"
        })
     */
    public function pay_info(){

        $pay_name = ConfigModel::where('name','pay_name')->value('value');
        $pay_bank_name = ConfigModel::where('name','pay_bank_name')->value('value');
        $pay_bank_account = ConfigModel::where('name','pay_bank_account')->value('value');

        $result = [
            'pay_name'=>$pay_name,
            'pay_bank_name'=>$pay_bank_name,
            'pay_bank_account'=>$pay_bank_account
        ];
        $this->success('', $result);
    }

    /**
     * 需求方-发起付款(线下)
     *
     * @ApiMethod (POST)
     * @ApiSummary  (线下第三方付款转账凭证提交)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $order_id     订单ID
     * @param string $pay_tip     付款说明
     * @param string $pay_file     付款截图
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="")
     */
    public function pay_offline(){
        $order_id = $this->request->post('order_id','');
        if($order_id==''){
            return $this->error('参数错误:缺少order_id');
        }
        //
        
        //状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 
        $order = OrderModel::where('id',$order_id)->find();
        if(!$order){
            return $this->error('订单不存在');
        }
//        if($order->status > 0){
//            return $this->error('当前订单无法付款');
//        }
        $has = OrderPayOfflineModel::where('order_id',$order_id)->where('status',0)->find();
        if($has){
            return $this->error('当前已存在审核中的付款信息，请等候');
        }
        //找到第一个未付款的节点
        $orderPay = OrderPayModel::where('order_id',$order_id)->where("is_pay = '0' or is_pay = '7'")->order('idx','asc')->find();
        if(!$orderPay){
            return $this->error('没有未付款的节点');
        }
        // dump($orderPay );die;

        $pay_tip = $this->request->post('pay_tip','');
        if($pay_tip==''){
            return $this->error('请填写转账说明');
        }
        $pay_file = $this->request->post('pay_file','');
        if($pay_file==''){
            return $this->error('请上传付款凭证');
        }

        $model = new OrderPayOfflineModel();
        $model->order_id = $order_id;
        $model->order_pay_id = $orderPay->id;
        $model->pay_tip = $pay_tip;
        $model->pay_file = $pay_file;
        $model->status  = '0';
        $model->vertify_refuse_reason = "";
        $model->vertify_file = "";
        $model->pay_sn = $order->sn . '-' . mt_rand(100000, 999999);
        $order->status = '1';
        $orderPay->is_pay = '1';
        //获取当前付款节点
        $points = OrderPayModel::where('idx',$order->now_point)->where('order_id',$order->id)->find();
        if(!$points){
            $this->error("找不到节点信息");
        }
        //添加付款节点信息
        // $orderPayDetailModel = new OrderPayDetailModel();
        //获取当前大节点
        $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
        if(!$points_detail){
            $this->error("找不到节点详细信息");
        }
        // dump($points_detail);die;
        //节点明细小节点
        $orderPayLogModel = new OrderPayLogModel();
        $orderPayLogModel->order_id = $order->id;
        $orderPayLogModel->order_pay_id = $points->id;
        $orderPayLogModel->tpye = $points->is_pay;
        $orderPayLogModel->order_pay_detail_id = $points_detail->id;
        $orderPayLogModel->idx = $order->now_point;
        $orderPayLogModel->tip_1 = date('Y-m-d H:i')."用户通过线下转账支付托管金额{$points->total}元";
        $orderPayLogModel->tip_2 = "通过线下转账支付托管金额{$points->total}元，等待平台审核";
        $orderPayLogModel->tip_3 = "用户{$this->auth->nickname}通过线下转账支付托管金额{$points->total}元，等待平台审核";
        
        //判断当前用户类型
        Db::startTrans();
        try {

            //setPostMessage(2,$order->specialist_id,'您有一条新待服务的订单，点击查看详情','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);
            $model->save();
            $order->save();
            $orderPay->save();
            $orderPayLogModel->save();
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success('操作成功');
    }

    /**
     * 需求方-发起付款-支付宝(线上扫码)
     *
     * @ApiMethod (POST)
     * @ApiSummary  (返回form，拿到form在前端中提交跳转支付宝页面)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $order_id     订单ID
     * @param string $return_url   回调地址 不传就默认https://www.qianqiance.com/personCenter/myOrder
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="")
     */
    public function pay_alipay(){
        $request = Request::instance(); // 获取请求实例
        $url = $request->domain(); // 获取当前地址，不包
        $id = $this->request->post('order_id');
        $return_url = $this->request->post('return_url');
        //获取订单
        $order = OrderModel::where('id',$id)->where('user_id',$this->auth->id)->find();
        if(!$order){
            $this->error("订单不存在或没有权限");
        }
        
        //获取当前节点
        $points = OrderPayModel::where('idx',$order->now_point)->where('order_id',$order->id)->find();
        if(!$points){
            $this->error("找不到节点信息");
        }
        if($order->status > 0){
            return $this->error('当前订单不是未付款状态');
        }
        $has = OrderPayOfflineModel::where('order_id',$order->id)->where('status',0)->find();
        if($has){
            return $this->error('当前已存在审核中的付款信息，请等候');
        }
        //订单号
        $out_trade_no = $order->sn . '-' . mt_rand(100000, 999999);
        $params = [
            'amount'=>$points->total,
            'orderid'=>$out_trade_no,
            'type'=>"alipay",
            'title'=>$order->title,
            'method'=>"web",
            'openid'=>"",
            'auth_code'=>""
        ];
        // $res = \addons\epay\library\Service::getConfig();
        // $res = \addons\epay\library\Service::submitOrder($params);
        $config = Service::getConfig('alipay');
        $order->out_trade_no = $out_trade_no;
        $points->out_trade_no = $out_trade_no;
        $order->save();
        $points->save();
        $config['notify_url'] = $url."/addons/epay/api/notifyx/paytype/alipay";
        $config['return_url'] = $return_url?$return_url:"https://www.qianqiance.com/personCenter/myOrder";
    
        $web = Pay::alipay($config)->web([
            'out_trade_no' => $out_trade_no,
            'total_amount' => $points->total,
            'subject' => $order->title
        ]);
        $content = $web->getContent();
        $this->success('操作成功',$content);
        dump($web);die;
        die();
        $order_id = $this->request->post('order_id','');
        if($order_id==''){
            return $this->error('参数错误:缺少order_id');
        }
        
        //状态:0-待收款,1-待审核,2-服务中,3-待验收,4-待跟进,5-已完成,6-已取消 
        $order = OrderModel::where('id',$order_id)->find();
        if(!$order){
            return $this->error('订单不存在');
        }
        if($order->status > 0){
            return $this->error('当前订单无法付款');
        }

        //找到第一个未付款的节点
        $orderPay = OrderPayModel::where('order_id',$order_id)->where('is_pay','0')->order('idx','asc')->find();
        if(!$orderPay){
            return $this->error('没有未付款的节点');
        }

        $pay_tip = $this->request->post('pay_tip','');
        if($pay_tip==''){
            return $this->error('请填写转账说明');
        }
        $pay_file = $this->request->post('pay_file','');
        if($pay_file==''){
            return $this->error('请上传付款凭证');
        }

        $model = new OrderPayOfflineModel();
        $model->pay_sn = $order->sn . '-' . mt_rand(100000, 999999);
        $model->order_id = $order_id;
        $model->order_pay_id = $orderPay->id;
        $model->pay_tip = $pay_tip;
        $model->pay_file = $pay_file;
        $model->status  = '0';
        $model->vertify_refuse_reason = "";
        $model->vertify_file = "";

        $order->status = '1';
        $orderPay->status = '1';
        
        //判断当前用户类型
        Db::startTrans();
        try {
            $model->save();
            $order->save();
            $orderPay->save();
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        $this->success('操作成功');
    }

    /**
     * 需求方-发起付款-微信(线上扫码)
     *
     * @ApiMethod (POST)
     * @ApiSummary
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $order_id     订单ID
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0", description="返回状态:1-成功，0-失败")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功", description="返回消息")
     * @ApiReturnParams   (name="data", type="object", sample="{'total':'int','rows':{'id':'integer','name':'string','id_no':'string'}}", description="")
     */
    public function pay_wxpay(){

        $request = Request::instance(); // 获取请求实例
        $url = $request->domain(); // 获取当前地址，不包

        $id = $this->request->post('order_id');
        //获取订单
        $order = OrderModel::where('id',$id)->where('user_id',$this->auth->id)->find();
        if(!$order){
            $this->error("订单不存在或没有权限");
        }

        //获取当前节点
        $points = OrderPayModel::where('idx',$order->now_point)->where('order_id',$order->id)->find();
        if(!$points){
            $this->error("找不到节点信息");
        }
        if($order->status > 0){
            return $this->error('当前订单不是未付款状态');
        }
        if($points->total > 20000){
            //return $this->error('2万以上请使用线下付款');
        }
        $has = OrderPayOfflineModel::where('order_id',$order->id)->where('status',0)->find();
        if($has){
            return $this->error('当前已存在审核中的付款信息，请等候');
        }
        //订单号
        $out_trade_no = $order->sn . '-' . mt_rand(100000, 999999);
        $config = Service::getConfig('wechat');
        $app = Factory::payment($config);
        $result = $app->order->unify([
            'body' => $order->title,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $points->total*100,
            'notify_url' => $url."/addons/epay/api/notifyx/paytype/wechat", // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            //'notify_url' => "https://mgtoffice.qianqiance.com/addons/epay/api/notifyx/paytype/wechat",
            'trade_type' => 'NATIVE', // 请对应换成你的支付方式对应的值类型
        ]);
        if(!isset($result['code_url'])){
            $this->error('支付失败，请联系管理员');
        }
        $order->out_trade_no = $out_trade_no;
        $points->out_trade_no = $out_trade_no;
        $order->save();
        $points->save();
        $this->success('操作成功',$result['code_url']);
    }

    /**
     * 个人中心(专家)-提交验收申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $type  正常提交-0；跟进反馈提交-1
     * @param string $id   订单ID
     * @param string $desc 申请说明
     * @param string $files 附件([{url:/supply/upload/20231201/a.xlsx,name:xx.xlsx}])
     */
    public function expertFinish(){
        $type = $this->request->post('type',0);
        $id = $this->request->post('id');
        $desc = $this->request->post('desc','');
        $files = htmlspecialchars_decode($this->request->post('files',''));


        if($desc==''){
            $this->error("请填写说明");
        }
        if($files==''){
            $this->error("请上传文件");
        }
        //判断用户是否为认证专家
        if($this->auth->role_type === '1'){
            // 专家 数据隔离
            $this->error("只有专家才能申请");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证专家才能申请");
        }
        //获取订单
        $order = OrderModel::where('id',$id)->where('specialist_id',$this->auth->id)->find();
        if(!$order){
            $this->error("订单不存在或没有权限");
        }

        $requirement = RequirementModel::where('id',$order->rid)->find();
        //获取当前节点
        $points = OrderPayModel::where('idx',$order->now_point)->where('order_id',$order->id)->find();
        if(!$points){
            $this->error("找不到节点信息");
        }
        $next_idx = $order->now_point+1;
        $points_next = OrderPayModel::where('idx',$next_idx)->where('order_id',$order->id)->find();
        //节点明细大节点插入
        $orderPayDetailModel = new OrderPayDetailModel();
        //获取当前大节点
        $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
        if(!$points_detail){
            $this->error("找不到节点详细信息");
        }
        //节点明细小节点
        $orderPayLogModel = new OrderPayLogModel();
        $orderPayLogModel->order_id = $order->id;
        $orderPayLogModel->order_pay_id = $points->id;
        $orderPayLogModel->tpye = $points->is_pay;
        $orderPayLogModel->order_pay_detail_id = $points_detail->id;
        $orderPayLogModel->idx = $order->now_point;
        $tipFileArr = json_decode($files,true);
        // 遍历数组，获取每个文件的文件名
        $fileNames = array_map(function($filePath) {
            return basename($filePath['name']);
        }, $tipFileArr);
        $tipFile = join(",",$fileNames);
        $orderPayLogModel->tip_1 = $desc;
        $orderPayLogModel->tip_2 = "专家{$this->auth->nickname}上传了{$tipFile}等文件";
        $orderPayLogModel->tip_3 = "专家{$this->auth->nickname}上传了{$tipFile}等文件";
        $orderPayLogModel->msg = $desc;
        $orderPayLogModel->msg_files = $files;
        $orderPayLogModel->msg_user_id = $this->auth->id;
        $orderPayLogModel->msg_user_type = '2';
        
        //验收申请
        $accept = new OrderAcceptanceModel();
        $accept->order_id = $order->id;
        $accept->order_pay_id = $points->id;
        $accept->specialist_desc = $desc;
        $accept->specialist_files = $files;
        $accept->status = '0';
        //是否需要插入大节点
        $needInsertDetail = false;
        $detailArr = [];
        //如果是免验收。直接通过进入下一节点
        if($order->need_acceptance === '0'){
            //无需验收
            $accept->status = '1';
            //1.完成当前大节点
            $orderPayDetailModel1 = new OrderPayDetailModel();
            $status_1 = "已完成";//平台
            $status_2 = "已完成";//用户
            $status_3 = "已完成";//专家
            $tip_1 = "专家确认提交服务完成";
            $tip_2 = "专家确认提交服务完成";
            $tip_3 = "专家确认提交服务完成";
            $orderPayDetailModel1->order_id = $order->id;
            $orderPayDetailModel1->idx = $order->now_point;
            $orderPayDetailModel1->type = $points->is_pay;
            $orderPayDetailModel1->tip_1 = $tip_1;
            $orderPayDetailModel1->tip_2 = $tip_2;
            $orderPayDetailModel1->tip_3 = $tip_3;
            $orderPayDetailModel1->status_1 = $status_1;
            $orderPayDetailModel1->status_2 = $status_2;
            $orderPayDetailModel1->status_3 = $status_3;
            $detailArr[] = $orderPayDetailModel1;
            //2.
            if($order->now_point === $order->num){
                //最后一个节点
                //当前节点变更状态-已完成
                $points->is_pay = '5';
                //订单状态变更-已完成
                $order->status = '5';
                //需求者消息
                setPostMessage(2,$order->user_id,'编号'.$order->sn.'订单已完成，点击查看详情','/myOrder/detail?status='.$order->status.'&id='.$order->id);
                //专家消息
                setPostMessage(2,$order->specialist_id,'编号'.$order->sn.'订单已完成，点击查看详情','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);
                //添加大节点-已完成
                // $orderPayDetailModel = new OrderPayDetailModel();
               
                
            }else{
                //进入下一节点
                $needInsertDetail = true;
                if(!$points_next){
                    $this->error("找不到节点信息");
                }
                //当前节点变更状态-已完成
                $points->is_pay = '5';
                //下一节点变更状态-服务中-代收款
                $points_next->is_pay = '0';
                //订单状态变更-服务中-代收款
                $order->status = '0';
                //订单状态节点变更
                $order->now_point = $next_idx;
                //添加大节点-待支付
                $total_first = $points_next->total;
                // $orderPayDetailModel = new OrderPayDetailModel();
                $status_1 = "待收款";//平台
                $status_2 = "待支付";//用户
                $status_3 = "待收款";//专家
                $tip_1 = "待用户托管支付金额{$total_first}元";
                $tip_2 = "{$total_first}元";
                $tip_3 = "{$total_first}元";
                $orderPayDetailModel->order_id = $order->id;
                $orderPayDetailModel->idx = $next_idx;
                $orderPayDetailModel->type = $points_next->is_pay;
                $orderPayDetailModel->tip_1 = $tip_1;
                $orderPayDetailModel->tip_2 = $tip_2;
                $orderPayDetailModel->tip_3 = $tip_3;
                $orderPayDetailModel->status_1 = $status_1;
                $orderPayDetailModel->status_2 = $status_2;
                $orderPayDetailModel->status_3 = $status_3;
                $detailArr[] = $orderPayDetailModel;
            }

        }else{
            $accept->status = '0';
            //当前节点变更状态-待验收
            $points->is_pay = '3';
            //订单状态变更-待验收
            $order->status = '3';
            //添加大节点-待验收
            $status_1 = $type>0?"二次待验收":"待验收";//平台
            $status_2 = $type>0?"二次待验收":"待验收";//用户
            $status_3 = $type>0?"二次待验收":"待验收";//专家
            $tip_1 = $type>0?"等待用户进行二次服务验收":"等待用户进行服务验收";
            $tip_2 = $type>0?"请进行产物二次验收操作":"请进行产物验收操作";
            $tip_3 = $type>0?"请等待需求方进行二次服务确认":"请等待需求方进行服务确认";
            $orderPayDetailModel->order_id = $order->id;
            $orderPayDetailModel->idx = $order->now_point;
            $orderPayDetailModel->type = $points->is_pay;
            $orderPayDetailModel->tip_1 = $tip_1;
            $orderPayDetailModel->tip_2 = $tip_2;
            $orderPayDetailModel->tip_3 = $tip_3;
            $orderPayDetailModel->status_1 = $status_1;
            $orderPayDetailModel->status_2 = $status_2;
            $orderPayDetailModel->status_3 = $status_3;
            $detailArr[] = $orderPayDetailModel;
            setPostMessage(2,$order->user_id,'您有一条待验收的服务订单，点击查看详情','/myOrder/detail?status='.$order->status.'&id='.$order->id);
        }
        
        
        
        Db::startTrans();
        try {
            $orderPayLogModel->save();
            foreach($detailArr as &$detail){
                $detail->save();
            }
            $accept->save();
            $order->save();
            $points->save();
            if($points_next){
                $points_next->save();
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$id);
    }

    /**
     * 个人中心(需求方)-通过验收申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   订单ID
     */
    public function passFinish(){
        $id = $this->request->post('id');
        $desc = $this->request->post('desc','');

        //判断用户是否为认证专家
        if($this->auth->role_type === '2'){
            // 专家 数据隔离
            $this->error("只有需求方才能申请");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证需求方才能申请");
        }
        //获取订单
        $order = OrderModel::where('id',$id)->where('user_id',$this->auth->id)->find();
        if(!$order){
            $this->error("订单不存在或没有权限");
        }

        $requirement = RequirementModel::where('id',$order->rid)->find();
        //获取当前节点
        $points = OrderPayModel::where('idx',$order->now_point)->where('order_id',$order->id)->find();
        if(!$points){
            $this->error("找不到节点信息");
        }
        $next_idx = $order->now_point+1;
        $points_next = OrderPayModel::where('idx',$next_idx)->where('order_id',$order->id)->find();
        //节点明细大节点插入
        $orderPayDetailModel = new OrderPayDetailModel();
        //获取当前大节点
        $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
        if(!$points_detail){
            $this->error("找不到节点详细信息");
        }
        //节点明细小节点
        $orderPayLogModel = new OrderPayLogModel();
        $orderPayLogModel->order_id = $order->id;
        $orderPayLogModel->order_pay_id = $points->id;
        $orderPayLogModel->tpye = $points->is_pay;
        $orderPayLogModel->order_pay_detail_id = $points_detail->id;
        $orderPayLogModel->idx = $order->now_point;
        // $tipFileArr = explode(",",$files);
        // // 遍历数组，获取每个文件的文件名
        // $fileNames = array_map(function($filePath) {
        //     return basename($filePath);
        // }, $tipFileArr);
        // $tipFile = join(",",$fileNames);
        $orderPayLogModel->tip_1 = "用户{$this->auth->nickname}确认验收通过，节点完成";
        $orderPayLogModel->tip_2 = "用户{$this->auth->nickname}确认验收通过，节点完成";
        $orderPayLogModel->tip_3 = "用户{$this->auth->nickname}确认验收通过，节点完成";
        // $orderPayLogModel->msg = $desc;
        // $orderPayLogModel->msg_files = $files;
        $orderPayLogModel->msg_user_id = $this->auth->id;
        $orderPayLogModel->msg_user_type = '1';
        
        //找到验收申请
        $accept = OrderAcceptanceModel::where('order_id',$id)->where('status','0')->find();
        if(!$accept){
            $this->error("当前并无验收申请");
        }
        // dump($id);die;
        // $accept->user_desc = $desc;
        //是否需要插入大节点
        $needInsertDetail = false;
        $detailArr = [];
        //已完成。直接通过进入下一节点
        $accept->status = '1';
        //1.完成当前大节点
        $orderPayDetailModel1 = new OrderPayDetailModel();
        $status_1 = "已完成";//平台
        $status_2 = "已完成";//用户
        $status_3 = "已完成";//专家
        $tip_1 = "需求方已确认节点完成";
        $tip_2 = "需求方已确认节点完成";
        $tip_3 = "需求方已确认节点完成";
        $orderPayDetailModel1->order_id = $order->id;
        $orderPayDetailModel1->idx = $order->now_point;
        $orderPayDetailModel1->type = $points->is_pay;
        $orderPayDetailModel1->tip_1 = $tip_1;
        $orderPayDetailModel1->tip_2 = $tip_2;
        $orderPayDetailModel1->tip_3 = $tip_3;
        $orderPayDetailModel1->status_1 = $status_1;
        $orderPayDetailModel1->status_2 = $status_2;
        $orderPayDetailModel1->status_3 = $status_3;
        $detailArr[] = $orderPayDetailModel1;
        //2.
        if($order->now_point === $order->num){
            //最后一个节点
            //当前节点变更状态-已完成
            $points->is_pay = '5';
            //订单状态变更-已完成
            $order->status = '5';
            setPostMessage(2,$order->specialist_id,'编号'.$order->sn.'订单已完成，点击查看详情','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);
            //添加大节点-已完成
            // $orderPayDetailModel = new OrderPayDetailModel();
           
            
        }else{
            //进入下一节点
            $needInsertDetail = true;
            if(!$points_next){
                $this->error("找不到节点信息");
            }
            //当前节点变更状态-已完成
            $points->is_pay = '5';
            //下一节点变更状态-服务中-代收款
            $points_next->is_pay = '0';
            //订单状态变更-服务中-代收款
            $order->status = '0';
            //订单状态节点变更
            $order->now_point = $next_idx;
            //添加大节点-待支付
            $total_first = $points_next->total;
            // $orderPayDetailModel = new OrderPayDetailModel();
            $status_1 = "待收款";//平台
            $status_2 = "待支付";//用户
            $status_3 = "待收款";//专家
            $tip_1 = "待用户托管支付金额{$total_first}元";
            $tip_2 = "{$total_first}元";
            $tip_3 = "{$total_first}元";
            $orderPayDetailModel->order_id = $order->id;
            $orderPayDetailModel->idx = $next_idx;
            $orderPayDetailModel->type = $points_next->is_pay;
            $orderPayDetailModel->tip_1 = $tip_1;
            $orderPayDetailModel->tip_2 = $tip_2;
            $orderPayDetailModel->tip_3 = $tip_3;
            $orderPayDetailModel->status_1 = $status_1;
            $orderPayDetailModel->status_2 = $status_2;
            $orderPayDetailModel->status_3 = $status_3;
            $detailArr[] = $orderPayDetailModel;
        }
        
        
        
        Db::startTrans();
        try {
            $orderPayLogModel->save();
            foreach($detailArr as &$detail){
                $detail->save();
            }
            $accept->save();
            $order->save();
            $points->save();
            if($points_next){
                $points_next->save();
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$id);
    }

    /**
     * 个人中心(需求方)-拒绝验收申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $id   订单ID
     * @param string $desc 反馈信息
     */
    public function refuseFinish(){
        $id = $this->request->post('id');
        $desc = $this->request->post('desc','');

        if($desc==''){
            $this->error("请填写反馈信息");
        }
        //判断用户是否为认证专家
        if($this->auth->role_type === '2'){
            // 专家 数据隔离
            $this->error("只有需求方才能申请");
        }
        if($this->auth->verify_status !== '1'){
            $this->error("只有认证需求方才能申请");
        }
        //获取订单
        $order = OrderModel::where('id',$id)->where('user_id',$this->auth->id)->find();
        if(!$order){
            $this->error("订单不存在或没有权限");
        }

        $requirement = RequirementModel::where('id',$order->rid)->find();
        //获取当前节点
        $points = OrderPayModel::where('idx',$order->now_point)->where('order_id',$order->id)->find();
        if(!$points){
            $this->error("找不到节点信息");
        }
        $next_idx = $order->now_point+1;
        $points_next = OrderPayModel::where('idx',$next_idx)->where('order_id',$order->id)->find();
        //节点明细大节点插入
        $orderPayDetailModel = new OrderPayDetailModel();
        //获取当前大节点
        $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
        if(!$points_detail){
            $this->error("找不到节点详细信息");
        }
        //节点明细小节点
        $orderPayLogModel = new OrderPayLogModel();
        $orderPayLogModel->order_id = $order->id;
        $orderPayLogModel->order_pay_id = $points->id;
        $orderPayLogModel->tpye = $points->is_pay;
        $orderPayLogModel->order_pay_detail_id = $points_detail->id;
        $orderPayLogModel->idx = $order->now_point;
        // $tipFileArr = explode(",",$files);
        // // 遍历数组，获取每个文件的文件名
        // $fileNames = array_map(function($filePath) {
        //     return basename($filePath);
        // }, $tipFileArr);
        // $tipFile = join(",",$fileNames);
        $orderPayLogModel->tip_1 = "用户{$this->auth->nickname}验收申请未通过，返回专家修改";
        $orderPayLogModel->tip_2 = "用户{$this->auth->nickname}验收申请未通过，返回专家修改";
        $orderPayLogModel->tip_3 = "用户{$this->auth->nickname}验收申请未通过，返回专家修改";
        $orderPayLogModel->msg = $desc;
        // $orderPayLogModel->msg_files = $files;
        $orderPayLogModel->msg_user_id = $this->auth->id;
        $orderPayLogModel->msg_user_type = '1';
        
        //找到验收申请
        $accept = OrderAcceptanceModel::where('order_id',$id)->where('status','0')->find();
        $accept->user_desc = $desc;
        $accept->status = '2';
        //是否需要插入大节点
        $needInsertDetail = false;
        $detailArr = [];
        //待跟进
        //1.增加当前节点拒绝明细
        $orderPayDetailModel1 = new OrderPayDetailModel();
        $status_1 = "待跟进";//平台
        $status_2 = "待跟进";//用户
        $status_3 = "待跟进";//专家
        $tip_1 = "等待专家跟进反馈";
        $tip_2 = "请等待专家进行跟进";
        $tip_3 = "请跟进当前需求方反馈的信息";
        $orderPayDetailModel1->order_id = $order->id;
        $orderPayDetailModel1->idx = $order->now_point;
        $orderPayDetailModel1->type = $points->is_pay;
        $orderPayDetailModel1->tip_1 = $tip_1;
        $orderPayDetailModel1->tip_2 = $tip_2;
        $orderPayDetailModel1->tip_3 = $tip_3;
        $orderPayDetailModel1->status_1 = $status_1;
        $orderPayDetailModel1->status_2 = $status_2;
        $orderPayDetailModel1->status_3 = $status_3;
        $detailArr[] = $orderPayDetailModel1;
        //2.
        //当前节点变更状态-待跟进
        $points->is_pay = '4';
        //订单状态变更-待跟进
        $order->status = '4';
        
        Db::startTrans();
        try {

            setPostMessage(2,$order->specialist_id,'您服务的订单验收未通过，点击查看详情','/exportMyOrder/detail?status='.$order->status.'&id='.$order->id);

            $orderPayLogModel->save();
            foreach($detailArr as &$detail){
                $detail->save();
            }
            $accept->save();
            $order->save();
            $points->save();
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$id);
    }

    /**
     * 支付宝异步通知回调
     * @ApiMethod (POST)
     * {"gmt_create":"2024-01-30 16:06:42","charset":"utf-8","gmt_payment":"2024-01-30 16:06:48","notify_time":"2024-01-30 16:06:48","subject":"\u652f\u4ed8 \u6d4b\u8bd5 - 1","sign":"dzv+1bvi0Cp+Ts2MQ8iA9ey6YDI47VLKWY5TS3N+nLJohUrTqOhmq8iBdz25R2Uv61FOOFLX8zeoNSv7j6emr+em0lycV+VuIw80yJ51F6smsjUnl\/4Ae4MLmQ8HwYriLqLaF7Lbp7zhLyouZelbQIYyMkKtK6flvE3\/d\/rx\/ZXYwtc2aFcI02RrMvRb04I7h5qcC5x2+KzWgA+CI2GvFNcS46V4gpiHcVahAiT1vwqJOz9COMO2sYJFxXA5if5vd91QuJStqrNiwzQy3ohVqsf\/WnOVlbsr9v7oQfVQrZ8SkhBGkXRtL7chMOeIznhw7OXassaGOhiox0y9exOEJQ==","buyer_id":"2088402110143031","invoice_amount":"0.01","version":"1.0","notify_id":"2024013001222160648043031434726037","fund_bill_list":"[{\"amount\":\"0.01\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","out_trade_no":"1706601993","total_amount":"0.01","trade_status":"TRADE_SUCCESS","trade_no":"2024013022001443031445801650","auth_app_id":"2021004129673938","receipt_amount":"0.01","point_amount":"0.00","buyer_pay_amount":"0.01","app_id":"2021004129673938","sign_type":"RSA2","seller_id":"2088430691102471"}
     */
    public function alipay_notify(){

        // Array
        // (
        //     [gmt_create] => 2024-01-30 16:06:42
        //     [charset] => utf-8
        //     [gmt_payment] => 2024-01-30 16:06:48
        //     [notify_time] => 2024-01-30 16:06:48
        //     [subject] => 支付 测试 - 1
        //     [sign] => dzv+1bvi0Cp+Ts2MQ8iA9ey6YDI47VLKWY5TS3N+nLJohUrTqOhmq8iBdz25R2Uv61FOOFLX8zeoNSv7j6emr+em0lycV+VuIw80yJ51F6smsjUnl/4Ae4MLmQ8HwYriLqLaF7Lbp7zhLyouZelbQIYyMkKtK6flvE3/d/rx/ZXYwtc2aFcI02RrMvRb04I7h5qcC5x2+KzWgA+CI2GvFNcS46V4gpiHcVahAiT1vwqJOz9COMO2sYJFxXA5if5vd91QuJStqrNiwzQy3ohVqsf/WnOVlbsr9v7oQfVQrZ8SkhBGkXRtL7chMOeIznhw7OXassaGOhiox0y9exOEJQ==
        //     [buyer_id] => 2088402110143031
        //     [invoice_amount] => 0.01
        //     [version] => 1.0
        //     [notify_id] => 2024013001222160648043031434726037
        //     [fund_bill_list] => [{"amount":"0.01","fundChannel":"ALIPAYACCOUNT"}]
        //     [notify_type] => trade_status_sync
        //     [out_trade_no] => 1706601993
        //     [total_amount] => 0.01
        //     [trade_status] => TRADE_SUCCESS
        //     [trade_no] => 2024013022001443031445801650
        //     [auth_app_id] => 2021004129673938
        //     [receipt_amount] => 0.01
        //     [point_amount] => 0.00
        //     [buyer_pay_amount] => 0.01
        //     [app_id] => 2021004129673938
        //     [sign_type] => RSA2
        //     [seller_id] => 2088430691102471
        // )
        $paytype = $this->request->param('paytype');
        $config = Service::getConfig('alipay');
        // $pay = Service::checkNotify($paytype);
        $pay = Pay::alipay($config);
        $payModel = new PayModel();
        
        $data = $_POST;
        $json = json_encode($data);
        $payModel->tip = "支付宝：".$paytype;
        $payModel->content = $json;
        $payModel->save();
        
        // if (!$pay) {
            // return json(['code' => 'FAIL', 'message' => '失败'], 500, ['Content-Type' => 'application/json']);
        // }

        // 获取回调数据，V3和V2的回调接收不同
        // $data = Service::isVersionV3() ? $pay->callback() : $pay->verify();

        // $json = json_encode($data);
        // $pay->tip = "支付宝";
        // $pay->content = $json;
        // $pay->save();

        try {
            
            $out_trade_no = $data['out_trade_no'];
            $total_amount = $data['total_amount'];

            $order = OrderModel::where('out_trade_no',$out_trade_no)->find();
            if($order){
                // return $this->error('订单不存在');
                //找到第一个未付款的节点
                $orderPay = OrderPayModel::where('order_id',$order->id)->where('out_trade_no',$out_trade_no)->where('is_pay','0')->find();
                if($orderPay){
                    //判断金额一致
                    $orderPay->total_amount = $total_amount;
                    $orderPay->is_pay = '1';
                    $orderPay->pay_time = time();
                    $order->is_pay = '1';
                    $order->save();
                    $orderPay->save();
                }
            }
            
            //你可以在此编写订单逻辑
        } catch (Exception $e) {
            \think\Log::record("回调逻辑处理错误:" . $e->getMessage(), "error");
        }

        return $pay->success()->send();
    }

    /**
     * 支付宝异步调试
     * 
     * @param string $id   订单ID
     */

    public function alipay_notify_test(){
        $id = $this->request->param('id');
        $pay = PayModel::where('id',$id)->find();
        $content = $pay->content;
        $data = json_decode($content,true);

        $out_trade_no = $data['out_trade_no'];
        // $total_amount = $data['total_amount'];

        $order = OrderModel::where('out_trade_no',$out_trade_no)->find();
        // if(!$order){
        //     return $this->error('订单不存在');
        // }
        //找到第一个未付款的节点
        // $orderPay = OrderPayModel::where('order_id',$order->id)->where('out_trade_no',$out_trade_no)->where('is_pay','0')->find();

        print_r([
            'data'=>$data,
            'order'=>$order,
        ]);
    }

    /**
     * 订单评价
     * @ApiMethod (POST)
     * @ApiSummary  (针对已完成订单发起评价)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $order_id     订单ID
     * @param string $points     评分：1-5
     * @param string $desc     评价内容
     * @param string $files     评价图片-多个,隔开
     */
    public function createComment(){
        $order_id = $this->request->param('order_id','');
        $points = $this->request->param('points',0);
        $desc = $this->request->param('desc','');
        $files = htmlspecialchars_decode($this->request->post('files',''));
        $order = OrderModel::where('id',$order_id)->find();
        if(!$order){
            return $this->error('订单不存在');
        }
        if($order->status !== '5'){
            // return $this->error('未完成的订单无法评价');
        }
        if(($order->user_id !== $this->auth->id) && ($order->specialist_id !== $this->auth->id)){
            return $this->error('您无法评价该订单');
        }
        $comment = new OrderCommentModel();
        $comment->order_id = $order_id;
        $comment->points = $points;
        $comment->desc = $desc;
        $comment->files = $files;
        $comment->type = $this->auth->role_type;
        if($this->auth->role_type == '1'){
            //需求方faqi
            $comment->to_user_id = $order->specialist_id;
        }else{
            //专家
            $comment->to_user_id = $order->user_id;
        }
        $comment->user_id = $this->auth->id;

        //修改专家平均评分
        

        Db::startTrans();
        try {
            $comment->save();
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error("操作失败",$e->getMessage());
        }
        
        $this->success("操作成功",$comment);
    }

    /**
     * 获取订单评价
     * @ApiMethod (POST)
     * @ApiSummary  (获取订单评价)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $order_id     订单ID
      * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1703175528",
        "data": {
            "id": 10,
            "order_id": 5,
            "user_id": 18,//评价发起人
            "to_user_id": 16,//被评价人
            "points": 1,//分数
            "desc": "2",//描述
            "files": "3",//文件
            "type": "1",//1-需求方对专家的评价,2-专家对需求方的评价
            "createtime": 1706758025,
            "updatetime": 1706758025,
            "deletetime": null,
            "createtime_text": "2024-02-01 11:27:05",
            "updatetime_text": "2024-02-01 11:27:05",
            "type_text": "需求方发起的评价"

        }
        })
     */
    public function comment(){
        $order_id = $this->request->param('order_id','');
        $order = OrderModel::where('id',$order_id)->find();
        if(!$order){
            return $this->error('订单不存在');
        }
        $data = OrderCommentModel::where('order_id',$order_id)->order('id','desc')->select();

        $this->success("操作成功",$data);
    }

    /**
     * 订单开票申请
     * @ApiMethod (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @param string $invoice_type 发票种类:1=普通发票,2=专用发票
     * @param string $order_id 订单ID
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
        $invoice = new InvoiceLogModel();
        $order_id = $this->request->post('order_id','');
        $order = OrderModel::where('id',$order_id)->find();
        if(!$order){
            return $this->error('订单不存在');
        }
        //判断是否已有申请
        $has = InvoiceLogModel::where('order_id',$order_id)->whereIn('status',[0,1])->find();
        if($has){
            return $this->error('该订单已有申请过发票');
        }
        $amount = $order->total ;
        
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
        $invoice->status = 0;
        $invoice->amount = $amount;
        $invoice->order_id = $order_id;
        $invoice->sn = $order->sn;
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
        $rate_system = ConfigModel::where('name','rate_system')->value('value');
//        $invoice->rate = $amount * $rate_system;
        $invoice->rate = 0;
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
}
