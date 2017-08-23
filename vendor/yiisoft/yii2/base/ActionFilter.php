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
 我们看到 动作过滤器继承自行为类Behavior。所以往大了说，过滤器也属于行为类
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
		下面还有两句话：
		ActionFilter叫过滤器，是因为它通过响应Controller::EVENT_BEFORE_ACTION事件发挥作用。
		ActionFilter叫行为类，是从它如何绑定事件，何时绑定事件上来说的。
     */
    public function attach($owner)
    {
        $this->owner = $owner;
		//只绑定beforeFilter方法就行了，且只绑定了控制器的EVENT_BEFORE_ACTION事件。
		//从这里是否又能看出：过滤器是行为类的一个特例而已（绑定了固定的事件与固定的事件处理者）
        $owner->on(Controller::EVENT_BEFORE_ACTION, [$this, 'beforeFilter']);
    }

    /**
	 * 脱离组件类
     * @inheritdoc
     */
    public function detach()
    {
        if ($this->owner) {
			//这里是把行为类当初绑定到组件类的事件，解绑掉。
            $this->owner->off(Controller::EVENT_BEFORE_ACTION, [$this, 'beforeFilter']);
            $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
			//这句代码很关键，这就使得当前行为脱离了组件类
            $this->owner = null;
        }
    }

    /**
	* 这个方法目前来看，也是父类仅有的，子类无需覆盖这个方法。而是直接写beforeAction即可。
     * @param ActionEvent $event
     */
    public function beforeFilter($event)
    {
		//首先判断当前的过滤器，是否对这个action有兴趣。
        if (!$this->isActive($event->action)) {
            return;
        }
		//执行一段固定的方法beforeAction。这是所有过滤器子类发挥自己特性的地方。
        $event->isValid = $this->beforeAction($event->action);
		//beforeAction的返回值影响afterAction的绑定，注意，是绑定，并不是调用执行哟
		//其实，能绑定就能执行，只是并不是在这里立即执行而已
        if ($event->isValid) {
            // call afterFilter only if beforeFilter succeeds
            // beforeFilter and afterFilter should be properly nested
			//注意，on方法第四个参数是false，意味着afterFilter注册到事件处理者队列的最前面
            $this->owner->on(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter'], null, false);
        } else {
            $event->handled = true;
        }
    }

    /**
	 * afterFilter的实质，就是afterAction方法而已
     * @param ActionEvent $event
     */
    public function afterFilter($event)
    {
		//执行完后就解绑afterFilter。为什么要解绑呢？是怕后患无穷吗？
        $event->result = $this->afterAction($event->action, $event->result);
        $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
    }

    /**
	 * 这个方法，就是交给过滤器子类来实现和发挥作用的，所以这里并没有什么实际的逻辑实现
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
	 *  也是留给过滤器子类来实现的
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
	 * 返回一个动作的名称，比如"index","login","list"等，不带有action的方法名
	 * 目前上不明白ID在控制器，模块，动作这三个概念之间的意思
     * Returns an action ID by converting [[Action::$uniqueId]] into an ID relative to the module
     * @param Action $action
     * @return string
     * @since 2.0.7
     */
    protected function getActionId($action)
    {
		//目前看到的owner都是控制器，是属于组件Component而非Module，故都直接返回$action对象的id属性即可。
        if ($this->owner instanceof Module) {
            $mid = $this->owner->getUniqueId();
            $id = $action->getUniqueId();
            if ($mid !== '' && strpos($id, $mid) === 0) {
                $id = substr($id, strlen($mid) + 1);
            }
		//目前是走这里
        } else {
            $id = $action->id;
        }

        return $id;
    }

    /**
	 * 判断当前的动作是否受动作过滤器的约束，受约束则是active,不受约束则not active
	 * 本质上就是通过only数组来判断的。
     * Returns a value indicating whether the filter is active for the given action.
	 * $action 当前的action对象，
     * @param Action $action the action being filtered
	 * 返回布尔值，当前的过滤器是否中意这个$action。因为不是哪个过滤器是中意所有$action的
	 * 它们也是有所选择的。具体就是过滤器的only和except两个成员发挥作用
     * @return bool whether the filter is active for the given action.
     */
    protected function isActive($action)
    {
        $id = $this->getActionId($action);
		//过滤器没有针对哪个action情有独钟，则所有action都行
        if (empty($this->only)) {
            $onlyMatch = true;
        } else {

		
            $onlyMatch = false;
			//依次遍历自己中意的action模式，用php原生函数fnmatch匹配这个$id是否符合自己中意的action
            foreach ($this->only as $pattern) {
                if (fnmatch($pattern, $id)) {
                    $onlyMatch = true;
                    break;
                }
            }
        }

        $exceptMatch = false;
		//依次遍历自己排外的action模式，用php原生函数fnmatch匹配这个$id是否符合自己排外的action
        foreach ($this->except as $pattern) {
            if (fnmatch($pattern, $id)) {
                $exceptMatch = true;
                break;
            }
        }
		//不能排外且还得喜欢这个action,才是中意的action
        return !$exceptMatch && $onlyMatch;
    }
}
