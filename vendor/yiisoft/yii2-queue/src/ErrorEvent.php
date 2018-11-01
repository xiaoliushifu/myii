<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

/**
 * 在execute里抛出异常时，会触发该事件
 * 这俩参数很重要，其中$retry决定是否还把当前失败的任务再次放回队列，留给后续机会执行
 * Class ErrorEvent
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ErrorEvent extends ExecEvent
{
    /**
     * @var \Exception
     */
    public $error;
    /**
     * @var bool
     */
    public $retry;
}