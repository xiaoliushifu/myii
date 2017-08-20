<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use ArrayIterator;
use yii\base\InvalidCallException;
use yii\base\Object;

/**CookieCollection维护了当前请求中可以获得的所有cookies（那response组件呢？）
 * CookieCollection maintains the cookies available in the current request.
 * 详情请查看官网guide
 * For more details and usage information on CookieCollection, see the [guide article on handling cookies](guide:runtime-sessions-cookies).
 *
 * $count 整型，表示集合中有多少cookie对象（yii\web\cookie),只读
 * @property int $count The number of cookies in the collection. This property is read-only.
 * $iterator 数组迭代器ArrayIterator，可以实现在cookies集合中遍历每个cookie    只读
 * @property ArrayIterator $iterator An iterator for traversing the cookies in the collection. This property
 * is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class CookieCollection extends Object implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * 集合类，是否是只读的，默认false
     * 在request组件使用cookie集合对象时，初始化cookie集合对象时会配置readOnly属性为true;意味着http请求中的cookie都是不可写的；
     * 在response组件中，初始化cookie集合对象时，没有配置属性，就是默认的false。意味着http响应中的cookie本身就是set-cookie的
     * @var bool whether this collection is read only.
     */
    public $readOnly = false;

    /**
     * 由每个cookie名字作为下标来存储所有的cookie，其实就是一个数组，每个数组元素是yii\web\cookie对象而已
     * @var Cookie[] the cookies in this collection (indexed by the cookie names)
     */
    private $_cookies = [];


    /**
     * Constructor.
     * 关联数组，应该是一个键值对，初始化cookie集合类时加入的cookie
     * @param array $cookies the cookies that this collection initially contains. This should be
     * an array of name-value pairs.
     * $config ,配置数组，用来初始化cookie集合对象的属性
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($cookies = [], $config = [])
    {
        $this->_cookies = $cookies;
        parent::__construct($config);
    }

    /**
     * 返回迭代器，用来遍历cookie集合里的每个cookie
     * Returns an iterator for traversing the cookies in the collection.
     * 这是cookie集合类实现PHP原生IteratorAggregate接口时，必须要实现的方法
     * This method is required by the SPL interface [[\IteratorAggregate]].
     * 当你通过foreach遍历cookie集合对象时，会隐式地调用这个方法
     * It will be implicitly called when you use `foreach` to traverse the collection.
     * @return ArrayIterator an iterator for traversing the cookies in the collection.
     */
    public function getIterator()
    {
        //转而又去使用PHP原生类，构造方法可选地可以传递一个数组或对象
        //参数类型不对的话，会报异常
        return new ArrayIterator($this->_cookies);
    }

    /**
     * 返回cookie集合对象里的cookie数量
     * Returns the number of cookies in the collection.
     * 这是cookie集合类实现PHP原生接口Countable时，必须要实现的方法
     * This method is required by the SPL `Countable` interface.
     * 当你count(集合对象）参数时，背后会隐式地调用这个方法
     * It will be implicitly called when you use `count($collection)`.
     * @return int the number of cookies in the collection.
     */
    public function count()
    {
        //转移到内部的另一个方法
        return $this->getCount();
    }

    /**
     * 我们看到，它还是转移到自己的_cookies属性中来。
     * Returns the number of cookies in the collection.
     * @return int the number of cookies in the collection.
     */
    public function getCount()
    {
        return count($this->_cookies);
    }

    /**
     * 通过cookie的名字，找到指定的cookie,只需你清楚cookie集合对象是通过属性_cookies保存cookie，且是个数组，下标是每个cookie名，值是
     * 对应的yii\web\cookie对象，那你自然明白下面的几个方法逻辑了。
     * Returns the cookie with the specified name.
     * @param string $name the cookie name
     * 没有就返回null
     * @return Cookie the cookie with the specified name. Null if the named cookie does not exist.
     * @see getValue()
     */
    public function get($name)
    {
        return isset($this->_cookies[$name]) ? $this->_cookies[$name] : null;
    }

    /**
     * 返回指定cookie的值，也就是value属性
     * Returns the value of the named cookie.
     * @param string $name the cookie name
     * 没有值的话，就返回第二个参数$defaultValue
     * @param mixed $defaultValue the value that should be returned when the named cookie does not exist.
     * @return mixed the value of the named cookie.
     * @see get()
     */
    public function getValue($name, $defaultValue = null)
    {
        return isset($this->_cookies[$name]) ? $this->_cookies[$name]->value : $defaultValue;
    }

    /**
     * 布尔判断，是否有指定的cookie存在于当前的cookie集合对象里
     * Returns whether there is a cookie with the specified name.
     * 注意，看源码实现，cookie存在，且值不为空，而且expire字段有效。才算这个cookie对象存在。
     * 如果一个cookie对象标记在浏览器端删除，则会返回false(这是针对response组件来说的吧）
     * Note that if a cookie is marked for deletion from browser, this method will return false.
     * @param string $name the cookie name
     * @return bool whether the named cookie exists
     * @see remove()
     */
    public function has($name)
    {
        return isset($this->_cookies[$name]) && $this->_cookies[$name]->value !== ''
            && ($this->_cookies[$name]->expire === null || $this->_cookies[$name]->expire >= time());
    }

    /**
     * 为cookie集合对象添加一个cookie对象
     * Adds a cookie to the collection.
     * 如果cookie同名的话会覆盖之前的
     * If there is already a cookie with the same name in the collection, it will be removed first.
     * @param Cookie $cookie the cookie to be added
     * 如果当前cookie集合对象是只读的，那么会报异常。
     * @throws InvalidCallException if the cookie collection is read only
     * 这应该是response组件里操作cookie才会调用的方法
     */
    public function add($cookie)
    {
        //在requrest组件中操作cookie集合时，估计是只读的吧
        if ($this->readOnly) {
            throw new InvalidCallException('The cookie collection is read only.');
        }
        $this->_cookies[$cookie->name] = $cookie;
    }

    /**
     * 删除一个cookie。
     * Removes a cookie. 
     * 如果$removeFromBrowser是true，则这个cookie会交给浏览器来删除
     * If `$removeFromBrowser` is true, the cookie will be removed from the browser.
     * 一般第二个参数是true,代表从浏览器端删除。那么浏览器端如何删除呢？就是设置过期的expire即可，
     * In this case, a cookie with outdated expiry will be added to the collection.
     * 比如本例把expire字段设置为1，为什么设置为1，浏览器就会删除？复习一下cookie的expire字段吧
     * 因为时间戳是1的时间，早就过时了
     * 
     * $cookie  cookie对象或者字符串
     * @param Cookie|string $cookie the cookie object or the name of the cookie to be removed.
     * @param bool $removeFromBrowser whether to remove the cookie from browser
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function remove($cookie, $removeFromBrowser = true)
    {
        if ($this->readOnly) {
            throw new InvalidCallException('The cookie collection is read only.');
        }
        //expire置为1，交由浏览器来删除，且直到发送给浏览器之前，也不会在读取出来了，看get方法就知道了
        if ($cookie instanceof Cookie) {
            $cookie->expire = 1;
            $cookie->value = '';
        } else {
            //字符串时创建一个cookie
            $cookie = new Cookie([
                'name' => $cookie,
                'expire' => 1,
            ]);
        }
        //交给浏览器删除这个cookie
        if ($removeFromBrowser) {
            //是以cookie对象的name为下标存储的
            $this->_cookies[$cookie->name] = $cookie;
        //直接从集合对象中删除
        } else {
            unset($this->_cookies[$cookie->name]);
        }
    }

    /**
     * 删除所有cookie，就是把cookie集合清空，
     * 什么场景下需要清空所有cookie呢？是request还是response呢？本次http响应中不会再向浏览器种植cookie
     * Removes all cookies.
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function removeAll()
    {
        if ($this->readOnly) {
            throw new InvalidCallException('The cookie collection is read only.');
        }
        $this->_cookies = [];
    }

    /**
     * 直接返回它的_cookies属性
     * Returns the collection as a PHP array.
     * @return array the array representation of the collection.
     * The array keys are cookie names, and the array values are the corresponding cookie objects.
     */
    public function toArray()
    {
        return $this->_cookies;
    }

    /**
     * 用一个数组填充_cookies属性
     * Populates the cookie collection from an array.
     * @param array $array the cookies to populate from
     * @since 2.0.3
     */
    public function fromArray(array $array)
    {
        $this->_cookies = $array;
    }

    /**
     * 
     * Returns whether there is a cookie with the specified name.
     * 下面是cookie集合类实现PHP原生接口ArrayAccess，必须要实现的四个方法
     * This method is required by the SPL interface [[\ArrayAccess]].
     * 当isset($collection[$name])时，会隐式调用这个方法
     * It is implicitly called when you use something like `isset($collection[$name])`.
     * @param string $name the cookie name
     * @return bool whether the named cookie exists
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Returns the cookie with the specified name.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * 当$cookie = $collection[$name];时，会隐式调用这个方法
     * It is implicitly called when you use something like `$cookie = $collection[$name];`.
     * This is equivalent to [[get()]].
     * @param string $name the cookie name
     * @return Cookie the cookie with the specified name, null if the named cookie does not exist.
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Adds the cookie to the collection.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * 当$collection[$name] = $cookie;时，会隐式调用这个方法
     * It is implicitly called when you use something like `$collection[$name] = $cookie;`.
     * This is equivalent to [[add()]].
     * @param string $name the cookie name
     * @param Cookie $cookie the cookie to be added
     */
    public function offsetSet($name, $cookie)
    {
        $this->add($cookie);
    }

    /**
     * Removes the named cookie.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * 当unset($collection[$name])时，会隐式调用这个方法
     * It is implicitly called when you use something like `unset($collection[$name])`.
     * This is equivalent to [[remove()]].
     * @param string $name the cookie name
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }
}
