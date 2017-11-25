<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
* 对象类是实现 *属性*这一特性的基类
 * Object is the base class that implements the *property* feature.
 *
 一个属性的定义，就是通过getter方法和setter方法定义的。看下面例子如何定义了Label属性
 * A property is defined by a getter method (e.g. `getLabel`), and/or a setter method (e.g. `setLabel`). For example,
 * the following getter and setter methods define a property named `label`:
 *
 * ```php
 * private $_label;
 *
 * public function getLabel()
 * {
 *     return $this->_label;
 * }
 *
 * public function setLabel($value)
 * {
 *     $this->_label = $value;
 * }
 * ```
 *属性名不区分大小写，这是因为php对函数名不区分大小写，所以getXXX，setXXX也就不区分大小写。
 * Property names are *case-insensitive*.
 *属性的方法，就像是对象的成员一样，读取是通过实现getter和setter方法完成的
 * A property can be accessed like a member variable of an object. Reading or writing a property will cause the invocation
 * of the corresponding getter or setter method. For example,
 *
 * ```php
 * // equivalent to $label = $object->getLabel();
 * $label = $object->label;
 * // equivalent to $object->setLabel('abc');
 * $object->label = 'abc';
 * ```
 *如果一个属性只有getter方法没有setter方法，那么就说该属性是只读的；此时尝试更改属性将会抛出异常。
 * If a property has only a getter method and has no setter method, it is considered as *read-only*. In this case, trying
 * to modify the property value will cause an exception.
 *如何判断是否有该属性，是否可以get，是否可以set呢？分别提供了三个方法来完成。
 * One can call [[hasProperty()]], [[canGetProperty()]] and/or [[canSetProperty()]] to check the existence of a property.
 *除了属性特性之外，Object类也引入了重要的【对象初始化生命周期】，也就是说，创建一个对象或其派生类对象将会包含如下过程：
 * Besides the property feature, Object also introduces an important object initialization life cycle. In particular,
 * creating an new instance of Object or its derived class will involve the following life cycles sequentially:
 *
 * 1. the class constructor is invoked;调用类构造方法
 * 2. object properties are initialized according to the given configuration;根据配置信息配置其成员属性
 * 3. the `init()` method is invoked.调用init方法
 *上述中，步骤2,3发生在构造方法的末尾。建议把对象初始化内容写在init方法里，因为此时的对象已经配置好
 * In the above, both Step 2 and 3 occur at the end of the class constructor. It is recommended that
 * you perform object initialization in the `init()` method because at that stage, the object configuration
 * is already applied.这里要说一点的是，我经常把第二步作为初始化，也就是给新对象的成员赋值为初始化。
 *为了确保上述生命周期，子类如果有自己的构造函数，请一定要包含parent::__construct()呗。
 * In order to ensure the above life cycles, if a child class of Object needs to override the constructor,
 * it should be done like the following:
 *
 * ```php
 * public function __construct($param1, $param2, ..., $config = [])
 * {
 *     ...
 *     parent::__construct($config);
 * }
 * ```
 *也就是说，构造函数的参数列表的最后一位，要留给$config（默认是[]),这是因为父类构造函数的参数列表的结构
 * That is, a `$config` parameter (defaults to `[]`) should be declared as the last parameter
 * of the constructor, and the parent implementation should be called at the end of the constructor.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Object implements Configurable
{
    /**
     * Returns the fully qualified name of this class.
     * @return string the fully qualified name of this class.
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * Constructor.
     * The default implementation does two things:
     *
     * - Initializes the object with the given configuration `$config`.
     * - Call [[init()]].
     *
     * If this method is overridden in a child class, it is recommended that
     *
     * - the last parameter of the constructor is a configuration array, like `$config` here.
     * - call the parent implementation at the end of the constructor.
     *所有的对象都继承了yii/base/Object,所以反射(解析）某个对象时，还有自动加载时，都会执行这个构造函数，
     * @param array $config name-value pairs that will be used to initialize the object properties
	 对象实例化的【生命周期】都在父类的这个构造函数里完成了
     */
    public function __construct($config = [])//1构造函数
    {
        if (!empty($config)) {//2 使用$config配置信息
            Yii::configure($this, $config);
        }
        $this->init();//3 调用init方法
    }

    /**
	* Object是基类，故它的init方法只是象征性的，真正实现留给子类完成
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
    }

    /**所谓实现写一个getXXX方法，就能定义一个成员属性的逻辑，就是利用了php的__get魔术方法机制实现的。
     * Returns the value of an object property.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $object->property;`.
     * @param string $name the property name
     * @return mixed the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only
     * @see __set()
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
	* 同上，定义一个可写的属性，就是利用php的__set魔术方法机制
     * Sets value of an object property.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$object->property = $value;`.
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is read-only
     * @see __get()
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Checks if a property is set, i.e. defined and not null.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($object->property)`.
     *
     * Note that if the property is not defined, false will be returned.
     * @param string $name the property name or the event name
     * @return bool whether the named property is set (not null).
     * @see http://php.net/manual/en/function.isset.php
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    /**
     * Sets an object property to null.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($object->property)`.
     *
     * Note that if the property is not defined, this method will do nothing.
     * If the property is read-only, it will throw an exception.
     * @param string $name the property name
     * @throws InvalidCallException if the property is read only.
     * @see http://php.net/manual/en/function.unset.php
     */
    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Calls the named method which is not a class method.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     * @param string $name the method name
     * @param array $params method parameters
     * @throws UnknownMethodException when calling unknown method
     * @return mixed the method return value
     */
    public function __call($name, $params)
    {
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    /**
	 * 检测某个对象的属性是否已定义
     * Returns a value indicating whether a property is defined.
     * A property is defined if:
     * 对象有这个属性的getter或者setter方法
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
	 * 这个对象本身就有这个属性
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param bool $checkVars whether to treat member variables as properties
     * @return bool whether the property is defined
     * @see canGetProperty()
     * @see canSetProperty()
     */
    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

    /**检测某个对象的属性是否可读，可读的情况有两种：
     * Returns a value indicating whether a property can be read.
     * A property is readable if:
     * 这个对象有getter方法，这时的属性不区分大小写（这是php函数名决定的）
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
	 * 对象本身就有这个属性
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param bool $checkVars whether to treat member variables as properties
     * @return bool whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
	 * 检测一个对象的属性是否可写，有两种情况：
     * Returns a value indicating whether a property can be set.
     * A property is writable if:
     * 当对象有这个属性的setter方法时
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
	 * 对象本身就有这个属性（不关心它的修饰符是public,protected或者private）
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param bool $checkVars whether to treat member variables as properties
     * @return bool whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
	 *检测对象是否有某个方法
     * Returns a value indicating whether a method is defined.
     * 子类可以覆盖这个方法
     * The default implementation is a call to php function `method_exists()`.
     * You may override this method when you implemented the php magic method `__call()`.
     * @param string $name the method name
     * @return bool whether the method is defined
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
}
