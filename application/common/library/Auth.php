<?php

namespace app\common\library;

use app\common\model\Area;
use app\common\model\Order;
use app\common\model\UserLoginLog;
use app\common\model\OrderComment;
use app\common\model\User;
use app\common\model\UserRule;
use app\common\model\UserArchive;
use app\common\model\Specialist as SpecialistModel;
use fast\Random;
use think\Config;
use think\Db;
use think\Exception;
use think\Hook;
use think\Request;
use think\Validate;

class Auth
{
    protected static $instance = null;
    protected $_error = '';
    protected $_logined = false;
    protected $_user = null;
    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;
    protected $requestUri = '';
    protected $rules = [];
    //默认配置
    protected $config = [];
    protected $options = [];
    protected $allowFields = ['id', 'username', 'nickname', 'mobile', 'avatar', 'score', 'role_type', 'typedata', 'verify_status', 'id_no', 'id_no_name','id_no_front_image', 'id_no_backend_image','id_no_bank_name','id_no_bank_id','company_name','company_id_no','company_id_no_image','company_attachfile','company_bank_name','company_bank_id','province_id','city_id','district_id','address','enterprise_status','status'];
    protected $allowFields2 = ['id','user_id','mobile','nickname','avatar','role_type', 'typedata', 'verify_status', 'id_no', 'id_no_name','id_no_front_image', 'id_no_backend_image','id_no_bank_name','id_no_bank_id', 'company_name','company_id_no','company_id_no_image','company_attachfile','company_bank_name','company_bank_id','province_id','city_id','district_id','address'];
    protected $allowFields3 = ['*'];
    

    public function __construct($options = [])
    {

        if ($config = Config::get('user')) {
            $this->config = array_merge($this->config, $config);
        }
        $this->options = array_merge($this->config, $options);
    }

    /**
     *
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 获取User模型
     * @return User
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * 兼容调用user模型的属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_user ? $this->_user->$name : null;
    }

    /**
     * 兼容调用user模型的属性
     */
    public function __isset($name)
    {
        return isset($this->_user) ? isset($this->_user->$name) : false;
    }

