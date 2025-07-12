<?php

namespace addons\epay\controller;

use addons\epay\library\Service;
use addons\epay\library\Wechat;
use addons\third\model\Third;
use app\common\library\Auth;
use Exception;
use think\addons\Controller;
use think\Response;
use think\Session;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Pay;
use app\common\model\Pay as PayModel;
use app\common\model\Order as OrderModel;
use app\common\model\OrderPay as OrderPayModel;
use app\common\model\OrderPayLog as OrderPayLogModel;
use app\common\model\OrderPayDetail as OrderPayDetailModel;
use app\common\model\UserArchive;
use app\common\model\User as UserModel;

/**
 * API接口控制器
 *
 * @package addons\epay\controller
 */
class Api extends Controller
{

    protected $layout = 'default';
    protected $config = [];

    /**
     * 默认方法
     */
    public function index()
    {
        return;
    }

    /**
     * 外部提交
     */
    public function submit()
    {
        $this->request->filter('trim');
        $out_trade_no = $this->request->request("out_trade_no");
        $title = $this->request->request("title");
        $amount = $this->request->request('amount');
        $type = $this->request->request('type', $this->request->request('paytype'));
        $method = $this->request->request('method', 'web');
        $openid = $this->request->request('openid', '');
        $auth_code = $this->request->request('auth_code', '');
        $notifyurl = $this->request->request('notifyurl', '');
        $returnurl = $this->request->request('returnurl', '');

        if (!$amount || $amount < 0) {
            $this->error("支付金额必须大于0");
        }

        if (!$type || !in_array($type, ['alipay', 'wechat'])) {
            $this->error("支付类型错误");
        }

        $params = [
            'type'         => $type,
            'out_trade_no' => $out_trade_no,
            'title'        => $title,
            'amount'       => $amount,
            'method'       => $method,
            'openid'       => $openid,
            'auth_code'    => $auth_code,
            'notifyurl'    => $notifyurl,
            'returnurl'    => $returnurl,
        ];
        return Service::submitOrder($params);
    }

