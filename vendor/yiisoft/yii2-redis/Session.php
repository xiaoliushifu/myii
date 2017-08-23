<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\redis;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Redis Session，顾名思义，是使用redis存储引擎代替默认的File存储引擎实现了Session。
 * Redis Session implements a session component using [redis](http://redis.io/) as the storage medium.
 * 需要redis 2.6.12版本或更高
 * Redis Session requires redis version 2.6.12 or higher to work properly.
 * 
  使用redis来实现session该如何配置呢？
  redis可以配置为一个连接数组，也可以单独把redis配置为一个应用组件
 * It needs to be configured with a redis [[Connection]] that is also configured as an application component.
 * 默认使用redis这个应用组件
 * By default it will use the `redis` application component.
 * 要使用redis Session作为应用组件，那就按照如下的配置：
 * To use redis Session as the session application component, configure the application as follows,
 *
 * ~~~
 * [
 *     'components' => [
 *         'session' => [
 *             'class' => 'yii\redis\Session',//class一定要配置，且配置到当前redis Session
				//单独给个redis的属性，配置redis的连接信息
 *             'redis' => [
 *                 'hostname' => 'localhost',
 *                 'port' => 6379,
 *                 'database' => 0,
 *             ]
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * 或者，如果你已经单独把redis配置为一个应用组件了，那就按照如下的样式来配置：
 * Or if you have configured the redis [[Connection]] as an application component, the following is sufficient:
 *
 * ~~~
 * [
 *     'components' => [
 *         'session' => [
 *             'class' => 'yii\redis\Session',
				//redis下标不是上述的配置连接数组，而是配置一个字符串，字符串的值就是redis的应用组件名。
				//默认会找组件名为‘redis'的。
 *             // 'redis' => 'redis' // id of the connection application component
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * $useCustomStorage 属性，是配置自定义存储引擎的。只读。
 * @property bool $useCustomStorage Whether to use custom storage. This property is read-only.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 * redis\Session,也是session，将来用其他方式实现的，比如mysql实现的redis，也得继承\yii\web\Session。
 * 这里还没有看出来redis是如何从Session中读取数据，和设置的数据的，因为仅仅一个executeCommand是看不出来的，
 * 还需要阅读Connection类才能了解。
 */
class Session extends \yii\web\Session
{
    /**
	 *  连接对象，字符串，数组。三者类型都行表示一个redis连接。
     * @var Connection|string|array the Redis [[Connection]] object or the application component ID of the Redis [[Connection]].
	 *数组，就可以去实例化redis 连接对象
     * This can also be an array that is used to create a redis [[Connection]] instance in case you do not want do configure
     * redis connection as an application component.
     * After the Session object is created, if you want to change this property, you should only assign it
     * with a Redis [[Connection]] object.
     */
    public $redis = 'redis';
    /**
	 * 一个字符串前缀。如果不设置的话，则从Application::id生成前缀。如果不想有前缀，就把$keyPrefix设置为''即可
     * @var string a string prefixed to every cache key so that it is unique. If not set,
     * it will use a prefix generated from [[Application::id]]. You may set this property to be an empty string
     * if you don't want to use key prefix. It is recommended that you explicitly set this property to some
	 * 如果你的redis服务器在多个系统之间共享时，可以使用redis前缀。
     * static value if the cached data needs to be shared among multiple applications.
     */
    public $keyPrefix;


    /**
     * Initializes the redis Session component.
	 * 主要根据$redis属性是（连接对象，字符串，数组）这三种情况来分别处理，得到最终的redis连接。
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     * @throws InvalidConfigException if [[redis]] is invalid.
     */
    public function init()
    {
		//使用locator模式，get到这个redis组件
        if (is_string($this->redis)) {
            $this->redis = Yii::$app->get($this->redis);
		//是个数组的话，就去直接实例化
        } elseif (is_array($this->redis)) {
			//如果数组里没有class下标，那就用Connection类名
            if (!isset($this->redis['class'])) {
				
                $this->redis['class'] = Connection::className();
            }
            $this->redis = Yii::createObject($this->redis);
        }
        if (!$this->redis instanceof Connection) {
            throw new InvalidConfigException("Session::redis must be either a Redis connection instance or the application component ID of a Redis connection.");
        }
		//没有默认前缀，就用当前应用的id处理后作为keyPrefix
        if ($this->keyPrefix === null) {
            $this->keyPrefix = substr(md5(Yii::$app->id), 0, 5);
        }
        parent::init();
    }

    /**
	 * 父类的这个方法是false,代表把存储引擎配置交给php.ini；
	 * 这里redis Session既然继承了Session，自然要使用redis来保存session数据。
	 * 所以肯定会覆盖父类的方法，并实现那六个方法。
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return bool whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     * Session read handler.
     * Do not call this method directly.
	 	 不能直接调用，而是由系统自动调用的
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
		//读取Sessin数据，必须实现的方法。我们看到其中就是redis的一个get命令而已
        $data = $this->redis->executeCommand('GET', [$this->calculateKey($id)]);

        return $data === false || $data === null ? '' : $data;
    }

    /**
     * Session write handler.
     * Do not call this method directly.
	 不能直接调用，而是由系统自动调用的
     * @param string $id session ID
     * @param string $data session data
     * @return bool whether session write is successful
     */
    public function writeSession($id, $data)
    {
        return (bool) $this->redis->executeCommand('SET', [$this->calculateKey($id), $data, 'EX', $this->getTimeout()]);
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
	 	 不能直接调用，而是由系统自动调用的
     * @param string $id session ID
     * @return bool whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        $this->redis->executeCommand('DEL', [$this->calculateKey($id)]);
        // @see https://github.com/yiisoft/yii2-redis/issues/82
        return true;
    }

    /**
	 * 对key的处理
     * Generates a unique key used for storing session data in cache.
	 * 先json_encode得到一个json格式的字符串，然后再用md5散射，然后加个前缀
     * @param string $id session variable name
     * @return string a safe cache key associated with the session variable name
     */
    protected function calculateKey($id)
    {
        return $this->keyPrefix . md5(json_encode([__CLASS__, $id]));
    }
}
