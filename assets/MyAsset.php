<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author liumingwei 
 */
class MyAsset extends AssetBundle
{
	//源资源，是和php代码放到一块的地方，需要发布到web可访问的目录才能用
	//发布资源，即已发布过的资源，一般经源资源的发布就变成为已发布资源，一般发布到web可访问的目录
	//外部资源，就是外网的，不是在本地应用的。


    //basePath是发布资源的属性，指明在Web服务器允许对外访问的哪个目录,@webroot就是D:/wamp64/www/basic/web
	//另外，还有一个好处，就是assetManager配置appendTimestamp为true时，还会根据js，css文件的修改
	//自动加上最后修改的时间戳，这样就可以控制静态资源文件的版本信息了。无需我们手动在url后写?a=xxx了
    public $basePath = '@webroot';
    //baseUrl就是相对域名的外部路径，@web是/basic/web,也就是www.xx.com/basic/web,
	//将来配合$js最终可以找到这个js文件，也就是说它会拼接在$js之前
    public $baseUrl = '@web';
    
    //
    public $css = [
       // 'css/site.css',
    ];
    public $js = [
        'static/js/addrepo.js',
    ];
    //依赖其他的资源包，也是AssetBundle类
    //依赖的资源会在当前资源之前加载，且依赖关系也会传递
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