    /**
     * 微信支付(公众号支付&PC扫码支付)
     */
    public function wechat()
    {
        $config = Service::getConfig('wechat');

        $isWechat = stripos($this->request->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
        $isMobile = $this->request->isMobile();
        $this->view->assign("isWechat", $isWechat);
        $this->view->assign("isMobile", $isMobile);

        //发起PC支付(Scan支付)(PC扫码模式)
        if ($this->request->isAjax()) {
            $pay = Pay::wechat($config);
            $orderid = $this->request->post("orderid");
            try {
                $result = Service::isVersionV3() ? $pay->find(['out_trade_no' => $orderid]) : $pay->find($orderid, 'scan');
                $this->success("", "", ['status' => $result['trade_state'] ?? 'NOTPAY']);
            } catch (GatewayException $e) {
                $this->error("查询失败(1001)");
            }
        }

        $orderData = Session::get("wechatorderdata");
        if (!$orderData) {
            $this->error("请求参数错误");
        }
        if ($isWechat && $isMobile) {
            //发起公众号(jsapi支付),openid必须

            //如果没有openid，则自动去获取openid
            if (!isset($orderData['openid']) || !$orderData['openid']) {
                $orderData['openid'] = Service::getOpenid();
            }

            $orderData['method'] = 'mp';
            $type = 'jsapi';
            $payData = Service::submitOrder($orderData);
            if (!isset($payData['paySign'])) {
                $this->error("创建订单失败，请返回重试", "");
            }
        } else {
            $orderData['method'] = 'scan';
            $type = 'pc';
            $payData = Service::submitOrder($orderData);
            if (!isset($payData['code_url'])) {
                $this->error("创建订单失败，请返回重试", "");
            }
        }
        $this->view->assign("orderData", $orderData);
        $this->view->assign("payData", $payData);
        $this->view->assign("type", $type);

        $this->view->assign("title", "微信支付");
        return $this->view->fetch();
    }

    /**
     * 支付宝支付(PC扫码支付)
     */
    public function alipay()
    {
        $config = Service::getConfig('alipay');

        $isWechat = stripos($this->request->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
        $isMobile = $this->request->isMobile();
        $this->view->assign("isWechat", $isWechat);
        $this->view->assign("isMobile", $isMobile);

        if ($this->request->isAjax()) {
            $orderid = $this->request->post("orderid");
            $pay = Pay::alipay($config);
            try {
                $result = $pay->find(['out_biz_no' => $orderid]);
                if ($result['code'] == '10000' && $result['trade_status'] == 'TRADE_SUCCESS') {
                    $this->success("", "", ['status' => $result['trade_status']]);
                } else {
                    $this->error("查询失败");
                }
            } catch (GatewayException $e) {
                $this->error("查询失败(1001)");
            }
        }

        //发起PC支付(Scan支付)(PC扫码模式)
        $orderData = Session::get("alipayorderdata");
        if (!$orderData) {
            $this->error("请求参数错误");
            
        }
        $config['notify_url'] = "https://mgtoffice.qianqiance.com/addons/epay/api/notifyx/paytype/alipay";
        $config['return_url'] = "https://www.qianqiance.com/personCenter/myOrder";
        // dump($config);die;
        
        $web = Pay::alipay($config)->web([
            'out_trade_no' => '2024011216191248481014-668801',
            'total_amount' => '0.01',
            'subject' => '支付 测试 - 5'
        ]);
        return $web;
        $content = $web->getContent();
        dump($web->getContent());die;
        return (string) $web->getBody();
        dump();die;
        return Pay::alipay($config)->web([
            'out_trade_no' => ''.time(),
            'total_amount' => '0.01',
            'subject' => 'yansongda 测试 - 1',
        ]);
        

        // dump($orderData);die;
        $orderData['method'] = 'scan';
        $payData = Service::submitOrder($orderData);
        if (!isset($payData['qr_code'])) {
            $this->error("创建订单失败，请返回重试");
        }

        $type = 'pc';
        $this->view->assign("orderData", $orderData);
        $this->view->assign("payData", $payData);
        $this->view->assign("type", $type);
        $this->view->assign("title", "支付宝支付");
        return $this->view->fetch();
    }

    /**
     * 支付成功回调-支付成功后更新订单为服务中
     */
    public function notifyx()
    {
        $paytype = $this->request->param('paytype','alipay');
        $pay = Service::checkNotify($paytype);
        $PayModel = new PayModel();
        $form = [
            'payType' => $paytype,
            'pay' => $pay,
            'notifyData' => $this->request->param(),
            'notifyTime' => date('Y-m-d H:i:s'),
            'notifyStatus' => 1,
            'isVersionV3'=>Service::isVersionV3()?"v3":'v2',
            // 'data' => $pay->verify()
        ];
        $PayModel->tip = "待验签";
        $PayModel->content = json_encode($form);
        $PayModel->save();
        if (!$pay) {
            return json(['code' => 'FAIL', 'message' => '失败'], 500, ['Content-Type' => 'application/json']);
        }
        
        

        // 获取回调数据，V3和V2的回调接收不同
        $data = Service::isVersionV3() ? $pay->callback() : $pay->verify();
        $PayModel->content = json_encode($data);
        $PayModel->tip = "验签通过";
        $PayModel->save();
        try {
            //微信支付V3返回和V2不同
            if (Service::isVersionV3() && $paytype === 'wechat') {
                $data = $data['resource']['ciphertext'];
                $data['total_fee'] = $data['amount']['total'];
            }

            \think\Log::record($data);
            //获取支付金额、订单号
            $payamount = $paytype == 'alipay' ? $data['total_amount'] : $data['total_fee'] / 100;
            $out_trade_no = $data['out_trade_no'];

            \think\Log::record("回调成功，订单号：{$out_trade_no}，金额：{$payamount}");
            //你可以在此编写订单逻辑
            $total_amount = $data['total_amount'];

            $order = OrderModel::where('out_trade_no',$out_trade_no)->find();
            if($order){
                // return $this->error('订单不存在');
                //找到第一个未付款的节点
                $orderPay = OrderPayModel::where('order_id',$order->id)->where('out_trade_no',$out_trade_no)->where('is_pay','0')->find();
                if($orderPay){
                    //判断金额一致
                    $orderPay->total_amount = $total_amount;
                    $orderPay->is_pay = '2';
                    $orderPay->pay_time = time();
                    $orderPay->pay_type = $paytype === 'wechat'?1:2;
                    $order->status = '2';
                    $order->save();
                    $orderPay->save();

                    try {
                        //插入节点信息
                        //添加付款节点信息
                        //获取当前大节点
                        $points_detail = OrderPayDetailModel::where('idx',$order->now_point)->where('order_id',$order->id)->order('id','desc')->find();
                        if(!$points_detail){
                            throw new Exception('找不到节点详细信息');
                        }
                        $user = UserModel::where('id',$order->user_id)->find();
                        //节点明细小节点
                        $orderPayLogModel = new OrderPayLogModel();
                        $orderPayLogModel->order_id = $order->id;
                        $orderPayLogModel->order_pay_id = $orderPay->id;
                        $orderPayLogModel->tpye = '0';
                        $orderPayLogModel->order_pay_detail_id = $points_detail->id;
                        $orderPayLogModel->idx = $order->now_point;
                        $orderPayLogModel->tip_1 = date('Y-m-d H:i')."用户通过{$paytype}转账支付托管金额{$total_amount}元，订单从第二天开始计算。";
                        $orderPayLogModel->tip_2 = "通过{$paytype}转账支付托管金额{$total_amount}元";
                        $orderPayLogModel->tip_3 = "用户{$user->nickname}通过{$paytype}转账支付托管金额{$total_amount}元，订单从第二天开始计算。";
                        $orderPayLogModel->save();

                        //插入下一个大节点-服务中
                        $orderPayDetailModel = new OrderPayDetailModel();
                        // $orderPayDetailModel = new OrderPayDetailModel();
                        $status_1 = "服务中";//平台
                        $status_2 = "服务中";//用户
                        $status_3 = "服务中";//专家
                        $tip_1 = "等待专家服务反馈及相关文件";
                        $tip_2 = "请等待专家完成服务说明";
                        $tip_3 = "专家可提交验收申请至需方";
                        $orderPayDetailModel->order_id = $order->id;
                        $orderPayDetailModel->idx = $order->now_point;
                        $orderPayDetailModel->type = $orderPay->is_pay;
                        $orderPayDetailModel->tip_1 = $tip_1;
                        $orderPayDetailModel->tip_2 = $tip_2;
                        $orderPayDetailModel->tip_3 = $tip_3;
                        $orderPayDetailModel->status_1 = $status_1;
                        $orderPayDetailModel->status_2 = $status_2;
                        $orderPayDetailModel->status_3 = $status_3;
                        $orderPayDetailModel->save();
                    }catch (Exception $e) {
                        \think\Log::record("回调逻辑处理错误:" . $e->getMessage(), "error");
                        $PayModel->tip = $e->getMessage();
                        $PayModel->save();
                    }
                }else{
                    throw new Exception('订单付款节点不存在');
                }
            }else{
                throw new Exception('订单不存在');
            }
        } catch (Exception $e) {
            \think\Log::record("回调逻辑处理错误:" . $e->getMessage(), "error");
            $PayModel->tip = $e->getMessage();
            $PayModel->save();
        }

        //下面这句必须要执行,且在此之前不能有任何输出
        if (Service::isVersionV3()) {
            return $pay->success()->getBody()->getContents();
        } else {
            return $pay->success()->send();
        }
    }

    /**
     * 支付成功返回
     */
    public function returnx()
    {
        $paytype = $this->request->param('paytype','alipay');
        // dump($paytype);die;
        if (Service::checkReturn($paytype)) {
            echo '签名错误';
            return;
        }

        //你可以在这里定义你的提示信息,但切记不可在此编写逻辑
        $this->success("恭喜你！支付成功!", addon_url("epay/index/index"));
    }

}
