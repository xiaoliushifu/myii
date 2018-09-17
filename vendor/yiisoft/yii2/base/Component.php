<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * 组件类是一个基类，它实现了属性，事件，行为三大特性！
 * Component is the base class that implements the *property*, *event* and *behavior* features.
 * 组件提供了事件和行为特性，以及继承自父类Object的属性特性
 * Component provides the *event* and *behavior* features, in addition to the *property* feature which is implemented in
 * its parent class [[\yii\base\Object|Object]].
 *事件是一种在已存在的代码里注入自定义代码的方式。比如用户添加一个评论时，由评论对象触发了添加评论事件
 * Event is a way to "inject" custom code into existing code at certain places. For example, a comment object can trigger我们可以把自定义的代码绑定到事件上，当事件触发时执行
 * an "add" event when the user adds a comment. We can write custom code and attach it to this event so that when the event
 * is triggered (i.e. comment will be added), our custom code will be executed.
 *事件是以事件名区分的，应用组件里的每个事件应该命名唯一，区分大小写
 * An event is identified by a name that should be unique within the class it is defined at. Event names are *case-sensitive*.
 *称为事件处理者的php可调用函数。可以绑定到事件上，应用组件调用trigger方法触发事件，当事件触发时，将会自动调用按顺序绑定的事件处理者
 * One or multiple PHP callbacks, called *event handlers*, can be attached to an event. You can call [[trigger()]] to
 * raise an event. When an event is raised, the event handlers will be invoked automatically in the order they were
 * attached.
 *如何绑定一个事件处理者到事件呢？
 * To attach an event handler to an event, call [[on()]]:
 *
 * ```php
 * $post->on('update', function ($event) {
 *     // send email notification
 * });
 * ```
 *上面是用匿名函数的方式绑定的，你可以使用匿名函数，对象方法，静态方法，全局函数四种事件处理者
 * In the above, an anonymous function is attached to the "update" event of the post. You may attach
 * the following types of event handlers:
 *
 * - anonymous function: `function ($event) { ... }`
 * - object method: `[$object, 'handleAdd']`
 * - static class method: `['Page', 'handleAdd']`
 * - global function: `'handleAdd'`
 *
 * 四种事件处理者的签名统一是这样的
 * The signature of an event handler should be like the following:
 *
 * ```php
 * function foo($event)
 * ```
 *
 * where `$event` is an [[Event]] object which includes parameters associated with the event.
 *
 * 在配置文件中如何给应用组件绑定事件呢？嘿嘿，看到__set()方法的实现就明白了
 * You can also attach a handler to an event when configuring a component with a configuration array.
 * The syntax is like the following:
 *
 * ```php
 * [
 *     'on add' => function ($event) { ... }
 * ]
 * ```
 *
 * where `on add` stands for attaching an event to the `add` event.
 * 如果绑定事件时，还需要第三个参数，那就只能是如下的方法绑定
 * Sometimes, you may want to associate extra data with an event handler when you attach it to an event
 * and then access it when the handler is invoked. You may do so by
 *
 * ```php
 * $post->on('update', function ($event) {
 *     // the data can be accessed via $event->data
 * }, $data);
 * ```
 *行为behavior是Behavior类或其子类的一个实例，一个应用组件可以绑定很多的行为类，当一个行为类绑定到应用组件后
 * 行为类的公共属性和方法可以由应用组件直接访问，就好像应用组件自己有这样的属性和方式似的
 * A behavior is an instance of [[Behavior]] or its child class. A component can be attached with one or multiple
 * behaviors. When a behavior is attached to a component, its public properties and methods can be accessed via the
 * component directly, as if the component owns those properties and methods.
 * 如何绑定一个行为类到应用组件呢？在应用组件的behaviors里声明就可以了
 * 或者直接调用应用组件的attachBehavior()方法
 * To attach a behavior to a component, declare it in [[behaviors()]], or explicitly call [[attachBehavior]]. Behaviors
 * declared in [[behaviors()]] are automatically attached to the corresponding component.
 *还有一种方法，就是在应用组件的配置数组里，使用as xyz的方式。
 * One can also attach a behavior to a component when configuring it with a configuration array. The syntax is like the
 * following:
 *
 * ```php
 * [
 *     'as tree' => [
 *         'class' => 'Tree',
 *     ],
 * ]
 * ```
 *
 * where `as tree` stands for attaching a behavior named `tree`, and the array will be passed to [[\Yii::createObject()]]
 * to create the behavior object.
 *
 * For more details and usage information on Component, see the [guide article on components](guide:concept-components).
 *
 * @property Behavior[] $behaviors List of behaviors attached to this component. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Component extends Object
{
    /**
     * @var array the attached event handlers (event name => handlers)
	 *私有属性，属于Component类的，注意Event类也有一个同名的属性
	 * 二维数组，以事件名为下标，事件处理者队列为值
     */
    private $_events = [];
    /**
     * @var Behavior[]|null the attached behaviors (behavior name => behavior). This is `null` when not initialized.
	 * 存储当前应用组件已经绑定的行为类，一般在Component子类中都有自己要绑定的行为类
     */
    private $_behaviors;


    /**
	 * 返回一个应用组件的属性 
     * Returns the value of a component property.
	 * 属性检查的顺序如下
     * This method will check in the following order and act accordingly:
     *	当前组件是否有getter方法
     *  - a property defined by a getter: return the getter result
	 *  所绑定的行为有没有该属性
     *  - a property of a behavior: return the behavior property value
     * 一看就知道，不要直接调用php的魔术方法
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $component->property;`.
     * @param string $name the property name
     * @return mixed the property value or the value of a behavior's property
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     * @see __set()
     */
    public function __get($name)
    {
		//优先判断getter方法
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        }
		//再来判断行为类
        // behavior property
		//ensurexxx方法很重要，我们之前一直见到过，这里终于要研究一下它了
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->canGetProperty($name)) {
                return $behavior->$name;
            }
        }
		//get不到，但是可以set到，说明该属性是只写的
        if (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        }

        throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    /**
     * Sets the value of a component property.
     * This method will check in the following order and act accordingly:
     * 有setter方法
     *  - a property defined by a setter: set the property value
	 * 格式以'on xyz'，绑定为一个事件。这个很少见
     *  - an event in the format of "on xyz": attach the handler to the event "xyz"
	 *  格式以'as xyz'，绑定为一个行为类
     *  - a behavior in the format of "as xyz": attach the behavior named as "xyz"
	 *  行为类的属性，直接设置即可
     *  - a property of a behavior: set the behavior property value
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$component->property = $value;`.
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is read-only.
     * @see __get()
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);

            return;
		//on xxx绑定事件的方式？嘿嘿少见呀
            //看例子,比如涉及接口应用时response组件的一个事件
            /**
             * response=>[
             *      'class'=>'yii\web\response',
             *      'on beforeSend' => function($event){
                        $response = $event->sender;
             *          $response->format = $response::FORMT_JSON;
             *          $response->statusCode = 200;
             *
             *      }
             * ]
             *
             */
        } elseif (strncmp($name, 'on ', 3) === 0) {
            // on event: attach event handler
            $this->on(trim(substr($name, 3)), $value);

            return;
		//as xxx直接绑定behavior
        } elseif (strncmp($name, 'as ', 3) === 0) {
            // as behavior: attach behavior
            $name = trim(substr($name, 3));
            $this->attachBehavior($name, $value instanceof Behavior ? $value : Yii::createObject($value));

            return;
        }

        // behavior property
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->canSetProperty($name)) {
                $behavior->$name = $value;
                return;
            }
        }

        if (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        }

        throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
    }

    /**
     * Checks if a property is set, i.e. defined and not null.
     * This method will check in the following order and act accordingly:
     * 是否有getter方法
     *  - a property defined by a setter: return whether the property is set
	 *  绑定的行为类中是否有可读属性
     *  - a property of a behavior: return whether the property is set
     *  - return `false` for non existing properties
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($component->property)`.
     * @param string $name the property name or the event name
     * @return bool whether the named property is set
     * @see http://php.net/manual/en/function.isset.php
     */
    public function __isset($name)
    {
		//本身就有这个属性的getter方法
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }
		
		//再去绑定的行为类里看看
        // behavior property
        $this->ensureBehaviors();
		//遍历每个绑定的行为类，看看它对这个属性的访问情况
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->canGetProperty($name)) {
                return $behavior->$name !== null;
            }
        }

        return false;
    }

    /**
	 * 置空应用组件的某个属性
     * Sets a component property to be null.
     * This method will check in the following order and act accordingly:
     * 使用有setter方法
     *  - a property defined by a setter: set the property value to be null
	 * 是绑定的行为类中的可写属性
     *  - a property of a behavior: set the property value to be null
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($component->property)`.
     * @param string $name the property name
     * @throws InvalidCallException if the property is read only.
     * @see http://php.net/manual/en/function.unset.php
     */
    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
            return;
        }

        // behavior property
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
			//行为类的这个属性可写
            if ($behavior->canSetProperty($name)) {
                $behavior->$name = null;
                return;
            }
        }

        throw new InvalidCallException('Unsetting an unknown or read-only property: ' . get_class($this) . '::' . $name);
    }

    /**
	 * 我们知道，在调用一个类没有的方法时，php会调用__call()方法
     * Calls the named method which is not a class method.
     * 这个方法将检测行为类中是否有$name指定的方法，有则执行之
     * This method will check if any attached behavior has
     * the named method and will execute it if available.
     *这是实现为组件扩展行为方法（不是行为属性）的关键
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     * @param string $name the method name
     * @param array $params method parameters
     * @return mixed the method return value
     * @throws UnknownMethodException when calling unknown method
     */
    public function __call($name, $params)
    {
        $this->ensureBehaviors();
		//遍历绑定的行为类，检查其是否有指定的方法
		//注意hasMethod是用的Object类的，而不是应用组件类的
		//这里再次表明，行为类属于Object的子类，应用组件Component也是Object的子类，
		//两者都是Object的子类，都是php的类。所以Yii框架的行为，其实是一种设计模式
		//一种实现某种逻辑的php代码，而不是trait等php本身支持的特性。
        foreach ($this->_behaviors as $object) {
            if ($object->hasMethod($name)) {
                return call_user_func_array([$object, $name], $params);
            }
        }
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    /**克隆一个新应用组件时，不包含旧身绑定的事件和行为类
     * This method is called after the object is created by cloning an existing one.
     * It removes all behaviors because they are attached to the old object.
     */
    public function __clone()
    {
        $this->_events = [];
        $this->_behaviors = null;
    }

    /**覆盖了父类Object::hasProperty()方法，检测应用组件是否有某个属性，有三种情况
     * Returns a value indicating whether a property is defined for this component.
     * A property is defined if:
     * 对象有这个属性的getter或setter方法
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
	 * 本身就有这个属性
     * - the class has a member variable with the specified name (when `$checkVars` is true);
	 * 绑定的行为类有这个可访问的属性
     * - an attached behavior has a property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param bool $checkVars whether to treat member variables as properties
     * @param bool $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return bool whether the property is defined
     * @see canGetProperty()
     * @see canSetProperty()
     */
    public function hasProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return $this->canGetProperty($name, $checkVars, $checkBehaviors) || $this->canSetProperty($name, false, $checkBehaviors);
    }

    /**
    /**覆盖了父类Object::canGetProperty()方法,检测应用组件的某个属性是否可读
     * Returns a value indicating whether a property can be read.
     * A property can be read if:
     * 对象有这个属性的getter方法
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
	 * 对象本身就有这个属性成员
     * - the class has a member variable with the specified name (when `$checkVars` is true);
	 * 绑定的行为类里是否有这个属性是可读的
     * - an attached behavior has a readable property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param bool $checkVars whether to treat member variables as properties
     * @param bool $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return bool whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
				//这里通过行为类继承自Object的方法，判断是否有可访问的$name属性
                if ($behavior->canGetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**覆盖了父类Object::canSetProperty()方法,检测应用组件的某个属性是否可写
     * Returns a value indicating whether a property can be set.
     * A property can be written if:
     * 对象有这个属性的setter方法
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
	 * 对象本身就有这个属性成员
     * - the class has a member variable with the specified name (when `$checkVars` is true);
	 * 绑定的行为里是否有这个属性是可写的
     * - an attached behavior has a writable property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param bool $checkVars whether to treat member variables as properties
     * @param bool $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return bool whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
	 * 检测一个对象是否含有某个方法，嘿嘿，注意这个和其父类Object::hasMethod()的区别
     * Returns a value indicating whether a method is defined.
     * A method is defined if:
     *
     * - the class has a method with the specified name
	 * 检查绑定的行为类里是否有这个方法
     * - an attached behavior has a method with the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param bool $checkBehaviors whether to treat behaviors' methods as methods of this component
     * @return bool whether the method is defined
     */
    public function hasMethod($name, $checkBehaviors = true)
    {
		//本身就有（这是Object类的源码）
        if (method_exists($this, $name)) {
            return true;
		//倘若本身没有，还去检测已经绑定的行为类里是否有这个方法
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
			//遍历绑定的行为类，看哪个行为类有这个方法
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->hasMethod($name)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
	 * behaviors方法，熟悉不？没错，之前在控制器的方法里有这个behaviors方法，它不是通常的actionIndex,actionList
	 * 这个方法特殊，这就是为当前控制器这个应用组件绑定行为的方法。写在控制器里，只是绑定行为类的一种方式而已。
	 * 因为控制器也是应用组件
     * Returns a list of behaviors that this component should behave as.
     * 需要使用行为类的应用组件们，都实现这个方法就是了
     * Child classes may override this method to specify the behaviors they want to behave as.
     * 这个方法应该返回一个数组，这个数组应该是行为类对象，或者是行为类对象的描述。可以是多个行为类
	 * 下面告诉我们返回的行为数组的格式
     * The return value of this method should be an array of behavior objects or configurations
     * indexed by behavior names. A behavior configuration can be either a string specifying
     * the behavior class or an array of the following structure:
     *
     * ```php
     * 'behaviorName' => [
     *     'class' => 'BehaviorClass',
     *     'property1' => 'value1',
     *     'property2' => 'value2',
     * ]
     * ```
     * 行为类必须是Behavior的子类，在其他地方定义，而应用组件通过behaviors方法把其他地方定义的行为引入而已
     * Note that a behavior class must extend from [[Behavior]]. Behaviors can be attached using a name or anonymously.
     * When a name is used as the array key, using this name, the behavior can later be retrieved using [[getBehavior()]]
     * or be detached using [[detachBehavior()]]. Anonymous behaviors can not be retrieved or detached.
     *
     * Behaviors declared in this method will be attached to the component automatically (on demand).
     *
     * @return array the behavior configurations.
     */
    public function behaviors()
    {
        return [];
    }

    /**
	*  判断一个事件是否已经绑定了事件处理者
     * Returns a value indicating whether there is any handler attached to the named event.
     * @param string $name the event name 事件名
     * @return bool whether there is any handler attached to the event.
     */
    public function hasEventHandlers($name)
    {
        $this->ensureBehaviors();
		//既判断Components的事件，还判断类级别的事件
        return !empty($this->_events[$name]) || Event::hasHandlers($this, $name);
    }

    /**
     * Attaches an event handler to an event.
     * 把一个事件处理者绑定到一个事件上
     * The event handler must be a valid PHP callback. The following are
	 * 事件处理者必须是一个PHP 函数（方法）
     * some examples:
     *
     * ```
     * function ($event) { ... }         // anonymous function   匿名函数
     * [$object, 'handleClick']          // $object->handleClick()  对象方法
     * ['Page', 'handleClick']           // Page::handleClick()		静态方法
     * 'handleClick'                     // global function handleClick()	普通全局函数
     * ```
     *
     * The event handler must be defined with the following signature,
     * 这些函数不是随便定义的，必须符合如下的签名
     * ```
     * function ($event)
     * ```
     * $event是一个对象，它有一些关联当前事件的属性，方便事件处理者获取该事件的一些信息进行处理
     * where `$event` is an [[Event]] object which includes parameters associated with the event.
     *
     * @param string $name the event name  事件名，自己随便定义
     * @param callable $handler the event handler  事件处理者
     * @param mixed $data the data to be passed to the event handler when the event is triggered.
	 * 事件处理者参数，在事件处理者中通过Event::data访问
     * When the event handler is invoked, this data can be accessed via [[Event::data]].
	 * booleam类型，默认是true,即一个事件可以绑定多个事件处理者，多个事件处理者按照绑定顺序依次触发处理
	 * 如果该参数设置是false,那么新的事件处理者就会绑定到事件处理者队列的最前面。
     * @param bool $append whether to append new event handler to the end of the existing
     * handler list. If false, the new handler will be inserted at the beginning of the existing
     * handler list.
     * @see off()
     */
    public function on($name, $handler, $data = null, $append = true)
    {
		//这行代码是干啥的，好多地方有，暂且略过
        $this->ensureBehaviors();
		
		//第四个参数是true(默认值）；或者这个事件还没有事件处理者，都把事件名为下标，保存成二维数组。
        if ($append || empty($this->_events[$name])) {
            $this->_events[$name][] = [$handler, $data];
        } else {
		//第四个参数是false,并且$name事件的处理者队列不空，那么就把当前的事件处理者放到队列的前头。
		//使用php原生函数array_unshift即可。
            array_unshift($this->_events[$name], [$handler, $data]);
        }
    }

    /**
     * Detaches an existing event handler from this component.
	 * 解绑一个已经绑定的事件处理者
     * This method is the opposite of [[on()]].
	 * 这个方法是on方法的相反操作
     * @param string $name event name  事件名
     * @param callable $handler the event handler to be removed.  要删除的事件处理者
     * If it is null, all handlers attached to the named event will be removed.
     * @return bool if a handler is found and detached 有这个事件处理者并删除它就是true.
     * @see on()
     */
    public function off($name, $handler = null)
    {
		//还是那一段代码，稍后再研究
        $this->ensureBehaviors();
		//事件队列里，根本没这个事件
        if (empty($this->_events[$name])) {
            return false;
        }
		//事件处理者参数为空，则把该事件下的所有事件处理者都干掉
        if ($handler === null) {
            unset($this->_events[$name]);
            return true;
        }
		//来个标记
        $removed = false;
		//开始遍历，熟悉事件处理者的结构，0是事件处理者本身，1是事件处理者的data参数
        foreach ($this->_events[$name] as $i => $event) {
            if ($event[0] === $handler) {
                unset($this->_events[$name][$i]);
                $removed = true;
            }
        }
        if ($removed) {
			//重新排列事件处理者队列的顺序，我觉得完全可以写在foreach中
            $this->_events[$name] = array_values($this->_events[$name]);
        }
        return $removed;
    }

    /**
	* 触发一个事件
     * Triggers an event.
	 * 该方法代表一个事件的发生
     * This method represents the happening of an event. It invokes
	 * 将会调用所有曾经绑定的事件处理者，包含类级别的事件处理者（第一维是事件名）
     * all attached handlers for the event including class-level handlers.
     * @param string $name the event name 事件名
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created. 事件对象
     */
    public function trigger($name, Event $event = null)
    {
		//这个ensureBehaviors()的作用非常明显，举例来说，Controller::EVENT_BEFORE_ACTION事件，将被触发的最后一刻
		//完成事件处理者的绑定，因为在这之前，Yii框架没有明显的Controller::EVENT_BEFORE_ACTION事件的on绑定，
		//类似这样的有好多(触发事件之前根本就没有绑定handler），当时我还纳闷呢：触发之前至少得绑定一个事件处理者，
		//这样在触发时才会调用事件处理者，否则都是空的事件处理者,岂不白白浪费了。
		//而下面的ensureBehaviors恰恰就是在最后关头，完成行为类的绑定，进而完成事件的绑定。
		//可以说立即绑定，然后就立即触发执行了。
		//阅读过滤器行为类ActionFilter的实现机制有感
        $this->ensureBehaviors();
		//这个事件有事件处理者绑定者
        if (!empty($this->_events[$name])) {
			//没有事件对象，就自动生成一个，这个事件对象是不可缺少的
            if ($event === null) {
                $event = new Event;
            }
			//把当前调用trigger的对象放到事件对象的sender属性里
            if ($event->sender === null) {
                $event->sender = $this;
            }
            $event->handled = false;
			//事件对象的name属性，就是事件名
            $event->name = $name;
			//遍历开始
            foreach ($this->_events[$name] as $handler) {
                $event->data = $handler[1];
				//调用每个事件处理者的时候，都会传递这个共享的事件对象
                call_user_func($handler[0], $event);
                // stop further handling if the event is handled
				//什么叫事件处理完了？有两种情况：1：只事件对象的handled属性为true就认为事件处理完了，虽然可能有其他的事件处理者还没来得及被调用
				//2 该事件绑定的所有事件处理者都被调用了，遍历结束了
                if ($event->handled) {
                    return;
                }
            }
        }
        // invoke class-level attached handlers
		//最后，才有机会调用类级别的事件处理者。注意，类是哪个类，就是$this
        Event::trigger($this, $name, $event);
    }

    /**返回$name指定的行为类
     * Returns the named behavior object.
     * @param string $name the behavior name
     * @return null|Behavior the behavior object, or null if the behavior does not exist
     */
    public function getBehavior($name)
    {
        $this->ensureBehaviors();
        return isset($this->_behaviors[$name]) ? $this->_behaviors[$name] : null;
    }

    /**返回所有绑定的行为类，本质上就是那个_behaviors数组
     * Returns all behaviors attached to this component.
     * @return Behavior[] list of behaviors attached to this component
     */
    public function getBehaviors()
    {
        $this->ensureBehaviors();
        return $this->_behaviors;
    }

    /**绑定$name指定的行为类到应用组件上
     * Attaches a behavior to this component.
	 * 以$name为下标绑定行为类，$behavior可以是配置数组或者行为对象
     * This method will create the behavior object based on the given
     * configuration. After that, the behavior object will be attached to
	 * 还是通过行为对象的attach方法绑定的
     * this component by calling the [[Behavior::attach()]] method.
     * @param string $name the name of the behavior.
     * @param string|array|Behavior $behavior the behavior configuration. This can be one of the following:
     *
     *  - a [[Behavior]] object  行为对象
     *  - a string specifying the behavior class 行为类名的字符串
     *  - an object configuration array that will be passed to [[Yii::createObject()]] to create the behavior object.行为对象的配置数组
     *
     * @return Behavior the behavior object
     * @see detachBehavior()
     */
    public function attachBehavior($name, $behavior)
    {
		//确保所有的行为类绑定
        $this->ensureBehaviors();
		//再把本次额外指定的绑定
        return $this->attachBehaviorInternal($name, $behavior);
    }

    /**绑定一系列的行为类到当前组件上
     * Attaches a list of behaviors to the component.
	 * 每个行为类都已它的类名为下标，且是Behavior的子类存到_behaviors数组里
     * Each behavior is indexed by its name and should be a [[Behavior]] object,
	 * 每个行为类可以是类名或者是一个配置数组
     * a string specifying the behavior class, or an configuration array for creating the behavior.
     * @param array $behaviors list of behaviors to be attached to the component
     * @see attachBehavior()
     */
    public function attachBehaviors($behaviors)
    {
		//确保所有的行为类都绑定了
        $this->ensureBehaviors();
		//再遍历本次额外添加的行为类，一个个添加进来
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehaviorInternal($name, $behavior);
        }
    }

    /**
	 * 解绑$name指定的一个行为类
     * Detaches a behavior from the component.
	 * 是调用具体行为类的detach方法
     * The behavior's [[Behavior::detach()]] method will be invoked.
     * @param string $name the behavior's name.
     * @return null|Behavior the detached behavior. Null if the behavior does not exist.
     */
    public function detachBehavior($name)
    {
		//还是先确保所有的行为类绑定
        $this->ensureBehaviors();
		//无需遍历，直接用isset判断是否在_behavior中
        if (isset($this->_behaviors[$name])) {
            $behavior = $this->_behaviors[$name];
            unset($this->_behaviors[$name]);
            $behavior->detach();
            return $behavior;
        }

        return null;
    }

    /**解绑应用组件的所有行为类
     * Detaches all behaviors from the component.
     */
    public function detachBehaviors()
    {
		//先确保绑定了所有的行为类,这里有点奇怪，为啥不直接遍历_befaviors数组呢，而是先绑定再解绑呢，多次一举没有？
        $this->ensureBehaviors();
		//再遍历行为类，依次解绑它们
        foreach ($this->_behaviors as $name => $behavior) {
            $this->detachBehavior($name);
        }
    }

    /**
	 * 这个方法出现的频率非常高，几乎前面的方法里都出现了。它何许人也？
	 * 用来确保在应用组件中behaviors()方法里声明的behavior类都绑定到应用组件上
	 * 注意这个方法是写在父类Component上的，所以一般是每个应用组件子类里实现这个behavior方法，
	 * 然后在父类的ensureBehaviors方法里完成绑定
     * Makes sure that the behaviors declared in [[behaviors()]] are attached to this component.
     */
    public function ensureBehaviors()
    {
        //第一次进来时_behavios是null,
        if ($this->_behaviors === null) {
            $this->_behaviors = [];
			//调用应用组件的behaviors方法获得组件数组，遍历之绑定到当前的应用组件上
            foreach ($this->behaviors() as $name => $behavior) {
                $this->attachBehaviorInternal($name, $behavior);
            }
        }
    }

    /**
	 * 绑定一个行为到当前组件上，注意这是私有方法，Yii框架对私有方法的命名一般都有xxxxxInternal这样的
     * Attaches a behavior to this component.
	 * $name是行为类名，如果是整数，则表示行为类不是类，而是匿名函数
     * @param string|int $name the name of the behavior. If this is an integer, it means the behavior
	 * 行为类有名称，则方便后续的解绑，而且是首先解绑
     * is an anonymous one. Otherwise, the behavior is a named one and any existing behavior with the same name
     * will be detached first.
	 * $behavior要被绑定的行为类
     * @param string|array|Behavior $behavior the behavior to be attached
     * @return Behavior the attached behavior.
	 * 注意，绑定的操作attach与解绑操作detach都是行为类的方法，
	 * 最终把行为类，装填入组件的_befaviors数组里，这就是绑定
     */
    private function attachBehaviorInternal($name, $behavior)
    {
		//不是实例的，就先去实例化
        if (!($behavior instanceof Behavior)) {
            $behavior = Yii::createObject($behavior);
        }
		//整数，直接绑定
        if (is_int($name)) {
            $behavior->attach($this);
			//互为引用，行为放到了组件的数组_behaviors；组件放到了行为的owner属性中。
            $this->_behaviors[] = $behavior;
        } else {
			//重名的行为类，先解绑旧的，然后再绑新的，最终其实就是覆盖
            if (isset($this->_behaviors[$name])) {
                $this->_behaviors[$name]->detach();
            }
            $behavior->attach($this);
            $this->_behaviors[$name] = $behavior;
        }

        return $behavior;
    }
}
