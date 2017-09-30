<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * InlineAction表示动作对象，对应控制器的方法
 * InlineAction represents an action that is defined as a controller method.
 *
 *actionMethod表示控制器的方法，在控制器中创建动作对象时的构造函数，第三个参数初始化的
 * The name of the controller method is available via [[actionMethod]] which
 * is set by the [[controller]] who creates this action.
 *
 * For more details and usage information on InlineAction, see the [guide article on actions](guide:structure-controllers).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InlineAction extends Action
{
    /**字符串，控制器方法名，关联行内动作对象。
     * @var string the controller method that this inline action is associated with
     */
    public $actionMethod;


    /**
     * @param string $id the ID of this action  动作ID
     * @param Controller $controller the controller that owns this action 控制器，即动作的属主
     * @param string $actionMethod the controller method that this inline action is associated with  控制器方法名 （actionxxxxx)
     * @param array $config name-value pairs that will be used to initialize the object properties 初始化配置信息
     */
    public function __construct($id, $controller, $actionMethod, $config = [])
    {
        //与Action基类对比来说的话，InlineAction只多了一个actionMethod而已，字符串。
        $this->actionMethod = $actionMethod;
        parent::__construct($id, $controller, $config);
    }

    /**
     * 传入指定的参数来执行当前的动作,这是行内动作，不是独立动作
     * Runs this action with the specified parameters.
     * 该方法由控制器来调用
     * This method is mainly invoked by the controller.
     * @param array $params action parameters
     * @return mixed the result of the action
     */
    public function runWithParams($params)
    {
        $args = $this->controller->bindActionParams($this, $params);
        //打个trace级别的日志
        Yii::trace('Running action: ' . get_class($this->controller) . '::' . $this->actionMethod . '()', __METHOD__);
        if (Yii::$app->requestedParams === null) {
            //全局保存请求到指定方法的参数
            Yii::$app->requestedParams = $args;
        }
        //用php原生函数call_user_func_array来调用方法，疑惑。
        //为什么不直接$this->controller->actionMethod()调用呢？最直观，简单了，为啥不呢？
        //call_user_func_array函数的返回值，就是actionMethod后的返回值
        return call_user_func_array([$this->controller, $this->actionMethod], $args);
    }
}
