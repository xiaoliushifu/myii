<?php

$config = [
    'components' => [
        //队列组件的配置,一个队列对应一个组件配置
        'queue' => [
            'class' => zhuravljov\yii\queue\db\Queue::class,
            'as log' => zhuravljov\yii\queue\LogBehavior::class,
            'mutex' => \yii\mutex\MysqlMutex::class
            // Other driver options
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
//                  'host' => 'smtp.yunzongnet.com',
                 'host' => 'smtp.qiye.163.com',
                 'username' => 'liumingwei@xiyun.com.cn',
                'password' => 'XYGJhttpis80',
//             'username' => 'liumingwei@yunzongnet.com',
//                 'password' => '6oA"3nC"',
                
//                  'username' => 'no-reply@yunzongnet.com',
//                  'password' => '0eG>8pC$',
//                  
//                  'port' => '',
                 'port' => '25',
                 //'encryption' => 'tls',
             ],
        ],
        'log' => [
            //'traceLevel' => YII_DEBUG ? 3 : 0,
            'traceLevel' => 0,#调用堆栈信息，明天再看
            'flushInterval' => 1,#配置输出到target的日志积累量
            'targets' => [
                [
                    'class'=>'yii\log\FileTarget',
                    //加上profile，就会打入进去到app.log里
//                    'levels' => ['error','warning','profile'],#空数组代表任何级别的都打印
                    'levels' => ['error'],
                    //'categories'=>['yii\db\*','application'],#一定要记录的日志类别
                    'exportInterval' => 1,#该target也可以配置积累量
                    'logVars'=>[],#不是每条日志都有这些信息，而是在最后真正输出到媒介时才补充这些信息到日志中
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
        
       //不配置默认在核心组件里就是yii\web\session
        /*  'session' => [
                   'class' => 'yii\redis\Session',
                   'redis' => [
                       'hostname' => 'localhost',
                       'port' => 6379,
                       'database' => 0,
                   ],
             'name' => 'SSID',
          ], */
    ],
    //把模块配置进来
    'modules' => [
        //这个才是模块的id,
        'cus' => [
            'class' => 'app\modules\report\Module',
        ],
        //这个才是模块的id,
        'rbac' => [
            'class' => Wkii\Yii2Rbac\Rbac::class,
        ],
    ],
    'params' => ['adminEmail' => '872140945@qq.com'],
];
return $config;
