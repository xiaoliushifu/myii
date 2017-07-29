<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ActionFilter is the base class for action filters.
 *ActionFilter是所有动作过滤器的基类
 * 动作过滤器通过由模块和控制器触发的响应事件来参与到动作执行流中，
 * 模块里的动作过滤器比控制器里的动作过滤器优先执行（它们都属于预过滤器），另外还有application级别的动作过滤器这里没有提及
 * 
 * An action filter will participate in the action execution workflow by responding to
 * the `beforeAction` and `afterAction` events triggered by modules and controllers.
 *具体的filter比如AccessControll，PageCache都是继承自Actionfilter。
 *它们都是动作过滤器，至少都得实现beforeAction,afterAction啊这些个。
 *在beforeAction里可以干啥，那就看具体的动作过滤器类的实现了。存取访问？页面缓存等都行。
 * Check implementation of [[\yii\filters\AccessControl]], [[\yii\filters\PageCache]] and [[\yii\filters\HttpCache]] as examples on how to use it.
 *
 * For more details and usage information on ActionFilter, see the [guide article on filters](guide:structure-filters).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActionFilter extends Behavior
{
    /**
	 *  动作名的数组，为空则过滤器应用于所有动作，除非写在except数组里
     * @var array list of action IDs that this filter should apply to. If this property is not set,
     * then the filter applies to all actions, unless they are listed in [[except]].
     * If an action ID appears in both [[only]] and [[except]], this filter will NOT apply to it.
     *
     * Note that if the filter is attached to a module, the action IDs should also include child module IDs (if any)
     * and controller IDs.
     *
     * Since version 2.0.9 action IDs can be specified as wildcards, e.g. `site/*`.
     *
     * @see except
     */
    public $only;
    /**
     * @var array list of action IDs that this filter should not apply to.
     * @see only
     */
    public $except = [];


    /**
     * @inheritdoc
	 * ActionFilter是过滤器不错，但是更大的概念，它还是行为类，所以它有绑定到应用组件类的attach方法
	 * 该方法覆盖了父类Component的attach()方法
	 * 这种行为类的目的比较一致，都是冲着Controller::EVENT_BEFORE_ACTION的事件来的，想在这里发挥点作用。
	 * 根据作用的不同（访问控制，页面缓存，请求host过滤，客户端缓存等）写了多个行为类，都是ActionFilter的子类。
	 * 具体如何实现各自的作用呢？
	 * 目前来看，这些行为子类都没有attach方法，只有ActionFilter父类有，attach方法是行为类绑定到应用组件的方式之一
	 * 绑定到应用组件不重要，重要的是它绑定了控制器类的Controller::EVENT_BEFORE_ACTION事件，事件处理者是
	 * ActionFilter::beforeFilter。我们去看beforeFilter方法，发现它在内部会执行beforeAction方法。这个beforeAction
	 * 方法不得了，目前来看它就是行为子类扩展自己功能的关键。也就是说任何ActionFilter行为子类只要实现了
	 * beforeAction方法，在该方法里写自己的逻辑就是了，不信？去看看filter目录下的类就更清楚了。

	 * 最后总结：
		从ActionFilter类的功能上来说，它是过滤器，因为它都是在真正执行控制器动作之前发挥作用。
		如何确保在控制器动作执行之前？绑定Controller::EVENT_BEFORE_ACTION事件即可
		何时绑定？在触发Controller::EVENT_BEFORE_ACTION事件的最后关头的ensureBehaviors()方法完成绑定。
		这是Yii的机制，在触发任何事件之前的最后一刻都会执行ensureBehaviors()为行为类提供机会完成绑定。
		为什么不直接通过on绑定呢？当然，如果过滤器类不是行为类，那肯定必须有一套自己的机制完成
		Controller::EVENT_BEFORE_ACTION事件的绑定，进而发挥自己的作用。这里为啥把ActionFilter写成行为类。
		我猜应该是绑定方便，不用单独写on。或者估计作者有一套自己的想法吧。
		ActionFilter即是过滤器，又是行为类，同一个东西有两个名称，前因后果明白了吧？
     */
    public function attach($owner)
    {
        $this->owner = $owner;
		//只绑定beforeFilter方法就行了
        $owner->on(Controller::EVENT_BEFORE_ACTION, [$this, 'beforeFilter']);
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        if ($this->owner) {
            $this->owner->off(Controller::EVENT_BEFORE_ACTION, [$this, 'beforeFilter']);
            $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
            $this->owner = null;
        }
    }

    /**
     * @param ActionEvent $event
     */
    public function beforeFilter($event)
    {
        if (!$this->isActive($event->action)) {
            return;
        }

        $event->isValid = $this->beforeAction($event->action);
        if ($event->isValid) {
            // call afterFilter only if beforeFilter succeeds
            // beforeFilter and afterFilter should be properly nested
            $this->owner->on(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter'], null, false);
        } else {
            $event->handled = true;
        }
    }

    /**
     * @param ActionEvent $event
     */
    public function afterFilter($event)
    {
        $event->result = $this->afterAction($event->action, $event->result);
        $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        return true;
    }

    /**
     * This method is invoked right after an action is executed.
     * You may override this method to do some postprocessing for the action.
     * @param Action $action the action just executed.
     * @param mixed $result the action execution result
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        return $result;
    }

    /**
     * Returns an action ID by converting [[Action::$uniqueId]] into an ID relative to the module
     * @param Action $action
     * @return string
     * @since 2.0.7
     */
    protected function getActionId($action)
    {
        if ($this->owner instanceof Module) {
            $mid = $this->owner->getUniqueId();
            $id = $action->getUniqueId();
            if ($mid !== '' && strpos($id, $mid) === 0) {
                $id = substr($id, strlen($mid) + 1);
            }
        } else {
            $id = $action->id;
        }

        return $id;
    }

    /**
	 * 判断当前的动作是否受动作过滤器的约束，受约束则是active,不受约束则not active
	 * 本质上就是通过only数组来判断的。
     * Returns a value indicating whether the filter is active for the given action.
     * @param Action $action the action being filtered
     * @return bool whether the filter is active for the given action.
     */
    protected function isActive($action)
    {
        $id = $this->getActionId($action);

        if (empty($this->only)) {
            $onlyMatch = true;
        } else {
            $onlyMatch = false;
            foreach ($this->only as $pattern) {
                if (fnmatch($pattern, $id)) {
                    $onlyMatch = true;
                    break;
                }
            }
        }

        $exceptMatch = false;
        foreach ($this->except as $pattern) {
            if (fnmatch($pattern, $id)) {
                $exceptMatch = true;
                break;
            }
        }

        return !$exceptMatch && $onlyMatch;
    }
}
