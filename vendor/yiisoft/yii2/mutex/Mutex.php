<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mutex;

use Yii;
use yii\base\Component;

/**
* Mutex 互斥（体），互斥组件，需要互斥地执行并发进程，以防止竞争资源
 * The Mutex component allows mutual execution of concurrent processes in order to prevent "race conditions".
 * 这是通过使用“锁”机制实现的，每个可能并发的线程通过获得锁来操作相关的数据
 * This is achieved by using a "lock" mechanism. Each possibly concurrent thread cooperates by acquiring
 * a lock before accessing the corresponding data.
 *
 * Usage example:
 *
 * ```
 * if ($mutex->acquire($mutexName)) {
 *     // business logic execution
 * } else {
 *     // execution is blocked!
 * }
 * ```
 *
 * This is a base class, which should be extended in order to implement the actual lock mechanism.
 *
 * @author resurtm <resurtm@gmail.com>
 * @since 2.0
 */
abstract class Mutex extends Component
{
    /**
	 * 在脚本结束时，是否允许自动释放当前php进程所获得的互斥锁，默认是true，意味释放。
     * @var bool whether all locks acquired in this process (i.e. local locks) must be released automatically
     * before finishing script execution. Defaults to true. Setting this property to true means that all locks
     * acquired in this process must be released (regardless of errors or exceptions).
     */
    public $autoRelease = true;

    /**
	* 被当前php进程获得的锁的名字，可以是多个。
     * @var string[] names of the locks acquired by the current PHP process.
     */
    private $_locks = [];


    /**
     * Initializes the Mutex component.
     */
    public function init()
    {
        if ($this->autoRelease) {
            $locks = &$this->_locks;
			//所谓脚本结束自动释放互斥锁，在php就是利用shutdown函数完成的。
			//聪明不？会写不？多好啊。
            register_shutdown_function(function () use (&$locks) {
                foreach ($locks as $lock) {
                    $this->release($lock);
                }
            });
        }
    }

    /**
	 * 根据锁名尝试获取锁
     * Acquires a lock by name.
	 *锁名必须是唯一的
     * @param string $name of the lock to be acquired. Must be unique.
     * @param int $timeout time to wait for lock to be released. Defaults to zero meaning that method will return
     * false immediately in case lock was already acquired.
     * @return bool lock acquiring result.
     */
    public function acquire($name, $timeout = 0)
    {
        if ($this->acquireLock($name, $timeout)) {
            $this->_locks[] = $name;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Releases acquired lock. This method will return false in case the lock was not found.
     * @param string $name of the lock to be released. This lock must already exist.
     * @return bool lock release result: false in case named lock was not found..
     */
    public function release($name)
    {
        if ($this->releaseLock($name)) {
			//由指定的key获得数组值的php函数，为啥不直接$this->_locks[$name]呢？
            $index = array_search($name, $this->_locks);
            if ($index !== false) {
                unset($this->_locks[$index]);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
	* 该方法实际完成获得互斥锁的功能，应该被子类实现
     * This method should be extended by a concrete Mutex implementations. Acquires lock by name.
     * @param string $name of the lock to be acquired.
     * @param int $timeout time to wait for the lock to be released.
     * @return bool acquiring result.
     */
    abstract protected function acquireLock($name, $timeout = 0);

    /**
	* 该方法实际完成释放互斥锁的功能，应该被子类实现
     * This method should be extended by a concrete Mutex implementations. Releases lock by given name.
     * @param string $name of the lock to be released.
     * @return bool release result.
     */
    abstract protected function releaseLock($name);
}
