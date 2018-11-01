<?php
/**
 * 这是一个任务类，不同的任务定义不同的类
 * 都继承Job接口，实现execute方法就行
 */
namespace app\common\job;


use yii\base\Exception;
use yii\base\Object;
use zhuravljov\yii\queue\Job;
use Yii;
/**
 * User: masterliu
 * Date: 2018/11/1
 * Time: 上午9:47
 */
class DownloadJob extends Object implements Job
{
    public $url;
    public $file;

    public function execute($queue)
    {
        Yii::error('Download 任务执行了 execute方法 参数是队列对象');
//        Yii::error($queue,'queue');
        throw new Exception('就是抛异常不让执行');
//        file_put_contents($this->file, file_get_contents($this->url));
    }
}