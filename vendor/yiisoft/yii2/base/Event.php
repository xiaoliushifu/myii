<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Event is the base class for all event classes.
 *
 * It encapsulates the parameters associated with an event.
 * The [[sender]] property describes who raises the event.
 * And the [[handled]] property indicates if the event is handled.
 * If an event handler sets [[handled]] to be `true`, the rest of the
 * uninvoked handlers will no longer be called to handle the event.
 *
 * Additionally, when attaching an event handler, extra data may be passed
 * and be available via the [[data]] property when the event handler is invoked.
 *
 * For more details and usage information on Event, see the [guide article on events](guide:concept-events).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Event extends Object
{
    /**
     * @var string the event name. This property is set by [[Component::trigger()]] and [[trigger()]].
     * Event handlers may use this property to check what event it is handling.
     */
    public $name;
    /**
     * @var object the sender of this event. If not set, this property will be
     * set as the object whose `trigger()` method is called.
     * This property may also be a `null` when this event is a
     * class-level event which is triggered in a static context.
     */
    public $sender;
    /**
     * @var bool whether the event is handled. Defaults to `false`.
     * When a handler sets this to be `true`, the event processing will stop and
     * ignore the rest of the uninvoked event handlers.
     */
    public $handled = false;
    /**
     * @var mixed the data that is passed to [[Component::on()]] when attaching an event handler.
     * Note that this varies according to which event handler is currently executing.
     */
    public $data;

    /**
     * @var array contains all globally registered event handlers.
	 * 私有静态变量
	 *  这里放置全局注册的或者全局绑定的事件处理者，注意与Component.php里的$_events不同。
	 * 这个属性是三维数组，[$name][$class][]=[$handler,$data]这样的
     */
    private static $_events = [];


    /**
     * Attaches an event handler to a class-level event.
     * 绑定一个事件处理者到一个事件上，一个类级别的事件。
	 * 当类级别的事件触发后，所有这些类及父类的事件处理者都会被调用
     * When a class-level event is triggered, event handlers attached
     * to that class and all parent classes will be invoked.
     * 比如，如下代码绑定绑定事件处理者到ActiveRecord类的ActiveRecord::EVENT_AFTER_INSERT事件
     * For example, the following code attaches an event handler to `ActiveRecord`'s
     * `afterInsert` event:
     *
     * ```php
     * Event::on(ActiveRecord::className(), ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
     *     Yii::trace(get_class($event->sender) . ' is inserted.');
     * });
     * ```
     * 上述事件处理者将在AR对象成功插入一条记录的时候调用
     * The handler will be invoked for EVERY successful ActiveRecord insertion.
     * 如何声明事件处理者，请参考Component的on方法
     * For more details about how to declare an event handler, please refer to [[Component::on()]].
     * 全命名空间的类名，类级别事件中涉及的那个类
     * @param string $class the fully qualified class name to which the event handler needs to attach.
	 * 事件名，一般是类常量
     * @param string $name the event name.
	 * 事件处理者，一般是php函数或方法
     * @param callable $handler the event handler.
	 * 事件触发时，传递给事件处理者的参数，在事件处理者中通过Event::data访问
     * @param mixed $data the data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[Event::data]].
	 * 默认是true,代表顺序添加到事件处理者队列的队尾，若是false,则添加到队头
     * @param bool $append whether to append new event handler to the end of the existing
     * handler list. If `false`, the new handler will be inserted at the beginning of the existing
     * handler list.
     * @see off()
     */
    public static function on($class, $name, $handler, $data = null, $append = true)
    {
		//去除最左边的斜杠，据说这是PHP早期支持命名空间时的bug
        $class = ltrim($class, '\\');
		//下面的代码和Component::on类似，只是加上了$class参数，变成了三维数组
        if ($append || empty(self::$_events[$name][$class])) {
            self::$_events[$name][$class][] = [$handler, $data];
        } else {
            array_unshift(self::$_events[$name][$class], [$handler, $data]);
        }
    }

    /**
     * Detaches an event handler from a class-level event.
     *  
     * This method is the opposite of [[on()]].
     * 既然是类级别的事件，得提供类的名字，全名
     * @param string $class the fully qualified class name from which the event handler needs to be detached.
     * @param string $name the event name.
     * @param callable $handler the event handler to be removed.
     * If it is `null`, all handlers attached to the named event will be removed.
     * @return bool whether a handler is found and detached.
     * @see on()
     */
    public static function off($class, $name, $handler = null)
    {
        $class = ltrim($class, '\\');
		//这个类上跟本没有绑定事件处理者呢，直接走人！
        if (empty(self::$_events[$name][$class])) {
            return false;
        }
		//没有事件处理者，那就是这个类的这个事件的事件处理者队列全部解绑
        if ($handler === null) {
            unset(self::$_events[$name][$class]);
            return true;
        }

        $removed = false;
		//还是遍历，解绑这个事件处理者，注意用的是全等
        foreach (self::$_events[$name][$class] as $i => $event) {
            if ($event[0] === $handler) {
                unset(self::$_events[$name][$class][$i]);
                $removed = true;
            }
        }
		//重新索引一下下标
        if ($removed) {
            self::$_events[$name][$class] = array_values(self::$_events[$name][$class]);
        }
        return $removed;
    }

    /** 
	 * 直接清空所有的类的所有事件的所有事件处理者队列
	 * 一句话，全部都清空。
	 * 我倒想不到，什么场景下需要调用这个方法呢？
     * Detaches all registered class-level event handlers.
     * @see on()
     * @see off()
     * @since 2.0.10
     */
    public static function offAll()
    {
        self::$_events = [];
    }

    /**判断某一个类级别的事件，是否有绑定的事件处理者
     * Returns a value indicating whether there is any handler attached to the specified class-level event.
	 * 不但查$class指定的类，而且还检查$class的父类里有没有绑定过$name事件的事件处理者
     * Note that this method will also check all parent classes to see if there is any handler attached
     * to the named event.
	 * $class可以是一个类名，或者是一个对象
     * @param string|object $class the object or the fully qualified class name specifying the class-level event.
     * @param string $name the event name.
     * @return bool whether there is any handler attached to the event.
     */
    public static function hasHandlers($class, $name)
    {
		//事件都没有呢，直接走人
        if (empty(self::$_events[$name])) {
            return false;
        }
		//是个对象，就找到它所属的类
        if (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }
		//把类，父类，接口一并都拿来
        $classes = array_merge(
            [$class],
            class_parents($class, true),
            class_implements($class, true)
        );
		//来遍历吧，三维数组，$name,$class,[]=[$handler,$data]
        foreach ($classes as $class) {
            if (!empty(self::$_events[$name][$class])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 触发一个类级别的事件
     * Triggers a class-level event.
	 * 这个方法将会触发该类的该事件的所有事件处理者方法的调用，父类的也会调用
     * This method will cause invocation of event handlers that are attached to the named event
     * for the specified class and all its parent classes.
     * @param string|object $class the object or the fully qualified class name specifying the class-level event.
     * @param string $name the event name.
	 * 事件对象，如果不传，则使用Event对象
	 * 事件对象很重要，是所有事件处理者在调用时所共享的对象，前一个事件处理者对$event的操作，会影响到后续的事件
	 * 处理者。
	 * 事件对象保留了事件发生时的一些有用信息：事件名name，事件触发者sender，是否handled等
	 * 当然，也可以在触发时自定义其他有用的信息
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created.
     */
    public static function trigger($class, $name, $event = null)
    {
		//根本没这个事件，那还触发个啥，直接退出!
        if (empty(self::$_events[$name])) {
            return;
        }
		//第三个参数为空，则实例化调用者，注意是static，而不是Event。
        if ($event === null) {
            $event = new static;
        }
		//定义一个标记
        $event->handled = false;
        $event->name = $name; //事件名，保存到事件对象的name属性

        if (is_object($class)) {
            if ($event->sender === null) {
				//把当前对象传递给事件对象的sender属性，这样在事件处理者中就能访问知道是哪个对象触发的了。
                $event->sender = $class;
            }
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }
		//当前类，父类，接口一并拿来
        $classes = array_merge(
            [$class],
            class_parents($class, true),
            class_implements($class, true)
        );
		//遍历调用$name事件，$class类的事件处理者队列里的成员。
        foreach ($classes as $class) {
            if (!empty(self::$_events[$name][$class])) {
                foreach (self::$_events[$name][$class] as $handler) {
					//每个事件处理者的参数都在绑定时确定，而非事件触发时确定，在事件处理者内部通过event::data访问
                    $event->data = $handler[1];
                    call_user_func($handler[0], $event);
					//对象都是引用传参，故在事件处理者内部转了一圈回到这里，会影响它的handled属性。
					//只要某个事件处理者把handled置为true,那么后续的事件处理者就都不再调用执行了
                    if ($event->handled) {
                        return;
                    }
                }
            }
        }
    }
}
