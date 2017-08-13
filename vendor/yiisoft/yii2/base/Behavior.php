<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * 行为是所有behavior类的基类
 * Behavior is the base class for all behavior classes.
 * 一个行为类能够增强一个已有的组件的功能而不必更改组件的代码
 * A behavior can be used to enhance the functionality of an existing component without modifying its code.
 * 特别的情况下，可以注入行为类的属性和方法到组件中去，让组件直接访问这些属性和方法
 * In particular, it can "inject" its own methods and properties into the component
 * 行为也能响应在组件中触发的时机（有待确认）
 * and make them directly accessible via the component. It can also respond to the events triggered in the component
 * 因此可以中断正常的代码执行流程
 * and thus intercept the normal code execution.
 *
 * For more details and usage information on Behavior, see the [guide article on behaviors](guide:concept-behaviors).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Behavior extends Object
{
    /**
     * @var Component the owner of this behavior
	 * owner属性，标识了当前behavior类属于哪个组件类，或者说behavior类绑定到哪个组件类中
	 *  owner类
     */
    public $owner;


    /**
	 * 为绑定类声明事件
     * Declares event handlers for the [[owner]]'s events.
     * behavior子类一般可以声明一些事件处理者，将来绑定到owner类的某个事件上
     * Child classes may override this method to declare what PHP callbacks should
     * be attached to the events of the [[owner]] component.
     * 当behavior绑定到owner上时，会立即把behavior里声明的事件处理者也绑定到owner上
     * The callbacks will be attached to the [[owner]]'s events when the behavior is
	 * 当behavior解绑一个owner时，behavior里声明的事件也会解绑，有始有终
     * attached to the owner; and they will be detached from the events when
     * the behavior is detached from the component.
     *
     * The callbacks can be any of the following:
     * *事件处理者都是什么，参数事件类里的handler就是了
     * - method in this behavior: `'handleClick'`, equivalent to `[$this, 'handleClick']`
     * - object method: `[$object, 'handleClick']`
     * - static method: `['Page', 'handleClick']`
     * - anonymous function: `function ($event) { ... }`
     *
     * The following is an example:
     *
     * ```php
     * [
     *     Model::EVENT_BEFORE_VALIDATE => 'myBeforeValidate',
     *     Model::EVENT_AFTER_VALIDATE => 'myAfterValidate',
     * ]
     * ```
     * 父类一般是空的，需要子类决定要绑定哪些事件
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        return [];
    }

    /**把behavior绑定到$owner类上，是通过设置behavior的owner属性完成的
	 * 这就是attach方法的本意。可是，还没完。
     * Attaches the behavior object to the component.
     * The default implementation will set the [[owner]] property
	 * 这里要注意的是，绑定behavior之后，还要把behavior里声明的事件也绑定到owner的类上（这里反应了事件与行为的关系）
     * and attach event handlers as declared in [[events]].
	 * 如果子类覆盖了这个方法，请确保parent::attach($owner)
     * Make sure you call the parent implementation if you override this method.
     * @param Component $owner the component that this behavior is to be attached to.
     */
    public function attach($owner)
    {
        $this->owner = $owner;
		//遍历事件
        foreach ($this->events() as $event => $handler) {
			//用owner的on方法绑定事件，也就是说绑定到owner上，但是事件处理者注意，
			//它的对象是$this,是behavior，不是owner哦，这一点要注意
            $owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
        }
    }

    /**从behavior自己的owner上解绑自己
     * Detaches the behavior object from the component.
	 * 所谓解绑，是设置自己的owner为null实现的
     * The default implementation will unset the [[owner]] property
	 * 另外还要把曾经绑定的事件也一块解绑了
     * and detach event handlers declared in [[events]].
     * Make sure you call the parent implementation if you override this method.
     */
    public function detach()
    {
        if ($this->owner) {
			//遍历曾经的事件，然后一个个解绑就行了
            foreach ($this->events() as $event => $handler) {
                $this->owner->off($event, is_string($handler) ? [$this, $handler] : $handler);
            }
            $this->owner = null;
        }
    }
}
