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
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => false,//开启这个选项时只把邮件放到本地文件系统而不是真的发送
            'transport' => [
                 'class' => 'Swift_SmtpTransport',
                 //'class' => 'Swift_SendmailTransport',
                 'host' => 'mail.yunzongnet.com',
                 'username' => 'liumingwei@yunzongnet.com',
                 'password' => '6oA"3nC"',
                 //'port' => '587',
                 //'encryption' => 'tls',
             ],
        ],
        'log' => [
            //下面这些属性，是在实例化logger类时，由反射解析依赖传入的
            //'traceLevel' => YII_DEBUG ? 3 : 0,
            'traceLevel' => 0,
            'flushInterval' => 1,#配置输出到target的日志积累量
            'targets' => [
                [
                    //Target类的levels,exportInterval可以在反射过程的解析依赖时传入，达到初始化的目的。
                    'class' => 'yii\log\FileTarget',
                    //因为这里写的日志级别是error和warning，所以我们手动打日志时，最好使用yii:error或者yii::warning
                    //否则会因为级别不正确而被过滤掉，最终看不到日志信息。
                    'levels' => ['error', 'warning'],
                    'exportInterval' => 1,#该target也可以配置积累量
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
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
    $config['bootstrap'][] = 'debug';
    
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

return $config;
