<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Action is the base class for all controller action classes.
 *
 * Action provides a way to reuse action method code. An action method in an Action
 * class can be used in multiple controllers or in different projects.
 *Action类必须实现一个名为run的方法，该方法当被控制器需要的时候调用
 * Derived classes must implement a method named `run()`. This method
 * will be invoked by the controller when the action is requested.
 * run方法可以接收参数。比如下面的例子：
 * The `run()` method can have parameters which will be filled up
 * with user input values automatically according to their names.
 * For example, if the `run()` method is declared as follows:
 *
 * ```php
 * public function run($id, $type = 'book') { ... }
 * ```
 *
 * And the parameters provided for the action are: `['id' => 1]`.
 * Then the `run()` method will be invoked as `run(1)` automatically.
 *
 * For more details and usage information on Action, see the [guide article on actions](guide:structure-controllers).
 *
 * @property string $uniqueId The unique ID of this action among the whole application. This property is
 * read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Action extends Component
{
    /**
     * 动作ID
     * @var string ID of the action
     */
    public $id;
    /**
     * 动作的属主控制器，对象。不是字符串。
     * @var Controller|\yii\web\Controller the controller that owns this action
     */
    public $controller;


    /**
     * 构造函数
     * Constructor.
     *
     * @param string $id the ID of this action
     * @param Controller $controller the controller that owns this action
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($id, $controller, $config = [])
    {
        //动作ID
        $this->id = $id;
        //属主控制器对象
        $this->controller = $controller;
        //再去调用父类。这下InlineAction--->Action--->Object连跳三级。
        parent::__construct($config);
    }

    /**
     * 返回动作ID,针对整个应用主体下，唯一识别的ID信息
     * Returns the unique ID of this action among the whole application.
     *其实是一个控制器id/动作ID的字符串，（控制器id不能重复，再拼接上动作iD，当然也不会重复了，当然也就唯一了）
     * @return string the unique ID of this action among the whole application.
     */
    public function getUniqueId()
    {
        return $this->controller->getUniqueId() . '/' . $this->id;
    }

    /**
     * 带着参数执行控制器的动作。独立动作
     * Runs this action with the specified parameters.
     * This method is mainly invoked by the controller.
     *
     * @param array $params the parameters to be bound to the action's run() method.
     * @return mixed the result of the action
     * @throws InvalidConfigException if the action class does not have a run() method
     */
    public function runWithParams($params)
    {
        //独立动作就是要执行它的run方法，这一点不同于行内动作（InlineAction)
        if (!method_exists($this, 'run')) {
            throw new InvalidConfigException(get_class($this) . ' must define a "run()" method.');
        }
        //依然要去绑定run方法执行时的参数（一般在子类中实现）
        $args = $this->controller->bindActionParams($this, $params);
        Yii::trace('Running action: ' . get_class($this) . '::run()', __METHOD__);
        //这是要干啥，把参数给到全局对象，应用主体上。
        if (Yii::$app->requestedParams === null) {
            Yii::$app->requestedParams = $args;
        }
        //独立动作，还有一个beforeRun()，类似于事件，但实际不是事件
        if ($this->beforeRun()) {
            //独立动作，就是执行独立动作这个对象的run()方法
            $result = call_user_func_array([$this, 'run'], $args);
            //独立动作执行完后，还给一个afterRun()，这都是嵌入自定义代码的好机会
            $this->afterRun();

            return $result;
        } else {
            return null;
        }
    }

    /**
     * 在run方法调用之前
     * This method is called right before `run()` is executed.
     * 默认什么也没有实现，开发者可以覆盖这个方法为run做一些准备工作
     * You may override this method to do preparation work for the action run.
     * 如果该方法返回false,则会取消独立动作的执行
     * If the method returns false, it will cancel the action.
     *
     * @return bool whether to run the action.
     */
    protected function beforeRun()
    {
        return true;
    }

    /**
     * 在run()方法执行完后，就执行这个方法
     * This method is called right after `run()` is executed.
     * 通过覆盖这个方法，可以做一些后处理
     * You may override this method to do post-processing work for the action run.
     */
    protected function afterRun()
    {
    }
}
