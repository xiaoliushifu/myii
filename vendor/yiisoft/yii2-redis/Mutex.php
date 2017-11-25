<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\redis;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
* 既然在redis这个扩展下，能想到的都有了，比如redis做缓存就是redisCache。
那么这里就是用redis实现的互斥锁Mutex
 * Redis Mutex implements a mutex component using [redis](http://redis.io/) as the storage medium.
 *Redis互斥锁，需要redis的版本是2.6.12或更高
 * Redis Mutex requires redis version 2.6.12 or higher to work properly.
 *即可以直接使用redis连接，也可以使用redis组件。反正最终互斥锁和redis服务器打交道都是使用连接Connection
 * It needs to be configured with a redis [[Connection]] that is also configured as an application component.
 默认使用名为redis的组件
 * By default it will use the `redis` application component.
 *
 * 使用redisMutex，是以组件形式配置的。如何配置，如下请看：
 * To use redis Mutex as the application component, configure the application as follows:
 *
 * ~~~
 * [
 *     'components' => [
 *         'mutex' => [
 *             'class' => 'yii\redis\Mutex',
				//配置redis连接信息
 *             'redis' => [
 *                 'hostname' => 'localhost',
 *                 'port' => 6379,
 *                 'database' => 0,
 *             ]
 *         ],
 *     ],
 * ]
 * ~~~
 *如果使用redis组件作为驱动，那么只需给出组件名就行了
 * Or if you have configured the redis [[Connection]] as an application component, the following is sufficient:
 *
 * ~~~
 * [
 *     'components' => [
 *         'mutex' => [
 *             'class' => 'yii\redis\Mutex',
 *             // 'redis' => 'redis' // redis就是组件的ID
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * @see \yii\mutex\Mutex
 * @see http://redis.io/topics/distlock
 *
 * @author Sergey Makinen <sergey@makinen.ru>
 * @author Alexander Zhuravlev <axelhex@gmail.com>
 * @since 2.0.6
 */
class Mutex extends \yii\mutex\Mutex
{
    /**
	* 自动释放锁的超时时间，默认30秒
     * @var int the number of seconds in which the lock will be auto released.
     */
    public $expire = 30;
    /**  redis的key前缀
     * @var string a string prefixed to every cache key so that it is unique. If not set,
     * it will use a prefix generated from [[Application::id]]. You may set this property to be an empty string
     * if you don't want to use key prefix. It is recommended that you explicitly set this property to some
     * static value if the cached data needs to be shared among multiple applications.
     */
    public $keyPrefix;
    /**
	 * redis配置，默认的redis就是指名为redis的组件ID。也可以配置为连接数组，或连接对象
     * @var Connection|string|array the Redis [[Connection]] object or the application component ID of the Redis [[Connection]].
     * This can also be an array that is used to create a redis [[Connection]] instance in case you do not want do configure
     * redis connection as an application component.
     * After the Mutex object is created, if you want to change this property, you should only assign it
     * with a Redis [[Connection]] object.
     */
    public $redis = 'redis';

    /**
     * @var array Redis lock values. Used to be safe that only a lock owner can release it.
     */
    private $_lockValues = [];


    /**
     * Initializes the redis Mutex component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     * @throws InvalidConfigException if [[redis]] is invalid.
     */
    public function init()
    {
        parent::init();
		//这一步，首先实例化redis驱动对象
        $this->redis = Instance::ensure($this->redis, Connection::className());
        if ($this->keyPrefix === null) {
            $this->keyPrefix = substr(md5(Yii::$app->id), 0, 5);
        }
    }

    /**
	* 做为互斥锁，继承父类必须实现的方法。是获得互斥锁的方法
     * Acquires a lock by name.
     * @param string $name of the lock to be acquired. Must be unique.
     * @param int $timeout time to wait for lock to be released. Defaults to `0` meaning that method will return
     * false immediately in case lock was already acquired.
     * @return bool lock acquiring result.
     */
    protected function acquireLock($name, $timeout = 0)
    {
        $key = $this->calculateKey($name);//redis的特殊性，key总得封装计算一下
		//它的值倒不重要，只要每次设置时不重复即可。故使用了安全组件security。
		//这里对security组件尚不清楚。
        $value = Yii::$app->security->generateRandomString(20);
        $waitTime = 0;
		//虽然while循环，但只会执行一次。因为timeout默认是0，$waitTime++后大于0永远成立，故就return false了
		//所以redisMutex获取锁的机会只一次，获得不了就退出。
		//一旦获得了互斥锁，也就是执行redis的set命令成功。那就会设置redis值的生存周期是30秒。30秒后该值生命周期结束
		//在这30秒内，值在redis服务器端保留，其他进程获得不了互斥锁
		//第一个参数是$key，第二个是$value
		//第三个从官网看NX是独立设置，也就是说当$key不存在时才会设置$key。但是参数顺序不对，
		//PX应该是毫秒级的超时，还有一个EX是秒级的超时。有点想不通。后来接着看官网set命令页下方的模式，
		//https://redis.io/commands/set。这才看到了有说明。才知道参数不分顺序。
		//NX写到3,4,5位哪里都行。PX后面永远写毫秒，EX后写秒就行了
        while (!$this->redis->executeCommand('SET', [$key, $value, 'NX', 'PX', (int) ($this->expire * 1000)])) {
            $waitTime++;
            if ($waitTime > $timeout) {
                return false;
            }
            sleep(1);
        }
		//保留锁名和值
        $this->_lockValues[$name] = $value;
        return true;
    }

    /**看到LUA脚本，有些不懂暂略过了，其实用redis释放锁的逻辑该如何写，redis官网已经给出了LUA脚本的写法。
	就在官网的set命令页的下方https://redis.io/commands/set。大家看看就知道了
     * Releases acquired lock. This method will return `false` in case the lock was not found or Redis command failed.
     * @param string $name of the lock to be released. This lock must already exist.
     * @return bool lock release result: `false` in case named lock was not found or Redis command failed.
     */
    protected function releaseLock($name)
    {
		//PHP大字符串格式<<<XXX
        static $releaseLuaScript = <<<LUA
if redis.call("GET",KEYS[1])==ARGV[1] then
    return redis.call("DEL",KEYS[1])
else
    return 0
end
LUA;
        if (!isset($this->_lockValues[$name]) || !$this->redis->executeCommand('EVAL', [
                $releaseLuaScript,
                1,
                $this->calculateKey($name),
                $this->_lockValues[$name]
            ])) {
            return false;
        } else {
            unset($this->_lockValues[$name]);
            return true;
        }
    }

    /**
	* 封装key。就是先json编码，然后md5散列，最后加上前缀即可
     * Generates a unique key used for storing the mutex in Redis.
     * @param string $name mutex name.
     * @return string a safe cache key associated with the mutex name.
     */
    protected function calculateKey($name)
    {
        return $this->keyPrefix . md5(json_encode([__CLASS__, $name]));
    }
}
