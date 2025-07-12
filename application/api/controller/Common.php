<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\model\Area;
use app\common\model\RequirementSpecialist;
use app\common\model\Version;
use app\common\model\Requirement;
use fast\Random;
use think\captcha\Captcha;
use think\Config;
use think\Hook;
use app\common\model\Tag;
use app\common\model\Level;
use app\admin\model\Notice;
use app\admin\model\Message;
use \app\common\model\Order;
use app\common\model\UserArchive;
use app\admin\service\SpecialistService;
use think\Db;
use app\common\model\Config as ConfigModel;
use Endroid\QrCode\QrCode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = ['init', 'captcha', 'tag_list', 'level_list','contact','sign_tip','config','notice_send','get_qrcode','importData','imp_data'];
    protected $noNeedRight = '*';

    public function _initialize()
    {

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Expose-Headers: __token__');//跨域让客户端获取到
        }
        //跨域检测
        check_cors_request();

        if (!isset($_COOKIE['PHPSESSID'])) {
            Config::set('session.id', $this->request->server("HTTP_SID"));
        }
        parent::_initialize();
    }

    public function imp_data(){
        set_time_limit(0);
        $title = [];//excel工作表标题
        $info = [];//excel内容
        $fileName = "v20240710.xlsx";
        $spreadsheet = IOFactory::load($fileName);
//$worksheet = $spreadsheet->getActiveSheet();   //获取当前文件内容
        $sheetAllCount = $spreadsheet->getSheetCount(); // 工作表总数
        for ($index = 0; $index < $sheetAllCount; $index++) {   //工作表标题
            $title[] = $spreadsheet->getSheet($index)->getTitle();
        }
//读取第一個工作表
        $whatTable = 1;
        $type = 1;
        $num = input('num')?input('num'):0;
        $sheet = $spreadsheet->getSheet($whatTable);
        $highest_row = $sheet->getHighestRow(); // 取得总行数
        $highest_column = $sheet->getHighestColumn(); ///取得列数  字母abc...
        $highestColumnIndex = Coordinate::columnIndexFromString($highest_column);  //转化为数字;


        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            for ($j = 1; $j <= $highest_row; $j++) {
                $conent = $sheet->getCellByColumnAndRow($i, $j)->getCalculatedValue();
                $info[$j][$i] = $conent;
            }
        }
        unset($info[1]);
        $info = array_chunk($info,50);
        Db::startTrans();
        try {
            foreach($info[$num] as $k=>$v){
                //一级
                $name = $v[1];
                $pid = Tag::where('name',$name)->where('level',1)->where('type',$type)->value('id');
                if(!$pid){
                    $tag = new Tag();
                    $tag->type = $type;
                    $tag->level = 1;
                    $tag->name = $name;
                    $tag->path = '-';
                    $tag->save();
                    $pid = $tag->id;
                }

                //二级
                $name = $v[2];
                $ppid = Tag::where('name',$name)->where('level',2)->where('pid',$pid)->where('type',$type)->value('id');
                if(!$ppid){
                    $tag = new Tag();
                    $tag->type = $type;
                    $tag->level = 2;
                    $tag->name = $name;
                    $tag->path = '-'.$pid.'-';
                    $tag->pid = $pid;
                    $tag->save();
                    $ppid = $tag->id;
                }
                //三级
                if(count($v) == 3 && !Tag::where('name',$name)->where('level',3)->where('pid',$ppid)->where('type',$type)->count()){
                    $name = $v[3];
                    if($name){
                        $is = Tag::where('name',$name)->where('level',2)->where('pid',$ppid)->where('type',$type)->count();
                        if(!$is){
                            $tag = new Tag();
                            $tag->type = $type;
                            $tag->level = 3;
                            $tag->name = $name;
                            $tag->path = '-'.$pid.'-'.$ppid.'-';
                            $tag->pid = $ppid;
                            $tag->save();
                        }
                    }
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
        }
        sleep(1);
        $num ++;
        // 跳转到其他页面
        header('Location: http://127.0.0.1/api/common/imp_data?num='.$num);
        // 停止脚本执行
        exit;

    }

    public function get_qrcode(){
        //https://mgtoffice.qianqiance.com
        $url = urlencode('https://mgtoffice.qianqiance.com/');

        $qrCode = new QrCode('https://open.weixin.qq.com/connect/qrconnect?appid=wxa7e1223c29a5f66b&redirect_uri='.$url.'&response_type=code&scope=snsapi_login&state=22q33#wechat_redirect');

// 内容区域宽高,默认为300
        $qrCode->setSize(300);
// 外边距大小,默认为10
        $qrCode->setMargin(10);
// 设置编码
        $qrCode->setEncoding('UTF-8');
// 设置二维码颜色,默认为黑色
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
// 设置二维码背景色,默认为白色
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);

##### 设置二维码下方的文字 #####

        //$qrCode->setLabel('个人技术博客网站', 11, null, LabelAlignment::CENTER());

##### 二维码中的logo #####

        //$qrCode->setLogoPath('logo.jpg');
        //$qrCode->setLogoSize(100, 90);
// $qrCode->setLogoWidth(100);
// $qrCode->setLogoHeight(90);
##### 二维码中的logo / #####

// 启用内置的验证读取器(默认情况下禁用)
        $qrCode->setValidateResult(false);

########## 二维码三种显示方式 ##########

        $dataUri = $qrCode->writeDataUri();
        echo '<img src="' . $dataUri . '">';
        //return $dataUri;
    }

    /**
     * Author: Administrator
     * Date: 2024/5/31 0031
     * Time: 14:24
     * 系统公告定时发送 && 订单过了确认失效自动取消
     */
    public function notice_send(){
        $time = time();
        //订单
        $order = Order::field('id,finishtime,specialist_id,rid')->where('confirm','0')->where('status','0')->select();
        foreach($order as $v){
            if($v->finishtime < $time){
                //需求-》匹配中
                Requirement::where('id',$v->rid)->update(['status'=>2]);
                //匹配中的专家信息改为取消
                RequirementSpecialist::where('requirement_id',$v->rid)->where('user_id',$v->specialist_id)->update(['status'=>5,'reason'=>'确认失效已过自动取消']);
                //其他参与需求的失效用户改为待匹配
                RequirementSpecialist::where('requirement_id',$v->rid)
                    ->where("status",6)
                    ->where('user_id',"<>",$v->specialist_id)
                    ->update(['status' => '0']);
                //订单取消
                Order::where('id',$v->id)->update(['status'=>6]);
                echo '订单-success:'.$v->id.'<br/>';
            }
        }


        /**系统公告 * **/
        $data = Notice::where('is_switch',1)->where('publish_time',['>=',$time-60],['<=',$time+60])->column('id');
        $userList = Db::name("user")->select();
        foreach ($data as $v){
            foreach($userList as $key=>$val){
                if(!Message::where('url',$v)->where('user_id',$val['id'])->count()){
                    setPostMessage(4,$val['id'],'您有一条新的系统公告信息，请点击查看',$v);
                    echo '公告-success:'.$val['id'].'<br/>';
                }

            }
        }
        die();

    }

    /**
     * 系统配置
     * @param string $name 配置名
     *
     */
    public function config(){
        $name = $this->request->request('name');
        $data = Db::name("config")->where(['name' => $name])->field("name,value")->find();
        $this->success('', $data);
    }

    /**
     * 加载初始化
     *
     * @param string $version 版本号
     * @param string $lng 经度
     * @param string $lat 纬度
     */
    public function init()
    {
        if ($version = $this->request->request('version')) {
            $lng = $this->request->request('lng');
            $lat = $this->request->request('lat');

            //配置信息
            $upload = Config::get('upload');
            //如果非服务端中转模式需要修改为中转
            if ($upload['storage'] != 'local' && isset($upload['uploadmode']) && $upload['uploadmode'] != 'server') {
                //临时修改上传模式为服务端中转
                set_addon_config($upload['storage'], ["uploadmode" => "server"], false);

                $upload = \app\common\model\Config::upload();
                // 上传信息配置后
                Hook::listen("upload_config_init", $upload);

                $upload = Config::set('upload', array_merge(Config::get('upload'), $upload));
            }

            $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
            $upload['uploadurl'] = preg_match("/^((?:[a-z]+:)?\/\/)(.*)/i", $upload['uploadurl']) ? $upload['uploadurl'] : url($upload['storage'] == 'local' ? '/api/common/upload' : $upload['uploadurl'], '', false, true);

            $content = [
                'citydata'    => Area::getCityFromLngLat($lng, $lat),
                'versiondata' => Version::check($version),
                'uploaddata'  => $upload,
                'coverdata'   => Config::get("cover"),
            ];
            $this->success('', $content);
        } else {
            $this->error(__('Invalid parameters'));
        }
    }

    /**
     * 上传文件
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');
        //必须设定cdnurl为空,否则cdnurl函数计算错误
        Config::set('upload.cdnurl', '');
        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!Config::get('upload.chunking')) {
                $this->error(__('Chunk file disabled'));
            }
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filename = $this->request->post("filename");
            $method = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload = new Upload();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
            } elseif ($method == 'clean') {
                //删除冗余的分片文件
                try {
                    $upload = new Upload();
                    $upload->clean($chunkid);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            } else {
                //上传分片文件
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');
            try {
                $upload = new Upload($file);
                $attachment = $upload->upload();
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
        }

    }

    /**
     * 验证码
     * @param $id
     * @return \think\Response
     */
    public function captcha($id = "")
    {
        \think\Config::set([
            'captcha' => array_merge(config('captcha'), [
                'fontSize' => 44,
                'imageH'   => 150,
                'imageW'   => 350,
            ])
        ]);
        $captcha = new Captcha((array)Config::get('captcha'));
        return $captcha->entry($id);
    }

    /**
     * 标签列表（行业标签|技能标签|地区标签）
     *
     * @ApiMethod (POST)
     * @param string $type 标签类型：1-行业，2-技能，3-地区
     */
    public function tag_list()
    {
        $type = $this->request->post('type');
        $list = Db::name("tag")->where(['type' => $type])->select();
        $this->success('', $list);
    }

    public function getSubTags($pid, $level, $type, $parentArray) {
        $tags = Db::name('tag')
            ->where('pid', $pid)
            ->where('level', $level)
            ->where('type', $type)
            ->field("id,pid,level,type,name")
            ->select();

        $result = [];
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $data[] = $this->getSubTags($tag['id'], $level + 1, $type, $tag); // 传递当前标签作为上级标签信息
//                $tag['parent'] = $parentArray;
                array_unshift($data, $parentArray); // 将 parent 放在 child 的最前面
                $result[] = $data;
            }
        }

        return $result;
    }


    /**
     * 标签列表（行业标签|技能标签|地区标签）
     *
     * @ApiMethod (POST)
     * @param string $keyword 关键词
     */
    public function tag_info()
    {
        //设置过滤方法
        $keyword = $this->request->post('keyword');

        $where = [];
        $model = new Tag();
        $list = $model
            // ->with('group')
            ->where('name','like','%'.$keyword.'%')
            ->order('sort', 'desc')
            ->find();

        $this->success('', $list);
    }

    /**
     * 专家评审等级列表
     *
     * @ApiMethod (POST)
     */
    public function level_list()
    {
        $model = new Level();
        $list = $model
            ->order('id', 'desc')
            ->select();
        
        $result = array("total" => count($list), "rows" => $list);

        $this->success('', $result);
    }

    /**
     * 读取省市区数据,联动列表
     * 
     * @ApiSummary  (1.不传参数-默认读取省；2.只传省ID-读取市；3.传省ID+市ID-读取区/街道)
     * @param string $province 省ID
     * @param string $city 市ID
     * @ApiMethod (POST)
     */
    public function area()
    {
        // $params = $this->request->get("row/a");
        $params = [
            'province' => $this->request->post('province')?$this->request->post('province'):null,
            'city' => $this->request->post('city')?$this->request->post('city'):null
        ];
        if (!empty($params)) {
            $province = isset($params['province']) ? $params['province'] : null;
            $city = isset($params['city']) ? $params['city'] : null;
        } else {
            $province = $this->request->get('province');
            $city = $this->request->get('city');
        }
        $where = ['pid' => 0, 'level' => 1];
        $provincelist = null;
        if ($province !== null) {
            $where['pid'] = $province;
            $where['level'] = 2;
            if ($city !== null) {
                $where['pid'] = $city;
                $where['level'] = 3;
            }
        }
        $provincelist = Db::name('area')->where($where)->field('id as value,name')->select();
        $this->success('', $provincelist);
    }

    /**
     * 联系我们
     * @ApiMethod (POST)
     * @ApiSummary  (联系我们)
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1708307761",
        "data": {
            "contact_mobile": "13888888888",//联系方式
            "contact_email": "123@qq.com",//联系邮箱
            "contact_kefu": "13888888888",//客服热线
            "contact_addr": "xxxxxxxx联系地址1121"//联系地址
        },
        "domain": "http://supply.test"
        })
     */
    public function contact()
    {
        $contact_mobile = ConfigModel::where('name','contact_mobile')->value('value');
        $contact_email = ConfigModel::where('name','contact_email')->value('value');
        $contact_kefu = ConfigModel::where('name','contact_kefu')->value('value');
        $contact_addr = ConfigModel::where('name','contact_addr')->value('value');

        $result = [
            'contact_mobile'=>$contact_mobile,
            'contact_email'=>$contact_email,
            'contact_kefu'=>$contact_kefu,
            'contact_addr'=>$contact_addr
        ];
        $this->success('', $result);
    }

    /**
     * 首页注册提示
     * @ApiMethod (POST)
     * @ApiSummary  (首页注册提示)
     * @ApiReturn   ({
        "code": 1,
        "msg": "",
        "time": "1708307761",
        "data": {
            "sign_tip": "xxxx",//注册提示
        },
        "domain": "http://supply.test"
        })
     */
    public function sign_tip()
    {
        $sign_tip = ConfigModel::where('name','sign_tip')->value('value');

        $result = [
            'sign_tip'=>$sign_tip
        ];
        $this->success('', $result);
    }
}