    /**
     * 根据Token初始化
     *
     * @param string $token Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined) {
            return true;
        }
        if ($this->_error) {
            return false;
        }
        $data = Token::get($token);
        if (!$data) {
            $this->setError(__('Please login first'));
            return false;
        }
        $user_id = intval($data['user_id']);
        if ($user_id > 0) {
            $user = User::get($user_id);
            if (!$user) {
                $this->setError('Account not exist');
                return false;
            }
            if ($user['status'] != 'normal') {
                $this->setError('账号已被封禁');
                return false;
            }
            //如果是专家，可能禁用
            if($user['role_type']  == 2 && SpecialistModel::where('user_id',$user_id)->value('status') == 2){
                $this->setError('该专家已被禁用！');
                return false;
            }
            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;

            //初始化成功的事件
            Hook::listen("user_init_successed", $this->_user);

            return true;
        } else {
            $this->setError('You are not logged in');
            return false;
        }
    }

    /**
     * 注册用户
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param array  $extend   扩展参数
     * @return boolean
     */
    public function register($username, $password, $email = '', $mobile = '', $extend = [], $role_type = '', $typedata = '')
    {
        
        // 检测用户名、昵称、邮箱、手机号是否存在
        if (User::getByUsername($username)) {
            $this->setError('Username already exist');
            return false;
        }
        if (User::getByNickname($username)) {
            $this->setError('Nickname already exist');
            return false;
        }
        if ($mobile && User::getByMobile($mobile)) {
            $this->setError('Mobile already exist');
            return false;
        }
        if ($role_type == ''){
            $this->setError('角色类型不能为空');
            return false;
        }
        if ($typedata == ''){
            $this->setError('身份类型不能为空');
            return false;
        }

        $ip = request()->ip();
        $time = time();

        $data = [
            'username' => $username,
            'password' => $password,
            'email'    => $email,
            'mobile'   => $mobile,
            'level'    => 1,
            'score'    => 0,
            'avatar'   => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAKPAowDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+/iiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiq11dQ2cTz3DpFDEjyyyyyJDDFDHgySyzzMkMaoCCTJIgxk5wDj4x+Kv8AwUi/YA+Bwuk+Ln7aP7L/AIAvrJ3juNG8QfHL4cQ+II5oxmSE+HrXxFda28qgj91FYSSknAjJ4JZvZXA+16K/C7xl/wAHJf8AwRj8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wd/0ZJ+yH/4jZ8Gv/mKr6+oou+7/AK/4ZfcB8g/8O+/2Dv8AoyT9kP8A8Rs+DX/zFUf8O+/2Dv8AoyT9kP8A8Rs+DX/zFV9fUUXfd/1/wy+4D5BP/BPr9g08H9iT9kP/AMRs+DX/AMxVMP8AwT0/YGJyf2If2Qyf+za/gz/8xdfYNFF33f3/ANdkB8fD/gnp+wKDkfsQ/shgj/q2v4M//MXU3/Dv39g8cf8ADFH7I3HT/jGz4Nf/ADGV9d0UXfd/eB8if8O/f2Dz/wA2T/sjf+I2fBr/AOYyoz/wT4/YMJyf2J/2R/8AxG34Nf8AzGV9f0UXfd/f/XZAfH5/4J8fsGHr+xP+yOfr+zb8Gv8A5jK63wT+xz+yf8M/EWn+L/ht+zF+zp8PvFujytPo3inwT8D/AIZeFPEWkXDwzWrzabrXh/wxp2pWcktpc3VpK9vcxyPb3M0O8RyypJ9J0UXfd/eADOBk5OOTjGT645x9M0UUUgCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKK/Cn/iJQ/4Iq/9HqSf+I5/tX//ADialH/Byn/wRTH/ADegx+v7On7WH9PgSKAP3Ror8Lv+IlP/AIIp/wDR6B/8R0/aw/8AnFUf8RKf/BFP/o9A/wDiOn7WH/ziqAP3Ror8Lv8AiJT/AOCKf/R6B/8AEdP2sP8A5xVH/ESn/wAEU/8Ao9A/+I6ftYf/ADiqAP3Ror8Lv+IlP/gin/0egf8AxHT9rD/5xVH/ABEp/wDBFP8A6PQP/iOn7WH/AM4qgD90aK/C7/iJT/4Ip/8AR6B/8R0/aw/+cVR/xEp/8EU/+j0D/wCI6ftYf/OKoA/dGivwu/4iU/8Agin/ANHoH/xHT9rD/wCcVR/xEp/8EU/+j0D/AOI6ftYf/OKoA/dGivwu/wCIlP8A4Ip/9HoH/wAR0/aw/wDnFUf8RKf/AART/wCj0D/4jp+1h/8AOKoA/dGivwu/4iU/+CKf/R6B/wDEdP2sP/nFUf8AESn/AMEU/wDo9A/+I6ftYf8AziqAP3Ror8Lv+IlP/gin/wBHoH/xHT9rD/5xVH/ESn/wRT/6PQP/AIjp+1h/84qgD90aK/C7/iJT/wCCKf8A0egf/EdP2sP/AJxVH/ESn/wRT/6PQP8A4jp+1h/84qgD90aK/C7/AIiU/wDgin/0egf/ABHT9rD/AOcVR/xEp/8ABFP/AKPQP/iOn7WH/wA4qgD90aK/C7/iJT/4Ip/9HoH/AMR0/aw/+cVR/wARKf8AwRT/AOj0D/4jp+1h/wDOKoA/dGivwu/4iU/+CKf/AEegf/EdP2sP/nFUf8RKf/BFP/o9A/8AiOn7WH/ziqAP3Ror8Lv+IlP/AIIp/wDR6B/8R0/aw/8AnFUf8RKf/BFP/o9A/wDiOn7WH/ziqAP3Ror8Lv8AiJT/AOCKf/R6B/8AEdP2sP8A5xVH/ESn/wAEU/8Ao9A/+I6ftYf/ADiqAP3Ror8Lv+IlP/gin/0egf8AxHT9rD/5xVH/ABEp/wDBFP8A6PQP/iOn7WH/AM4qgD90aK/C7/iJT/4Ip/8AR6B/8R0/aw/+cVR/xEp/8EU/+j0D/wCI6ftYf/OKoA/dGivwu/4iU/8Agin/ANHoH/xHT9rD/wCcVR/xEp/8EU/+j0D/AOI6ftYf/OKoA/dGivwu/wCIlP8A4Ip/9HoH/wAR0/aw/wDnFUf8RKf/AART/wCj0D/4jp+1h/8AOKoA/dGivwu/4iU/+CKf/R6B/wDEdP2sP/nFUf8AESn/AMEU/wDo9A/+I6ftYf8AziqAP3Ror8Lv+IlP/gin/wBHoH/xHT9rD/5xVH/ESn/wRT/6PQP/AIjp+1h/84qgD90aK/C7/iJT/wCCKf8A0egf/EdP2sP/AJxVH/ESn/wRT/6PQP8A4jp+1h/84qgD90aK/C7/AIiU/wDgin/0egf/ABHT9rD/AOcVR/xEp/8ABFP/AKPQP/iOn7WH/wA4qgD90aK/C7/iJT/4Ip/9HoH/AMR0/aw/+cVR/wARKf8AwRT/AOj0D/4jp+1h/wDOKoA/dGivwu/4iU/+CKf/AEegf/EdP2sP/nFUf8RKf/BFP/o9A/8AiOn7WH/ziqAP3Ror8Lv+IlP/AIIp/wDR6B/8R0/aw/8AnFUf8RKf/BFP/o9A/wDiOn7WH/ziqAP3Ror8Lv8AiJT/AOCKf/R6B/8AEdP2sP8A5xVH/ESn/wAEU/8Ao9A/+I6ftYf/ADiqAP3Ror8Lv+IlP/gin/0egf8AxHT9rD/5xVH/ABEp/wDBFP8A6PQP/iOn7WH/AM4qgD90aK/C7/iJT/4Ip/8AR6B/8R0/aw/+cVR/xEp/8EU/+j0D/wCI6ftYf/OKoA/dGivwu/4iU/8Agin/ANHoH/xHT9rD/wCcVR/xEp/8EU/+j0D/AOI6ftYf/OKoA/dGivwu/wCIlP8A4Ip/9HoH/wAR0/aw/wDnFUf8RKf/AART/wCj0D/4jp+1h/8AOKoA/dGivwu/4iU/+CKf/R6B/wDEdP2sP/nFUf8AESn/AMEU/wDo9A/+I6ftYf8AziqAP3Ror8Lv+IlP/gin/wBHoH/xHT9rD/5xVH/ESn/wRT/6PQP/AIjp+1h/84qgD90aK/C7/iJT/wCCKf8A0egf/EdP2sP/AJxVH/ESn/wRT/6PQP8A4jp+1h/84qgD90aK/C7/AIiU/wDgin/0egf/ABHT9rD/AOcVR/xEp/8ABFP/AKPQP/iOn7WH/wA4qgD90aK/C7/iJT/4Ip/9HoH/AMR0/aw/+cVR/wARKf8AwRT/AOj0D/4jp+1h/wDOKoA/dGivwu/4iU/+CKf/AEegf/EdP2sP/nFUf8RKf/BFP/o9A/8AiOn7WH/ziqAP3Ror8Lv+IlP/AIIp/wDR6B/8R0/aw/8AnFUf8RKf/BFP/o9A/wDiOn7WH/ziqAP3Ror8Lv8AiJT/AOCKf/R6B/8AEdP2sP8A5xVH/ESn/wAEU/8Ao9A/+I6ftYf/ADiqAP3Ror8Lv+IlP/gin/0egf8AxHT9rD/5xVH/ABEp/wDBFP8A6PQP/iOn7WH/AM4qgD90aK/C7/iJT/4Ip/8AR6B/8R0/aw/+cVR/xEp/8EU/+j0D/wCI6ftYf/OKoA//2bhK9z8AAAAAPduXk868YALAnlSr+mHhew==',
            'role_type' => $role_type,
            'typedata' => $typedata
        ];
        $params = array_merge($data, [
            'nickname'  => preg_match("/^1[3-9]{1}\d{9}$/", $username) ? substr_replace($username, '****', 3, 4) : $username,
            'salt'      => Random::alnum(),
            'jointime'  => $time,
            'joinip'    => $ip,
            'logintime' => $time,
            'loginip'   => $ip,
            'prevtime'  => $time,
            'status'    => 'normal'
        ]);
        $params['password'] = $this->getEncryptPassword($password, $params['salt']);
        $params = array_merge($params, $extend);

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            if(isset($extend['id'])){
                //微信扫码原来的id信息删掉
                User::where('id',$extend['id'])->delete();
            }
            $user = User::create($params, true);

            $this->_user = User::get($user->id);

            //设置Token
            $this->_token = Random::uuid();
            Token::set($this->_token, $user->id, $this->keeptime);

            //设置登录状态
            $this->_logined = true;

            //注册成功的事件
            Hook::listen("user_register_successed", $this->_user, $data);
            Db::commit();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * 用户登录
     *
     * @param string $account  账号,用户名、邮箱、手机号
     * @param string $password 密码
     * @return boolean
     */
    public function login($account, $password)
    {
        $field = Validate::is($account, 'email') ? 'email' : (Validate::regex($account, '/^1\d{10}$/') ? 'mobile' : 'username');
        $user = User::get([$field => $account]);
        if (!$user) {
            $this->setError('Account is incorrect');
            return false;
        }

        if ($user->status != 'normal') {
            $this->setError('Account is locked');
            return false;
        }
        if ($user->password != $this->getEncryptPassword($password, $user->salt)) {
            $this->setError('Password is incorrect');
            return false;
        }

        //直接登录会员
        return $this->direct($user->id);
    }

    public function save_login_time($user){
        if(!UserLoginLog::where('user_id',$user['id'])->whereTime('login_time', 'today')->count()){
            $res = new UserLoginLog();
            $res->login_time = time();
            $res->user_id = $user['id'];
            $res->typedata = $user['typedata'];
            $res->role_type = $user['role_type'];
            $res->save();
        }

    }


    /**
     * 退出
     *
     * @return boolean
     */
    public function logout()
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //设置登录标识
        $this->_logined = false;
        //删除Token
        Token::delete($this->_token);
        //退出成功的事件
        Hook::listen("user_logout_successed", $this->_user);
        return true;
    }

