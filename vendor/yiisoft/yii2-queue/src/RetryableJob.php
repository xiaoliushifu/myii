<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

/**
 * 如果你的任务在执行失败时，希望给出机会再次尝试执行，那么
 * 需要在任务类里实现下述的两个方法
 * Interface RetryableJob
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface RetryableJob extends Job
{
    /**
     *
     * @return int time to reserve in seconds
     */
    public function getTtr();

    /**
     * @param int $attempt number
     * @param \Exception $error from last execute of the job
     * @return bool  返回true表示可以下次再执行
     */
    public function canRetry($attempt, $error);
}