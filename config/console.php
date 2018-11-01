<?php

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        //队列组件的配置, 由于控制台和web配置文件不同，所以必须一样的配置在两个文件各写一份
        'queue' => [
            'class' => zhuravljov\yii\queue\db\Queue::class,
            'as log' => zhuravljov\yii\queue\LogBehavior::class,
            'mutex' => \yii\mutex\MysqlMutex::class,
            'deleteReleased' => false,
            //某些事件必须在控制台绑定
            'on beforeExec' => function ($event) {
//                \Yii::error($event,'eventObj');

                //handled=true这样不会执行当前任务的execute方法而是提前结束，把该任务当作正常结束（软删除）
//                $event->handled=true;
            },

            'on afterError' => function ($event) {
                \Yii::error($event,'eventObj');
                //这样本次失败的任务不会被删除，后续还可以继续被Worker
                \Yii::error($event->attempt,'$attempt 次数');
                $event->retry=true;
            },
            // Other driver options
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
//                    'levels' => ['error','info'],
                    'levels' => ['error'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
    //这是配置什么？controllerMap，忘记了吗？这也是Yii的优先级判断，稍后细说
    'controllerMap' => [
        // ...
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'zhuravljov\yii\queue\db\migrations'
            ],
        ],
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
//    $config['bootstrap'][] = 'gii';
    $config['bootstrap'][] = 'queue';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