    /**
     * 修改密码
     * @param string $newpassword       新密码
     * @param string $oldpassword       旧密码
     * @param bool   $ignoreoldpassword 忽略旧密码
     * @return boolean
     */
    public function changepwd($newpassword, $oldpassword = '', $ignoreoldpassword = false)
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //判断旧密码是否正确
        if ($this->_user->password == $this->getEncryptPassword($oldpassword, $this->_user->salt) || $ignoreoldpassword) {
            Db::startTrans();
            try {
                $salt = Random::alnum();
                $newpassword = $this->getEncryptPassword($newpassword, $salt);
                $this->_user->save(['loginfailure' => 0, 'password' => $newpassword, 'salt' => $salt]);

                Token::delete($this->_token);
                //修改密码成功的事件
                Hook::listen("user_changepwd_successed", $this->_user);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->setError($e->getMessage());
                return false;
            }
            return true;
        } else {
            $this->setError('Password is incorrect');
            return false;
        }
    }

    /**
     * 直接登录账号
     * @param int $user_id
     * @return boolean
     */
    public function direct($user_id)
    {
        $user = User::get($user_id);
        if ($user) {
            Db::startTrans();
            try {
                $ip = request()->ip();
                $time = time();

                //判断连续登录和最大连续登录
                if ($user->logintime < \fast\Date::unixtime('day')) {
                    $user->successions = $user->logintime < \fast\Date::unixtime('day', -1) ? 1 : $user->successions + 1;
                    $user->maxsuccessions = max($user->successions, $user->maxsuccessions);
                }

                $user->prevtime = $user->logintime;
                //记录本次登录的IP和时间
                $user->loginip = $ip;
                $user->logintime = $time;
                //重置登录失败次数
                $user->loginfailure = 0;

                $user->save();

                $this->_user = $user;

                $this->_token = Random::uuid();
                Token::set($this->_token, $user->id, $this->keeptime);

                $this->_logined = true;

                //登录成功的事件
                Hook::listen("user_login_successed", $this->_user);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->setError($e->getMessage());
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测是否是否有对应权限
     * @param string $path   控制器/方法
     * @param string $module 模块 默认为当前模块
     * @return boolean
     */
    public function check($path = null, $module = null)
    {
        if (!$this->_logined) {
            return false;
        }

        $ruleList = $this->getRuleList();
        $rules = [];
        foreach ($ruleList as $k => $v) {
            $rules[] = $v['name'];
        }
        $url = ($module ? $module : request()->module()) . '/' . (is_null($path) ? $this->getRequestUri() : $path);
        $url = strtolower(str_replace('.', '/', $url));
        return in_array($url, $rules);
    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取会员基本信息
     */
    public function getUserinfo()
    {
        $data = $this->_user->toArray();
        $allowFields = $this->getAllowFields();
        $userinfo = array_intersect_key($data, array_flip($allowFields));
        $userinfo = array_merge($userinfo, Token::get($this->_token));

        $userinfo['province_name'] = Db::name("area")->where(['id' => $userinfo['province_id']])->value("name");
        $userinfo['city_name'] = Db::name("area")->where(['id' => $userinfo['city_id']])->value("name");
        $userinfo['district_name'] = Db::name("area")->where(['id' => $userinfo['district_id']])->value("name");

        //if($userinfo['typedata'] == 2){
            //$userinfo['verify_status'] = $userinfo['enterprise_status'];
        //}
        //unset($userinfo['enterprise_status']);
        return $userinfo;
    }

    /**
     * 获取会员认证信息
     */
    public function getVerification()
    {

        $userArchive = UserArchive::where('user_id', $this->_user->id)->order('id','desc')->find();
        if(!$userArchive){
            return false;
        }
        
        $data = $userArchive->toArray();
        $allowFields = $this->getAllowFields2();
        $userinfo = array_intersect_key($data, array_flip($allowFields));
        
        return $userinfo;
    }

    /**
     * 获取专家认证信息
     */
    public function getSpecVerification()
    {
        $userArchive = SpecialistModel::alias("s")->where('s.user_id', $this->_user->id)
            ->join('fa_order_comment c', 's.id = c.to_user_id', 'left')
            ->field("s.*,ROUND(IFNULL(AVG(c.points), 0), 1) as avg_score")
            ->order('s.id','desc')
            ->find();
        if(!$userArchive){
            return false;
        }
        
        $data = $userArchive->toArray();
        $data['favorable_comment'] = OrderComment::where(['to_user_id' => $data['id']])->where('points >= 3')->count();
        $data['income'] = Order::where(['specialist_id' => $data['id']])->where("status = '5'")->sum('total');
        $allowFields = $this->getAllowFields3();
        $userinfo = array_intersect_key($data, array_flip($allowFields));
        
        return $data;
    }

    /**
     * 获取会员组别规则列表
     * @return array|bool|\PDOStatement|string|\think\Collection
     */
    public function getRuleList()
    {
        if ($this->rules) {
            return $this->rules;
        }
        $group = $this->_user->group;
        if (!$group) {
            return [];
        }
        $rules = explode(',', $group->rules);
        $this->rules = UserRule::where('status', 'normal')->where('id', 'in', $rules)->field('id,pid,name,title,ismenu')->select();
        return $this->rules;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields2()
    {
        return $this->allowFields2;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields3()
    {
        return $this->allowFields3;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 删除一个指定会员
     * @param int $user_id 会员ID
     * @return boolean
     */
    public function delete($user_id)
    {
        $user = User::get($user_id);
        if (!$user) {
            return false;
        }
        Db::startTrans();
        try {
            // 删除会员
            User::destroy($user_id);
            // 删除会员指定的所有Token
            Token::clear($user_id);

            Hook::listen("user_delete_successed", $user);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password 密码
     * @param string $salt     密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 设置会话有效时间
     * @param int $keeptime 默认为永久
     */
    public function keeptime($keeptime = 0)
    {
        $this->keeptime = $keeptime;
    }

    /**
     * 渲染用户数据
     * @param array  $datalist  二维数组
     * @param mixed  $fields    加载的字段列表
     * @param string $fieldkey  渲染的字段
     * @param string $renderkey 结果字段
     * @return array
     */
    public function render(&$datalist, $fields = [], $fieldkey = 'user_id', $renderkey = 'userinfo')
    {
        $fields = !$fields ? ['id', 'nickname', 'level', 'avatar'] : (is_array($fields) ? $fields : explode(',', $fields));
        $ids = [];
        foreach ($datalist as $k => $v) {
            if (!isset($v[$fieldkey])) {
                continue;
            }
            $ids[] = $v[$fieldkey];
        }
        $list = [];
        if ($ids) {
            if (!in_array('id', $fields)) {
                $fields[] = 'id';
            }
            $ids = array_unique($ids);
            $selectlist = User::where('id', 'in', $ids)->column($fields);
            foreach ($selectlist as $k => $v) {
                $list[$v['id']] = $v;
            }
        }
        foreach ($datalist as $k => &$v) {
            $v[$renderkey] = $list[$v[$fieldkey]] ?? null;
        }
        unset($v);
        return $datalist;
    }

    /**
     * 设置错误信息
     *
     * @param string $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }
}
