<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

use yii\base\Event;

/**
 * Class JobEvent
 * 整体任务（job）事件的基类对象
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JobEvent extends Event
{
    /**
     * @var string|null unique id of a job
     */
    public $id;  //任务ID,任务在队列里的唯一标示
    /**
     * @var Job
     */
    public $job; //任务对象
    /**
     * 两次读取同一个任务时的最小时间间隔（只有第一次读取后但是执行失败的情况下
     * 才有可能二次读取任务）
     * @var int time to reserve in seconds of the job
     */
    public $ttr;
}