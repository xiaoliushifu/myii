<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Object;
use ArrayIterator;

/**
 * 头部集合类，是Response组件用来保存当前要发送给客户端的http响应头部的。
 * HeaderCollection is used by [[Response]] to maintain the currently registered HTTP headers.
 *
 * $count 集合中头部字段的数量，只读
 * @property int $count The number of headers in the collection. This property is read-only.
 * ArrayIterator $iterator 是一个用来遍历集合里头部字段的迭代器，只读
 * @property ArrayIterator $iterator An iterator for traversing the headers in the collection. This property
 * is read-only.
 *
 * 头部集合类继承自Object类，实现了php的三个接口【IteratorAggregate】，【ArrayAccess】，【Countable】
 *实现这三个接口应该怎么做？记得上回学习session还是cookie来着，见到过。
 * 每个接口都有一些固定的方法，实现这个接口，就要实现这个接口带来的方法。有的带来一个，有的接口带来多个方法。
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HeaderCollection extends Object implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
	 * 私有成员属性，是一个用http响应的头部字段名为下标的数组
     * @var array the headers in this collection (indexed by the header names)
     */
    private $_headers = [];


    /**
	 * 为遍历头部集合而返回的迭代器
     * Returns an iterator for traversing the headers in the collection.
	 * 需要PHP的SPL接口之一[[\IteratorAggregate]].
     * This method is required by the SPL interface [[\IteratorAggregate]].
	 * 该方法不能直接调用，而是在遍历头部集合时（比如foreach)由php暗中调用。
     * It will be implicitly called when you use `foreach` to traverse the collection.
     * @return ArrayIterator an iterator for traversing the headers in the collection.
     */
    public function getIterator()
    {
		//注意，迭代器的构造函数需要一个数组来初始化
        return new ArrayIterator($this->_headers);
    }

    /**
	 * 返回头部集合里头部字段的数量
     * Returns the number of headers in the collection.
 	 * 需要PHP的SPL接口之一`Countable`。
     * This method is required by the SPL `Countable` interface.
	 * 当对头部集合类使用count()函数时，php将暗中调用该方法。跟getIterator()一样不能主动调用
	 * 疑问？是不能主动调用，还是建议不要主动调用？过吧，会不会我的思路又要分叉了？看到吗，这就是我的学习习惯。先跳过吧。
     * It will be implicitly called when you use `count($collection)`.
     * @return int the number of headers in the collection.
     */
    public function count()
    {
        return $this->getCount();
    }

    /**返回头部集合对象的私有成员，_headers数组的元素数量
     * Returns the number of headers in the collection.
     * @return int the number of headers in the collection.
     */
    public function getCount()
    {
        return count($this->_headers);
    }

    /**
	 * 返回指定的头部字段
     * Returns the named header(s).
	 * $name  就是指定的头部字段名
     * @param string $name the name of the header to return
	 * $default  当该头部字段不在_headers数组中时，返回这个默认值
     * @param mixed $default the value to return in case the named header does not exist
	 * $first 是否只返回指定的第一个header,（有点疑问，既指定名字还是第一个？，不忙，接着往下看）
     * @param bool $first whether to only return the first header of the specified name.
	 * 如果是false,则指定name的所有header将返回
     * If false, all headers of the specified name will be returned.
	 * 返回数组或者字符串，$first为true,则返回一个字符串；$first为false,则返回一个数组。
	 * 看来，需要了解_headers的组成，才能明白上述所说的【第一个，指定名字】各是什么意思了
     * @return string|array the named header(s). If `$first` is true, a string will be returned;
     * If `$first` is false, an array will be returned.
     */
    public function get($name, $default = null, $first = true)
    {
        $name = strtolower($name);
        if (isset($this->_headers[$name])) {
			//看来，_headers是一个二维数组，所以有两种可能：
			//返回指定下标的整个二维数组，
			//或者，返回指定下标的二维数组的第一个元素
            return $first ? reset($this->_headers[$name]) : $this->_headers[$name];
        } else {
            return $default;
        }
    }

    /**
	 * 设置一个新的头部字段到_headers私有成员数组中
     * Adds a new header.
	 * 如果之前已经存在该头部字段，那么将是覆盖式得添加
     * If there is already a header with the same name, it will be replaced.
     * @param string $name the name of the header
     * @param string $value the value of the header
     * @return $this the collection object itself
	 * set，我个人倾向翻译为【设置】，故虽注释中有add，也是看方法的具体实现来翻译。
     */
    public function set($name, $value = '')
    {
        $name = strtolower($name);
        $this->_headers[$name] = (array) $value;

        return $this;
    }

    /**
	 * 添加一个新成员
     * Adds a new header.
	 * 注意，这个与上个方法set()不同。add()是为指定的头部字段内部追加一个数组元素（若有重复成员也不会覆盖之）
     * If there is already a header with the same name, the new one will
     * be appended to it instead of replacing it.
     * @param string $name the name of the header
     * @param string $value the value of the header
     * @return $this the collection object itself
     */
    public function add($name, $value)
    {
        $name = strtolower($name);
		//看到没，第二维的数组是个索引数组
        $this->_headers[$name][] = $value;

        return $this;
    }

    /**
	 * 当不存在指定的头部字段时，添加它到指定的头部字段里
     * Sets a new header only if it does not exist yet.
     * If there is already a header with the same name, the new one will be ignored.
     * @param string $name the name of the header
     * @param string $value the value of the header
     * @return $this the collection object itself
     */
    public function setDefault($name, $value)
    {
        $name = strtolower($name);
		//指定的二维数组为空时，则添加一个数组元素
        if (empty($this->_headers[$name])) {
            $this->_headers[$name][] = $value;
        }

        return $this;
    }

    /**
	 * 是否有指定的头部字段存在于头部字段集合里。
     * Returns a value indicating whether the named header exists.
     * @param string $name the name of the header
     * @return bool whether the named header exists
     */
    public function has($name)
    {
        $name = strtolower($name);
		//有没有指定的二维数组存在
        return isset($this->_headers[$name]);
    }

    /**
	 * 删除指定的头部字段，然后再返回这个已经删除的字段的值
     * Removes a header.
     * @param string $name the name of the header to be removed.
     * @return array the value of the removed header. Null is returned if the header does not exist.
     */
    public function remove($name)
    {
        $name = strtolower($name);
        if (isset($this->_headers[$name])) {
            $value = $this->_headers[$name];
            unset($this->_headers[$name]);
            return $value;
        } else {
            return null;
        }
    }

    /**
	 *  全部清空所有的头部字段 
     * Removes all headers.
     */
    public function removeAll()
    {
        $this->_headers = [];
    }

    /**
	 * 返回头部字段数组
     * Returns the collection as a PHP array.
	 * 疑问，这个方法难道也是php的哪个接口要实现的？
     * @return array the array representation of the collection.
     * The array keys are header names, and the array values are the corresponding header values.
     */
    public function toArray()
    {
        return $this->_headers;
    }

    /**从一个指定的数组来填充头部字段（_headers成员）
     * Populates the header collection from an array.
     * @param array $array the headers to populate from
     * @since 2.0.3
	 * Populates 本意是定居。曾经翻译为填充。感觉填充和添加意思差不多，都是为已有的再多加一个。个人感觉应该翻译为“白手起家，从零开始“
	 * 但是目前也没想到，就仍然沿用“填充”吧。
     */
    public function fromArray(array $array)
    {
        $this->_headers = $array;
    }

    /**
	 * 是否存在指定的头部字段，
     * Returns whether there is a header with the specified name.
	 * 这是实现PHP的SPL接口ArrayAccess必须要实现的方法，本质还是使用has()方法完成的。
     * This method is required by the SPL interface [[\ArrayAccess]].
	 * 当在程序中isset($collection[$name])时，php将暗中调用这个方法
     * It is implicitly called when you use something like `isset($collection[$name])`.
     * @param string $name the header name
     * @return bool whether the named header exists
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
	* 返回指定的头部字段，同上是接口方法
     * Returns the header with the specified name.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$header = $collection[$name];`.
     * This is equivalent to [[get()]].
     * @param string $name the header name
     * @return string the header value with the specified name, null if the named header does not exist.
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
	 * 设置头部字段的值，同上是接口方法
     * Adds the header to the collection.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$collection[$name] = $header;`.
     * This is equivalent to [[add()]].
     * @param string $name the header name
     * @param string $value the header value to be added
     */
    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    /**
	 * 删除指定的头部字段，不是删除头部字段的值，同上是接口方法
     * Removes the named header.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `unset($collection[$name])`.
     * This is equivalent to [[remove()]].
     * @param string $name the header name
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }
}
