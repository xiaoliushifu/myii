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
    //basePath是发布资源的属性，指明在Web服务器允许对外访问的哪个目录,@webroot就是D:/wamp64/www/basic/web
    public $basePath = '@webroot';
    //baseUrl就是相对域名的外部路径，@web是/basic/web,也就是www.xx.com/basic/web
    public $baseUrl = '@web';
    
    //
    public $css = [
       // 'css/site.css',
    ];
    public $js = [
        'static/js/addrepo.js',
    ];
    //依赖其他的资源包，也是php的AssetBundle类
    //依赖的资源会在当前资源之前加载，且依赖关系也会传递
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
