<?php

namespace addons\cos\library;

class Auth
{

    public function __construct()
    {

    }

    public static function isModuleAllow()
    {
        $config = get_addon_config('cos');
        $module = request()->module();
        $module = $module ? strtolower($module) : 'index';
        $noNeedLogin = array_filter(explode(',', $config['noneedlogin'] ?? ''));
        $isModuleLogin = false;
        $tagName = 'upload_config_checklogin';
        foreach (\think\Hook::get($tagName) as $index => $name) {
            if (\think\Hook::exec($name, $tagName)) {
                $isModuleLogin = true;
                break;
            }
        }
        if (in_array($module, $noNeedLogin)
            || ($module == 'admin' && \app\admin\library\Auth::instance()->id)
            || ($module != 'admin' && \app\common\library\Auth::instance()->id)
            || $isModuleLogin) {
            return true;
        } else {
            return false;
        }
    }

}
