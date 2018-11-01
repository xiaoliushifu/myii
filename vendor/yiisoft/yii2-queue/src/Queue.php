<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\di\Instance;
use yii\helpers\VarDumper;
use zhuravljov\yii\queue\serializers\PhpSerializer;
use zhuravljov\yii\queue\serializers\Serializer;

/**
 * 基础队列对象
 * 所有驱动队列都继承它（db,redis等）
 * Base Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends Component
{
    /**
     * @event PushEvent
     */
    const EVENT_BEFORE_PUSH = 'beforePush';
    /**
     * @event PushEvent
     */
    const EVENT_AFTER_PUSH = 'afterPush';
    /**前两个事件在加入队列的进程里触发
     * 下述的三个事件是在读取队列执行任务的进程里触发
     * @event ExecEvent
     */
    const EVENT_BEFORE_EXEC = 'beforeExec';
    /**
     * @event ExecEvent
     */
    const EVENT_AFTER_EXEC = 'afterExec';
    /**
     * @event ExecEvent
     */
    const EVENT_AFTER_ERROR = 'afterError';
    /**任务状态1，刚刚加入队列时的状态
     * @see Queue::isWaiting()
     */
    const STATUS_WAITING = 1;
    /**任务被Worker进程读取，但是尚未执行的中间状态
     * @see Queue::isReserved()
     */
    const STATUS_RESERVED = 2;
    /**
     * 任务状态3，任务已经完成（被执行了）
     * @see Queue::isDone()
     */
    const STATUS_DONE = 3;

    /**
     * 任务加入队列时一般需要序列化
     * @var Serializer|array
     */
    public $serializer = PhpSerializer::class;
    /**
     * 默认的任务读取间隔
     * @var int default time to reserve a job
     */
    public $ttr = 300;
    /**
     * @var int default attempt count
     */
    public $attempts = 1;

    private $pushTtr;
    private $pushDelay;
    private $pushPriority;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->serializer = Instance::ensure($this->serializer, Serializer::class);
    }

    /**
     * Sets TTR for job execute
     *
     * @param int|mixed $value
     * @return $this
     */
    public function ttr($value)
    {
        $this->pushTtr = $value;
        return $this;
    }

    /**
     * Sets delay for later execute
     *
     * @param int|mixed $value
     * @return $this
     */
    public function delay($value)
    {
        $this->pushDelay = $value;
        return $this;
    }

    /**
     * Sets job priority
     *
     * @param mixed $value
     * @return $this
     */
    public function priority($value)
    {
        $this->pushPriority = $value;
        return $this;
    }

    /**
     * Pushes job into queue
     *
     * @param Job|mixed $job
     * @return string|null id of a job message
     */
    public function push($job)
    {
        $event = new PushEvent([
            'job' => $job,
            'ttr' => $job instanceof RetryableJob
                ? $job->getTtr()
                : ($this->pushTtr ?: $this->ttr),
            'delay' => $this->pushDelay ?: 0,
            'priority' => $this->pushPriority,
        ]);
        $this->pushTtr = null;
        $this->pushDelay = null;
        $this->pushPriority = null;
        //触发before事件
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        if ($event->handled) {
            return null;
        }
        //序列号任务对象job
        $message = $this->serializer->serialize($event->job);
        //加入队列（ttr,delay,priority都来自事件对象）
        $event->id = $this->pushMessage($message, $event->ttr, $event->delay, $event->priority);
        //触发after事件
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param string $message
     * @param int $ttr time to reserve in seconds
     * @param int $delay
     * @param mixed $priority
     * @return string|null id of a job message
     */
    abstract protected function pushMessage($message, $ttr, $delay, $priority);

    /**
     *
     * 这是处理任务的方法，也就是说，把从队列中取出的任务执行
     * @param string|null $id of a job message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @return boolean
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        //反序列化
        $job = $this->serializer->unserialize($message);
        if (!($job instanceof Job)) {
            throw new InvalidParamException(strtr('Job must be {class} object instead of {dump}.', [
                '{class}' => Job::class,
                '{dump}' => VarDumper::dumpAsString($job),
            ]));
        }
        //执行时，也得触发一个execute事件，注意，执行触发是从控制台而不是web
        $event = new ExecEvent([
            'id' => $id,
            'job' => $job,
            'ttr' => $ttr,
            'attempt' => $attempt,
        ]);
        $this->trigger(self::EVENT_BEFORE_EXEC, $event);
        if ($event->handled) {
            //提前结束，不再执行任务的execute方法
            return true;
        }

        try {
            //到此任务真正执行，走它的execute方法，传递参数$this,当前队列对象
            $event->job->execute($this);
        } catch (\Exception $error) {
            return $this->handleError($event->id, $event->job, $event->ttr, $event->attempt, $error);
        }
        //再触发after
        $this->trigger(self::EVENT_AFTER_EXEC, $event);

        return true;
    }

    /**
     * 执行时出错了
     * @param string|null $id
     * @param Job $job
     * @param int $ttr
     * @param int $attempt
     * @param \Exception $error
     * @return bool
     * @internal
     */
    public function handleError($id, $job, $ttr, $attempt, $error)
    {
        $event = new ErrorEvent([
            'id' => $id,
            'job' => $job,
            'ttr' => $ttr,
            'attempt' => $attempt,
            'error' => $error,
            'retry' => $job instanceof RetryableJob
                ? $job->canRetry($attempt, $error)
                : $attempt < $this->attempts,
        ]);
        //触发EVENT_AFTER_ERROR事件
        $this->trigger(self::EVENT_AFTER_ERROR, $event);
        //retry如果是false,则不放回队列，如果是true,则仍然放回队列，留给下次机会执行
        //所谓放回去，并不是真的insert,其实它仍然在队列中，只是reserved_at字段值发生变化了而已
        return !$event->retry;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isWaiting($id)
    {
        return $this->status($id) === Queue::STATUS_WAITING;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isReserved($id)
    {
        return $this->status($id) === Queue::STATUS_RESERVED;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isDone($id)
    {
        return $this->status($id) === Queue::STATUS_DONE;
    }

    /**
     * @param string $id of a job message
     * @return int status code
     */
    abstract protected function status($id);
}