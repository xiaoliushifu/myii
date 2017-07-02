<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Yii;
use Closure;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
* 服务定位器类实现了wiki上所说的服务定位器模式
 * ServiceLocator implements a [service locator](http://en.wikipedia.org/wiki/Service_locator_pattern).
 * 使用服务定位器，就是首先通过set方法注册组件id和其相关联的组件定义。
 * To use ServiceLocator, you first need to register component IDs with the corresponding component
 * definitions with the locator by calling [[set()]] or [[setComponents()]].
 * 使用的时候，通过get方法，传入组件ID来获得这个组件，组件对象此时已经自动地实例化并配置好了。
 * You can then call [[get()]] to retrieve a component with the specified ID. The locator will automatically
 * instantiate and configure the component according to the definition.
 *
 * For example,
 *
 * ```php
 * $locator = new \yii\di\ServiceLocator;
 * //组件注册的过程
 * $locator->setComponents([
 *     'db' => [
 *         'class' => 'yii\db\Connection',
 *         'dsn' => 'sqlite:path/to/file.db',
 *     ],
 *     'cache' => [
 *         'class' => 'yii\caching\DbCache',
 *         'db' => 'db',
 *     ],
 * ]);
 * //获取组件的过程
 * $db = $locator->get('db');  // or $locator->db
 * $cache = $locator->get('cache');  // or $locator->cache
 * ```
 * Module继承ServiceLocator,application也继承了它。所以应用都是服务定位器
 * Because [[\yii\base\Module]] extends from ServiceLocator, modules and the application are all service locators.
 *
 * 官网也给了一套解释
 * For more details and usage information on ServiceLocator, see the [guide article on service locators](guide:concept-service-locator).
 *
 * @property array $components The list of the component definitions or the loaded component instances (ID =>
 * definition or instance).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ServiceLocator extends Component
{
    /** 
	 * 该属性存储了已经注册的组件对象实例，注意是实例
     * @var array shared component instances indexed by their IDs
     */
    private $_components = [];
    /**该属性存储了已经注册的组件，有可能已经实例，有可能是一个组件定义，也有可能是一个回调函数（必须返回实例）
     * @var array component definitions indexed by their IDs
     */
    private $_definitions = [];


    /** 
	 *  利用PHP魔术方法实现了如何获取服务的逻辑
     * Getter magic method.
	 * 众所周知，__get()是在访问对象不存在的属性时调用执行的。
	 * 在yii中应用该魔术方法，这使得组件就像是应用的属性一样来访问了
     * This method is overridden to support accessing components like reading properties.
     * @param string $name component or property name
     * @return mixed the named property value
     */
    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->get($name);
        } else {
            return parent::__get($name);
        }
    }

    /**检测某个属性是否为null时调用
	* 这也是PHP魔术方法之一。在对不可访问属性进行isset()或者empty时会自动调用这个方法
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named component is loaded.
     * @param string $name the property name or the event name
     * @return bool whether the property value is null
     */
    public function __isset($name)
    {
        if ($this->has($name)) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    /**
	 * 判断一个组件是否已经注册了
     * Returns a value indicating whether the locator has the specified component definition or has instantiated the component.
	 * 如果第二个参数是true,还要检测这个组件在存在的前提下，是否是一个已经实例化了的组件对象，而不是一个组件定义。
     * This method may return different results depending on the value of `$checkInstance`.
     *默认是false,仅仅检测是否已经注册过了而已。
     * - If `$checkInstance` is false (default), the method will return a value indicating whether the locator has the specified
     *   component definition.
     * - If `$checkInstance` is true, the method will return a value indicating whether the locator has
     *   instantiated the specified component.
     *
     * @param string $id component ID (e.g. `db`).
     * @param bool $checkInstance whether the method should check if the component is shared and instantiated.
     * @return bool whether the locator has the specified component definition or has instantiated the component.
     * @see set()
     */
    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    /**
	 * 根据组件的ID来获取组件实例，也就是获取服务
     * Returns the component instance with the specified ID.
     *
     * @param string $id component ID (e.g. `db`).
	 * 第二个参数默认是true,也就是如果找不到组件的话，会返回一个异常
	 * 如果是false的话，就不返回异常，而是返回一个null
     * @param bool $throwException whether to throw an exception if `$id` is not registered with the locator before.
     * @return object|null the component of the specified ID. If `$throwException` is false and `$id`
     * is not registered before, null will be returned.
     * @throws InvalidConfigException if `$id` refers to a nonexistent component ID
     * @see has()
     * @see set()
     */
    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            } else {
                return $this->_components[$id] = Yii::createObject($definition);
            }
        } elseif ($throwException) {
            throw new InvalidConfigException("Unknown component ID: $id");
        } else {
            return null;
        }
    }

    /** 
	* 注册一个组件到服务定位器中
     * Registers a component definition with this locator.
     * 可以有四种形式注册组件
     * For example,
     *
     * ```php
     * // a class name
	 * 注册一个cache组件，类名形式
     * $locator->set('cache', 'yii\caching\FileCache');
     *
     * // a configuration array
	 * 注册一个组件，定义数组形式（必须含有class)
     * $locator->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // an anonymous function
	 * 注册一个组件，回调函数形式
     * $locator->set('cache', function ($params) {
     *     return new \yii\caching\FileCache;
     * });
     *
     * // an instance
	 * 注册一个组件，直接就是组件对象
     * $locator->set('cache', new \yii\caching\FileCache);
     * ```
     * 注册的组件都是单例，或者重名的会覆盖前者
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * @param string $id component ID (e.g. `db`).
     * @param mixed $definition the component definition to be registered with this locator.
     * It can be one of the following:
     * 第二个参数有四种形式（四种情况)

	 *   类名
     * - a class name
	 *   与类定义有关的数组，必须有个class下标
     * - a configuration array: the array contains name-value pairs that will be used to
     *   initialize the property values of the newly created object when [[get()]] is called.
     *   The `class` element is required and stands for the the class of the object to be created.
	 *   PHP回调函数
     * - a PHP callable: either an anonymous function or an array representing a class method (e.g. `['Foo', 'bar']`).
     *   The callable will be called by [[get()]] to return an object associated with the specified component ID.
	 *   组件对象，最直接
     * - an object: When [[get()]] is called, this object will be returned.
     *
     * @throws InvalidConfigException if the definition is an invalid configuration array
     */
    public function set($id, $definition)
    {
        if ($definition === null) {
            unset($this->_components[$id], $this->_definitions[$id]);
            return;
        }

        unset($this->_components[$id]);

		/*这段代码非常重要，这里能够解释为什么yii组件的配置有很多都是以数组形式来配置的了*/
		/*而且重要的是，把每个组件的配置信息都保存到_definitions属性里，这有什么意义呢？
		嘿嘿，不要忘记了，它还有一个_components属性。这俩有啥区别呢？
		不错，_components存储对象实例，_definitions存储对象的定义。
		在合适的时机，通过get方法来进行反射解析_definitions里的组件，然后保存到_components里
		明白了不？可以认为_components就是一个单例大工厂呀。
		*/
        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new InvalidConfigException("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new InvalidConfigException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

    /**清除组件（从_definitions和_omponents里都清除了）
     * Removes the component from the locator.
     * @param string $id the component ID
     */
    public function clear($id)
    {
        unset($this->_definitions[$id], $this->_components[$id]);
    }

    /**获得组件，或者叫返回注册的服务，代码就这两行，我就不写了
     * Returns the list of the component definitions or the loaded component instances.
     * @param bool $returnDefinitions whether to return component definitions instead of the loaded component instances.
     * @return array the list of the component definitions or the loaded component instances (ID => definition or instance).
     */
    public function getComponents($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_components;
    }

    /**
	* 通过foreach循环来注册Conponents数组里的组件
     * Registers a set of component definitions in this locator.
     *这其实是set方法的bulk版。啥是bulk?正好复习一下英语
	 * 简单理解来说，Component是以下标为组件id，值为组件定义信息的数组
     * This is the bulk version of [[set()]]. The parameter should be an array
     * whose keys are component IDs and values the corresponding component definitions.
     * 最终还是交给set方法来注册每个组件
     * For more details on how to specify component IDs and definitions, please refer to [[set()]].
     * 已经注册过的组件会被后来的覆盖掉
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * The following is an example for registering two component definitions:
     *
     * ```php
     * [
     *     'db' => [
     *         'class' => 'yii\db\Connection',
     *         'dsn' => 'sqlite:path/to/file.db',
     *     ],
     *     'cache' => [
     *         'class' => 'yii\caching\DbCache',
     *         'db' => 'db',
     *     ],
     * ]
     * ```
     *
     * @param array $components component definitions or instances
     */
    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }
}
