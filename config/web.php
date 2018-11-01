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
            //'class' => 'yii\caching\DbCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            //是否使用cookie方式保存认证信息（默认false,即使用session来保存认证信息）
            'enableAutoLogin' => true,
            //'enableSession' => false,
            'authTimeout'=>20,
            //'absoluteAuthTimeout'=>100,
        ],
		//错误处理器组件必须有
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        ///*
        'urlManager' => [
            //所谓开启美化，就是使用Path Info而已。
            //但是下面还有rules列表，给出url的特殊匹配模式，如果匹配就转换为对应的path info格式的字符串
            //没有匹配，就仍然使用原始的pathinfo呗
            'enablePrettyUrl' => true,  //开启第二种路由解析机制（所谓的美化URL)，而不使用默认的r
            'showScriptName' => false,  //不显示index.php。需要web服务器的rewrite机制配合
            //rules列出的，是和原始url的pathinfo部分进行匹配
            //除非有特别的url匹配需要，否则只需开启enablePrettyUrl就行，无需写rules。
            //当rules都没有匹配到时，将会使用pathinfo就是最终的路由了
            'rules' => [
				//'index'=>'site/msg',
				//'abc'=>'site/msg',
				//'index/.*index'=>'site/msg',
            ],
            //该属性默认就是false，即不开启严格解析。当rules都没有匹配时，直接按照path info解析，
            //开启严格解析情况下，如果rules都没有匹配时，将会报异常。NotFoundHttpException
            'enableStrictParsing'=>false,
        ],
        //*/
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
//    $config['bootstrap'][] = 'debug';
    //队列要在bootstrap过程启动
    $config['bootstrap'][] = 'queue';

    //添加一个模块debug
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    //用不到就把它去掉
    //$config['bootstrap'][] = 'gii';
    
    //添加一个模块gii
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}
//所有这些$config配置，都在Component::__construct()时，添加为yii/web/application对象的属性了。
return $config;
