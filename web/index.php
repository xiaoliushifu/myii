<?php
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

//这是composer的自动加载机制
require(__DIR__ . '/../vendor/autoload.php');
//注册yii框架自己的自动加载机制，并首次出现Yii全局对象，还把容器对象填充到Yii的属性$container
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

//开始自己的配置，还可以有更多的配置文件
$config = require(__DIR__ . '/../config/web.php');
(new yii\web\Application($config))->run();
