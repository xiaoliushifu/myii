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
            //'traceLevel' => YII_DEBUG ? 3 : 0,
            'traceLevel' => 0,#调用堆栈信息，明天再看
            'flushInterval' => 1,#配置输出到target的日志积累量
            'targets' => [
                //同一个日志信息，可以打到不同的目标里
                [
                    'class' => 'yii\log\DbTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\EmailTarget',
                   // 'mailer' => 'mailer', //默认就是使用yii自己的mailer
                    'levels' => ['error', 'warning','info'],
                    'message' => [
                                   'from' => ['liumingwei@yunzongnet.com'],
                                   'to' => ['chenxiaolong1@yunzongnet.com'],
                                   'BCC'=>['872140945@qq.com'],
                                   'subject' => '以邮件形式发送日志',
                    ],
                    //'categories'=>['yii\db\*','application'],#一定要记录的日志类别
                    'exportInterval' => 1,#该target也可以配置积累量
                    'logVars'=>[],#不是每条日志都有这些信息，而是在最后真正输出到媒介时才补充这些信息到日志中
                    'prefix'=>function($message){
                        return '';
                    }
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
//所有这些$config配置，都在Component::__construct()时，添加为yii/web/application对象的属性了。
return $config;
