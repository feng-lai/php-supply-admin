<?php

namespace addons\cos;

use addons\cos\library\Auth;
use app\common\library\Menu;
use fast\Http;
use Qcloud\Cos\Client;
use think\Addons;
use think\App;
use think\Loader;

/**
 * COS插件
 */
class Cos extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {

        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {

        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {

        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {

        return true;
    }

    /**
     * 渲染命名空间配置信息
     */
    public function appInit()
    {
        if (!class_exists('\Qcloud\Cos\Client')) {
            Loader::addNamespace('Qcloud\Cos', $this->addons_path . str_replace('/', DS, 'library/Qcloud/Cos/'));
        }
        if (!class_exists('\GuzzleHttp\Command\Command')) {
            Loader::addNamespace('GuzzleHttp\Command', $this->addons_path . str_replace('/', DS, 'library/Guzzle/command/src/'));
        }
        if (!class_exists('\GuzzleHttp\Command\Guzzle\Description')) {
            Loader::addNamespace('GuzzleHttp\Command\Guzzle', $this->addons_path . str_replace('/', DS, 'library/Guzzle/guzzle-services/src/'));
        }
        if (!class_exists('\GuzzleHttp\UriTemplate\UriTemplate')) {
            Loader::addNamespace('GuzzleHttp\UriTemplate', $this->addons_path . str_replace('/', DS, 'library/Guzzle/uri-template/src/'));
        }

    }

    /**
     * 判断是否来源于API上传
     */
    public function moduleInit($request)
    {
        $config = $this->getConfig();
        $module = strtolower($request->module());
        if ($module == 'api' && ($config['apiupload'] ?? 0) &&
            strtolower($request->controller()) == 'common' &&
            strtolower($request->action()) == 'upload') {
            request()->param('isApi', true);
            App::invokeMethod(["\\addons\\cos\\controller\\Index", "upload"], ['isApi' => true]);
        }
    }

    /**
     *
     */
    public function uploadConfigInit(&$upload)
    {
        $config = $this->getConfig();
        $module = request()->module();
        $module = $module ? strtolower($module) : 'index';

        $data = ['deadline' => time() + $config['expire']];
        $signature = hash_hmac('sha1', json_encode($data), $config['secretKey'], true);

        $token = '';
        if (Auth::isModuleAllow()) {
            $token = $config['appId'] . ':' . base64_encode($signature) . ':' . base64_encode(json_encode($data));
        }
        $multipart = [
            'costoken' => $token
        ];

        $upload = array_merge($upload, [
            'cdnurl'     => $config['cdnurl'],
            'uploadurl'  => $config['uploadmode'] == 'client' ? $config['uploadurl'] : addon_url('cos/index/upload', [], false, true),
            'uploadmode' => $config['uploadmode'],
            'bucket'     => $config['bucket'],
            'maxsize'    => $config['maxsize'],
            'mimetype'   => $config['mimetype'],
            'savekey'    => $config['savekey'],
            'chunking'   => (bool)($config['chunking'] ?? $upload['chunking']),
            'chunksize'  => (int)($config['chunksize'] ?? $upload['chunksize']),
            'multipart'  => $multipart,
            'storage'    => $this->getName(),
            'multiple'   => (bool)$config['multiple'],
        ]);
    }

    /**
     * 附件删除后
     */
    public function uploadDelete($attachment)
    {
        $config = $this->getConfig();
        if ($attachment['storage'] == 'cos' && isset($config['syncdelete']) && $config['syncdelete']) {
            $cosConfig = array(
                'region'      => $config['region'],
                'schema'      => 'https', //协议头部，默认为http
                'credentials' => array(
                    'secretId'  => $config['secretId'],
                    'secretKey' => $config['secretKey']
                )
            );
            $oss = new Client($cosConfig);
            $ret = $oss->deleteObject(array('Bucket' => $config['bucket'], 'Key' => ltrim($attachment->url, '/')));

            //如果是服务端中转，还需要删除本地文件
            //if ($config['uploadmode'] == 'server') {
            //    $filePath = ROOT_PATH . 'public' . str_replace('/', DS, $attachment->url);
            //    if ($filePath) {
            //        @unlink($filePath);
            //    }
            //}
        }
        return true;
    }
}
