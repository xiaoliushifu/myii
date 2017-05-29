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
            //搞错了，下面这些属性，是实例化Dispatcher(不是logger)类，由反射解析依赖传入的
            //又搞错了，这些属性最终还是给了logger类，因为Dispatcher通过__set方法，给logger设置了属性。哎
            //'traceLevel' => YII_DEBUG ? 3 : 0,
            'traceLevel' => 0,#调用堆栈信息，明天再看
            'flushInterval' => 1,#配置输出到target的日志积累量
            'targets' => [
                [
                    //Target类的levels,exportInterval可以在反射过程的解析依赖时传入，达到初始化的目的。
                    'class' => 'yii\log\FileTarget',
                    //因为这里写的日志级别是error和warning，所以我们手动打日志时，最好使用yii:error或者yii::warning
                    //否则会因为级别不正确而被过滤掉，最终看不到日志信息。
                    //'levels' => ['error', 'warning','trace','info'], #不指定，就是不被过滤
                    'categories'=>['yii\db\*','application'],#一定要记录的日志类别
                    'except'=>['application'],#排除类别的日志
                    'exportInterval' => 1,#该target也可以配置积累量
                    'logVars'=>[],#禁止记录那些上下文信息，太多太乱了
                    'prefix'=>function($message){#日志还能自定义前缀哟
                        
                    },
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
