<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\redis;

use Yii;
use yii\di\Instance;

/** 
 * redisCache实现缓存组件，缓存驱动是基于redis的键值存储
 * Redis Cache implements a cache application component based on [redis](http://redis.io/) key-value store.
 *目前需要redis的版本至少是2.6.12
 * Redis Cache requires redis version 2.6.12 or higher to work properly.
 *这个缓存组件，需要配置一个redis连接，也可以单独配置个redis的组件。默认redisCache使用名为redis的缓存组件
 * It needs to be configured with a redis [[Connection]] that is also configured as an application component.
 * By default it will use the `redis` application component.
 *
 * See [[Cache]] manual for common cache operations that redis Cache supports.
 *
 * redisCache的过期时间，可以是浮点数，可以精确到毫秒级
 * Unlike the [[Cache]], redis Cache allows the expire parameter of [[set]], [[add]], [[mset]] and [[madd]] to
 * be a floating point number, so you may specify the time in milliseconds (e.g. 0.1 will be 100 milliseconds).
 
 *  redis缓存，也是缓存的一种，故仍属于Cache组件，区别只是class下标不同而已。而且多了个redis下标，用来给出redis服务器的
 连接配置。
 * To use redis Cache as the cache application component, configure the application as follows,
 *
 * ~~~
 * [
 *     'components' => [
 *         'cache' => [//缓存组件
 *             'class' => 'yii\redis\Cache',//redis缓存驱动
 *             'redis' => [//redis服务器配置信息
 *                 'hostname' => 'localhost',
 *                 'port' => 6379,
 *                 'database' => 0,
 *             ]
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * Or if you have configured the redis [[Connection]] as an application component, the following is sufficient:
 *
 * ~~~
 * [
 *     'components' => [
 *         'cache' => [
 *             'class' => 'yii\redis\Cache',
 *             // 'redis' => 'redis' //不配置连接信息的话，也可以指定一个名为redis的组件
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Cache extends \yii\caching\Cache
{
    /**
	 * 这是操作的关键，既然缓存驱动是redis,当然得有个与redis服务器的连接才行，就像和数据库的操作，得保持一个数据库的连接一样。 redis属性支持两种类别：1连接实例，2可以建立连接的配置信息。
	 而配置信息又分为两种：1数组形式的配置信息；2字符串直接给出redis组件的名字（缓存组件和redis组件的配合）
     * @var Connection|string|array the Redis [[Connection]] object or the application component ID of the Redis [[Connection]].
     * This can also be an array that is used to create a redis [[Connection]] instance in case you do not want do configure
     * redis connection as an application component.
     * After the Cache object is created, if you want to change this property, you should only assign it
     * with a Redis [[Connection]] object.
     */
    public $redis = 'redis';


    /**
     * Initializes the redis Cache component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     * @throws \yii\base\InvalidConfigException if [[redis]] is invalid.
     */
    public function init()
    {
        parent::init();
		//初始化的重要一步，就是首先实例化redis连接资源（尚未与redis服务器连接）
        $this->redis = Instance::ensure($this->redis, Connection::className());
    }

	/**
	下面就开始了redisCache作为Cache必须实现的那五个方法:
	- [[getValue()]]: retrieve the value with a key (if any) from cache
	- [[setValue()]]: store the value with a key into cache
	- [[addValue()]]: store the value only if the cache does not have this key before
	- [[deleteValue()]]: delete the value with the specified key from cache
	- [[flushValues()]]: delete all values from cache
	**/
    /**
	* exists视各自的缓存驱动不同，有的直接使用父类Cache的，有的需要单独写，故不再那五个方法里。
     * Checks whether a specified key exists in the cache.
	 * 检测某个key是否存在，有两个逻辑：
	 1用这个key去缓存中取得数据，如果真取得了，那就说明有这个key存在；这属于间接判断。
	 2直接有一种方法检测是否有key存在。直接判断。某些驱动是支持的，比如redsi支持exists命令。
	 而其他的缓存驱动，比如DbCache,FileCache都是第一种方式。
     * This can be faster than getting the value from the cache if the data is big.
     * Note that this method does not check whether the dependency associated
     * with the cached data, if there is any, has changed. So a call to [[get]]
     * may return false while exists returns true.
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @return bool true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function exists($key)
    {
        return (bool) $this->redis->executeCommand('EXISTS', [$this->buildKey($key)]);
    }

    /**
     * @inheritdoc
     */
    protected function getValue($key)
    {
        return $this->redis->executeCommand('GET', [$key]);
    }

    /**
     * @inheritdoc
     */
    protected function getValues($keys)
    {
        $response = $this->redis->executeCommand('MGET', $keys);
        $result = [];
        $i = 0;
        foreach ($keys as $key) {
            $result[$key] = $response[$i++];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $expire)
    {
        if ($expire == 0) {
            return (bool) $this->redis->executeCommand('SET', [$key, $value]);
        } else {
			//参数转化成毫秒级
            $expire = (int) ($expire * 1000);

            return (bool) $this->redis->executeCommand('SET', [$key, $value, 'PX', $expire]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function setValues($data, $expire)
    {
        $args = [];
        foreach ($data as $key => $value) {
            $args[] = $key;
            $args[] = $value;
        }

        $failedKeys = [];
        if ($expire == 0) {
            $this->redis->executeCommand('MSET', $args);
        } else {
            $expire = (int) ($expire * 1000);
            $this->redis->executeCommand('MULTI');
            $this->redis->executeCommand('MSET', $args);
            $index = [];
            foreach ($data as $key => $value) {
                $this->redis->executeCommand('PEXPIRE', [$key, $expire]);
                $index[] = $key;
            }
            $result = $this->redis->executeCommand('EXEC');
            array_shift($result);
            foreach ($result as $i => $r) {
                if ($r != 1) {
                    $failedKeys[] = $index[$i];
                }
            }
        }

        return $failedKeys;
    }

    /**
     * @inheritdoc
     */
    protected function addValue($key, $value, $expire)
    {
        if ($expire == 0) {
			//set和add的区别，在redis里就是多了第三个参数 NX
            return (bool) $this->redis->executeCommand('SET', [$key, $value, 'NX']);
        } else {
            $expire = (int) ($expire * 1000);

            return (bool) $this->redis->executeCommand('SET', [$key, $value, 'PX', $expire, 'NX']);
        }
    }

    /**
     * @inheritdoc
     */
    protected function deleteValue($key)
    {
        return (bool) $this->redis->executeCommand('DEL', [$key]);
    }

    /**
     * @inheritdoc
     */
    protected function flushValues()
    {
		//flushdb是redis的命令，可以清空当前数据库
        return $this->redis->executeCommand('FLUSHDB');
    }
}
