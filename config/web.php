<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    
    //把log(应用)组件加入到引导过程中，每次请求都载入
    'bootstrap' => ['log'],
    //注册应用组件，组件分核心应用组件和普通应用组件
    //在应用中可以任意注册组件， 并可以通过表达式 \Yii::$app->ComponentID 全局访问。
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'AoKJSF4OEaAJ2uzd7TZRmNJOipNJ6UgK',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            //'enableAutoLogin' => true,
            //'enableSession' => false,
            //'authTimeout'=>20,
            'absoluteAuthTimeout'=>300,
        ],
		//错误处理器组件必须有
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
   // $config['bootstrap'][] = 'debug';
    
    //添加一个模块debug
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    
    //添加一个模块gii
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}
//所有这些$config配置，都在Component::__construct()时，添加为yii/web/application对象的属性了。
return $config;
