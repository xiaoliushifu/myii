<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ActionEvent是触发action事件时的事件对象参数，是Event类的子类
 * ActionEvent represents the event parameter used for an action event.
 *通过设置isValid属性，可以决定是否真的执行这个action
 * By setting the [[isValid]] property, one may control whether to continue running the action.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActionEvent extends Event
{
    /**
     * @var Action the action currently being executed
     * 存储当前的action对象，要提升思想，action是一个对象，不是一个'index','list'这样的字符串
     * action对象的actionMethod指出了完整的方法名actionXXXXX
     * action对象的controller指出了所属的控制器
     */
    public $action;
    /**
     * 执行方法后的结果，方法的结果应该在方法里return出来，但是在事件处理器中也可以更改$result
     * @var mixed the action result. Event handlers may modify this property to change the action result.
     */
    public $result;
    /**
     * 布尔值，是否继续执行当前的action。注意区别，事件流的中断(提前结束）是由事件对象的$handled属性决定的，
     * 无论事件流是否提前结束，事件流结束之后都得去执行action。
     *这个$isValid是在事件流结束之后，决定action动作是否执行的判断依据
     * @var bool whether to continue running the action. Event handlers of
     * [[Controller::EVENT_BEFORE_ACTION]] may set this property to decide whether
     * to continue running the current action.
     * actionEvent扩展了父类Event的属性，看吧，每个Event的子类都得扩展这个属性
     */
    public $isValid = true;


    /**
     * Constructor.
     * @param Action $action the action associated with this action event.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($action, $config = [])
    {
        $this->action = $action;
        //会调用父类Event初始化属性
        parent::__construct($config);
    }
}
