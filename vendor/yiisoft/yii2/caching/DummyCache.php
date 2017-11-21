<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

/**
* DummyCache是缓存组件的一个填充器（待填充）（不太明白，没读懂）
 * DummyCache is a placeholder cache component.
 *DummyCache不缓存任何数据项，它被提供是为了满足开发人员仅仅配置一个叫做Cache的应用组件即可
 * DummyCache does not cache anything. It is provided so that one can always configure
 它只保存一个对cache对象的检测结果。不错，保存一个检测结果而已。
 * a 'cache' application component and save the check of existence of `\Yii::$app->cache`.
 开发人员可以通过用其他缓存组件替换DummyCache的方法，来随意在无缓存和有缓存两个模式下切换。
 有意思吧？在不改变原有代码的基础上，切换DummyCache，仍然可以走通涉及cache操作的代码，而并没有
 什么逻辑执行（get获取永远false，set永远返回true而实际啥也没有存入缓存）
 这就是DummyCache的真谛。而使用场景应该是临时切换或者临时测试使用吧。？
 * By replacing DummyCache with some other cache component, one can quickly switch from
 * non-caching mode to caching mode.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DummyCache extends Cache
{
    /**
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return mixed|false the value stored in cache, false if the value is not in the cache or expired.
     */
    protected function getValue($key)
    {
        return false;
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param mixed $value the value to be cached
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $duration)
    {
        return true;
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key identifying the value to be cached
     * @param mixed $value the value to be cached
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $duration)
    {
        return true;
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return bool if no error happens during deletion
     */
    protected function deleteValue($key)
    {
        return true;
    }

    /**
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     * @return bool whether the flush operation was successful.
     */
    protected function flushValues()
    {
        return true;
    }
}
