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
     */
    public function attach($owner)
    {
        $this->owner = $owner;
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
