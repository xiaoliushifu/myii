<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

/**
 * Class ExecEvent
 * 任务执行时，也就是execute之前触发该事件
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ExecEvent extends JobEvent
{
    /**
     * @var int attempt number
     */
    public $attempt;
}