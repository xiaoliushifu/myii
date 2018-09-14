<?php
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
//这是composer的自动加载机制
require(__DIR__ . '/../vendor/autoload.php');
//注册yii框架自己的自动加载机制，并首次出现Yii全局对象，还把容器对象填充到Yii的属性$container
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

//开始自己的配置，还可以有更多的配置文件
$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../config/web.php'),
    require(__DIR__ . '/../config/web-local.php')
);

//这一行代码，把它的父类base\Application\,base\Module,Servicelocator也都引入到了内存中
(new yii\web\Application($config))->run();
