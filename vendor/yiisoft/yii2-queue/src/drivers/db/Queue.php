<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\db;

use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use zhuravljov\yii\queue\cli\Queue as CliQueue;
use zhuravljov\yii\queue\cli\Signal;

/**
 * 数据库驱动的队列实现,继承基类CliQueue
 * Db Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    /**
     * 数据库连接对象
     * @var Connection|array|string
     */
    public $db = 'db';
    /**
     * 互斥锁对象（用来互斥地从队列里获取任务）
     * @var Mutex|array|string
     */
    public $mutex = 'mutex';
    /**
     * 获取锁的超时秒数（3秒内获得不了就退出本次查询）
     * @var int timeout
     */
    public $mutexTimeout = 3;
    /**实现队列的表名字
     * @var string table name
     */
    public $tableName = '{{%queue}}';
    /**
     * 频道（对应一种任务，因为同一个数据库表里据此字段区别不同的任务，也就是一种队列）
     * @var string
     */
    public $channel = 'queue';
    /**
     * 是否删除已经处于released状态的消息（任务），默认是删除,false表示软删除
     * @var boolean ability to delete released messages from table
     */
    public $deleteReleased = true;

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //初始化数据库连接对象
        $this->db = Instance::ensure($this->db, Connection::class);
        //初始化互斥锁对象
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
    }

    /**
     * 从队列中取出任务----执行-----销毁任务
     * Runs all jobs from db-queue.
     */
    public function run()
    {
        while (!Signal::isExit() && ($payload = $this->reserve())) {
            //$payload就是从队列里获得的可以执行的任务（php对象），交给handleMessage处理
            if ($this->handleMessage(
                $payload['id'],
                $payload['job'],
                $payload['ttr'],
                $payload['attempt']
            )) {
                //处理完后释放该任务（可以从数据库里硬删除，也可以标记为软删除）
                $this->release($payload);
            }
        }
    }

    /**
     * Listens db-queue and runs new jobs.
     * 监听其实就是无限循环不断地去run,并没有启动操作系统级别的守护进程比如cron
     * 0表示不延迟，其它大于0的整数表示延迟查询地秒数
     * listen在命令行操纵   ./yii queue/listen
     * @param integer $delay number of seconds for waiting new job.
     */
    public function listen($delay)
    {
        do {
            $this->run();
            //这个处理方法好，即无限循环，还能有一定的延迟！
        } while (!$delay || sleep($delay) === 0);
    }

    /**
     * DB驱动加入队列必须实现的方法
     * 把任务加入到队列里，该方法正常在web端进程调用执行
     * 返回任务编号,一般是自增主键的ID
     * Db驱动的队列就是一张queue表而已。
     * 但是里面的字段你得好好理解才行
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $this->db->createCommand()->insert($this->tableName, [
            'channel' => $this->channel,
            'job' => $message,
            'pushed_at' => time(),//加入队列的时间
            'ttr' => $ttr,//time to reserved,就是每两次被Worker进程从队列中读取的时间间隔，两次？
            //如果某个任务执行失败了，它才有机会回到队列等待二次被执行
            'delay' => $delay,// 延迟秒数（正常情况下，某任务一旦加入到队列，就有可能被Worker进程立即读取并执行）
            'priority' => $priority ?: 1024,// 优先级，数据库就是排序，越小优先级越高
        ])->execute();
        $tableSchema = $this->db->getTableSchema($this->tableName);
        $id = $this->db->getLastInsertID($tableSchema->sequenceName);

        return $id;
    }

    /**
     * 一般是被控制台的 ./yii queue/info  命令使用
     * 检测指定任务的状态
     * 1刚刚加入到队列，尚未执行
     * 2从队列中取出正在被执行
     * 3已执行完毕
     * @inheritdoc
     */
    protected function status($id)
    {
        $payload = (new Query())
            ->from($this->tableName)
            ->where(['id' => $id])
            ->one($this->db);

        if (!$payload) {
            if ($this->deleteReleased) {
                return self::STATUS_DONE;
            } else {
                throw new InvalidParamException("Unknown messages ID: $id.");
            }
        }
        //reserved_at为null,表示仍处于等待中,这是任务刚刚加入到队列的情况，或者可以被Worker执行的状态
        if (!$payload['reserved_at']) {
            return self::STATUS_WAITING;
            //reserved_at不为空，但是done_at为null,是说它正被Worker从队列中取出，准备执行但尚未执行的中间状态
        } elseif (!$payload['done_at']) {
            return self::STATUS_RESERVED;
        } else {
            //其它就是任务执行完了
            return self::STATUS_DONE;
        }
    }

    /**
     * 该方法是从队列中取出任务，注意是取出，这里面有很大的学问哦
     * 注意reserved_at字段的状态变化
     * @return array|false payload
     * @throws Exception in case it hasn't waited the lock
     */
    protected function reserve()
    {
        // 首先应该获得锁才行（能获得锁，表示该队列现在仅仅由我来控制，而不是被其它进程控制，避免执行冲突或者重复执行这样的）
        if (!$this->mutex->acquire(__CLASS__ . $this->channel, $this->mutexTimeout)) {
            throw new Exception("Has not waited the lock.");
        }

        // Move reserved and not done messages into waiting list
        //这是干啥的，没有看明白（之前被Worker从队列中取出来，但是并没有执行的，重新把reserved_at置为null,可以在下一次被执行）
        //取出的最小间隔就是ttr,默认是300。也就是说，如果本次它被取出来尝试去执行（也许没有成功），那么下次被取出来肯定在五分钟之后！

        if ($this->reserveTime !== time()) {
            $this->reserveTime = time();
            $query = $this->db->createCommand()->update(
                $this->tableName,
                ['reserved_at' => null],
                '[[reserved_at]] < :time - [[ttr]] and [[done_at]] is null',
                [':time' => $this->reserveTime]
            );
            $row = $query->execute();
//            \Yii::error($this->messageHandler,'mesageHandler');
//            \Yii::error($query,'请求sql');
//            \Yii::error($row,'行数');
        }

        // Reserve one message
        //$payload 这个词要好好理解下，其实就是取得了一个有效的任务（满足条件的任务）
        $payload = (new Query())
            ->from($this->tableName)
            //频道就是在同一个表里，区别不同的队列，可以为每种任务对应一个频道，reserved_at=null为啥？
                //频道如何动态调整吗？待定
            ->andWhere(['channel' => $this->channel, 'reserved_at' => null])
            //入队列时间+延迟delay小于当前时间的，表示这个任务该执行了
            ->andWhere('[[pushed_at]] <= :time - delay', [':time' => time()])
            //优先级，在数据库里就是一个排序而已，值越小优先级越高
            ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
            ->limit(1)
            ->one($this->db);

        if (is_array($payload)) {
            \Yii::error($payload,'serverd方法 payload');
            //reserved_at置为当前时间，表示它已经被Worker从队列中取出，但是还没有被执行的中间状态
            $payload['reserved_at'] = time();
            //尝试次数加1
            $payload['attempt'] = (int)$payload['attempt'] + 1;
            $this->db->createCommand()->update($this->tableName, [
                'reserved_at' => $payload['reserved_at'], 'attempt' => $payload['attempt']],
                ['id' => $payload['id']]
            )->execute();
        }
        //释放刚刚获得的锁
        $this->mutex->release(__CLASS__ . $this->channel);

        // pgsql
        if (is_resource($payload['job'])) {
            \Yii::error($payload,'stream_get_contents');
            //任务用stream_get_contents解析，可以解析blob类型的数据？暂略
            $payload['job'] = stream_get_contents($payload['job']);
        }

        return $payload;
    }

    private $reserveTime;

    /**
     * 释放这个任务，就是把该任务从队列中移除
     * 可以是标记状态的软删除，也可以是硬删除
     * @param array $payload
     */
    protected function release($payload)
    {
        if ($this->deleteReleased) {
             //硬删除
            $this->db->createCommand()->delete(
                $this->tableName,
                ['id' => $payload['id']]
            )->execute();
        } else {
            // 软删除，标记done_at就行
            $this->db->createCommand()->update(
                $this->tableName,
                ['done_at' => time()],
                ['id' => $payload['id']]
            )->execute();
        }
    }
}