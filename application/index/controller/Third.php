<?php
namespace app\index\controller;

use addons\third\library\Application;
use addons\third\library\Service;
use app\common\controller\Frontend;
use think\Lang;
use think\Session;

/**
 * 第三方登录控制器
 */
class Third extends Frontend
{
    protected $noNeedLogin = ['prepare'];
    protected $noNeedRight = ['*'];
    protected $app = null;
    protected $options = [];
    protected $layout = 'default';

    public function _initialize()
    {
        parent::_initialize();
        $config = get_addon_config('third');
        $this->app = new Application($config);
    }

    /**
     * 准备绑定
     */
    public function prepare()
    {
        $platform = $this->request->request('platform', '');
        if (!in_array($platform, ['wechat', 'weibo', 'qq'])) {
            $this->error("未找到指定平台");
        }
        $url = $this->request->get('url', '/', 'trim');
        if ($this->auth->id) {
            $this->redirect(url("index/third/bind") . "?" . http_build_query(['platform' => $platform, 'url' => $url]));
        }

        // 授权成功后的回调
        $userinfo = Session::get("{$platform}-userinfo");
        if (!$userinfo) {
            $this->error("操作失败，请返回重试");
        }

        $lang = $this->request->langset();
        $lang = preg_match("/^([a-zA-Z\-_]{2,10})\$/i", $lang) ? $lang : 'zh-cn';
        Lang::load([
            APP_PATH . 'index' . DS . 'lang' . DS . $lang . DS . 'user' . EXT,
        ]);

        $this->view->assign('userinfo', $userinfo['userinfo']);
        $this->view->assign('platform', $platform);
        $this->view->assign('url', $url);
        $this->view->assign('bindurl', url("index/third/bind") . '?' . http_build_query(['platform' => $platform, 'url' => $url]));
        $this->view->assign('captchaType', config('fastadmin.user_register_captcha'));
        $this->view->assign('title', "账号绑定");

        return $this->view->fetch();
    }

    /**
     * 绑定账号
     */
    public function bind()
    {
        $platform = $this->request->request('platform', '');
        if (!in_array($platform, ['wechat', 'weibo', 'qq'])) {
            $this->error("未找到指定平台");
        }
        $url = $this->request->get('url', $this->request->server('HTTP_REFERER', '', 'trim'), 'trim');
        if (!$platform) {
            $this->error("参数不正确");
        }

        $apptype = $platform == 'wechat' ? Service::getApptype() : '';

        // 授权成功后的回调
        $userinfo = Session::get("{$platform}-userinfo");
        if (!$userinfo) {
            $this->redirect(addon_url('third/index/connect', [':platform' => $platform]) . '?url=' . urlencode($url));
        }
        $third = \addons\third\model\Third::where('user_id', $this->auth->id)
            ->where('platform', $platform)
            ->where(function ($query) use ($platform, $apptype) {
                if ($platform == 'wechat') {
                    if (in_array($apptype, ['', 'mp'])) {
                        $query->where('apptype', 'in', ['', 'mp']);
                    } else {
                        $query->where('apptype', $apptype);
                    }
                }
            })
            ->find();
        if ($third) {
            $this->error("已绑定账号，请勿重复绑定");
        }
        $time = time();
        $values = [
            'platform'      => $platform,
            'apptype'       => $apptype,
            'user_id'       => $this->auth->id,
            'openid'        => $userinfo['openid'],
            'openname'      => $userinfo['userinfo']['nickname'] ?? '',
            'access_token'  => $userinfo['access_token'],
            'refresh_token' => $userinfo['refresh_token'],
            'expires_in'    => $userinfo['expires_in'],
            'logintime'     => $time,
            'expiretime'    => $time + $userinfo['expires_in'],
        ];
        $third = \addons\third\model\Third::create($values);
        if ($third) {
            $this->success("账号绑定成功", $url);
        } else {
            $this->error("账号绑定失败，请重试", $url);
        }
    }

    /**
     * 解绑账号
     */
    public function unbind()
    {
        $platform = $this->request->request('platform', '');
        if (!in_array($platform, ['wechat', 'weibo', 'qq'])) {
            $this->error("未找到指定平台");
        }
        $apptype = $platform == 'wechat' ? Service::getApptype() : '';

        $third = \addons\third\model\Third::where('user_id', $this->auth->id)
            ->where('platform', $platform)
            ->where(function ($query) use ($platform, $apptype) {
                if ($platform == 'wechat') {
                    $query->where('apptype', $apptype);
                }
            })
            ->find();
        if (!$third) {
            $this->error("未找到指定的账号绑定信息");
        }
        Session::delete("{$platform}-userinfo");
        $third->delete();
        $this->success("账号解绑成功");
    }
}
