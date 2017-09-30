<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\filters;

use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\di\Instance;
use yii\web\User;
use yii\web\ForbiddenHttpException;

/**
 * AccessControl provides simple access control based on a set of rules.
 *AccessControl是一个动作过滤器。可以针对具体的控制器里的方法（动作）进行规则配置
 * 比如哪些方法受约束，如果约束的话，约束规则是什么。
 * *如果请求方法不受约束，则直接放行。
 * 如果当前的请求方法受约束的话，规则的设置一般是：
 * 用户是什么角色[未登录的访客，登录用户，具体角色的用户]
 * 用户本次请求的IP是什么
 * 根据上述进行匹配。
 * 约束规则不匹配则拒绝
 * AccessControl is an action filter. It will check its [[rules]] to find
 * the first rule that matches the current context variables (such as user IP address, user role).
 * The matching rule will dictate whether to allow or deny the access to the requested controller
 * action. If no rule matches, the access will be denied.
 *
 * To use AccessControl, declare it in the `behaviors()` method of your controller class.
 * For example, the following declarations will allow authenticated users to access the "create"
 * and "update" actions and deny all other users from accessing these two actions.
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'access' => [
 *             'class' => \yii\filters\AccessControl::className(),
 *             'only' => ['create', 'update'],
 *             'rules' => [
 *                 // deny all POST requests
 *                 [
 *                     'allow' => false,
 *                     'verbs' => ['POST']
 *                 ],
 *                 // allow authenticated users
 *                 [
 *                     'allow' => true,
 *                     'roles' => ['@'],
 *                 ],
 *                 // everything else is denied
 *             ],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AccessControl extends ActionFilter
{
    /**
     * User 数组，字符串  。用户对象，表示认证状态或者应用主体的user组件
     * @var User|array|string the user object representing the authentication status or the ID of the user application component.
     * 从2.0.2开始的，也可以是一个数组配置信息，用来生成对象
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $user = 'user';
    /**
     * 一个回调函数，如果当前的过滤器拒绝了当前的用户（程序不放行）。
     * @var callable a callback that will be called if the access should be denied
     * 如果没有设置回调，那么[[denyAccess()]]将会调用
     * to the current user. If not set, [[denyAccess()]] will be called.
     *
     *回调函数的签名如下：
     * The signature of the callback should be as follows:
     *
     * ```php
     * function ($rule, $action)
     * ```
     *$rule是拒绝的那个规则对象，而$action则一般是行内对象
     * where `$rule` is the rule that denies the user, and `$action` is the current [[Action|action]] object.
     * $rule也可以是null,因为也可能是因为没有一个规则匹配而被拒绝
     * `$rule` can be `null` if access is denied because none of the rules matched.
     */
    public $denyCallback;
    /**
	*  rules类的配置，指明使用哪个rule类，及rule类的属性设置。
	*  而且在这里设置rule类的属性，比下面的rules属性里设置的属性优先。
     * @var array the default configuration of access rules. Individual rule configurations
     * specified via [[rules]] will take precedence when the same property of the rule is configured.
     */
    public $ruleConfig = ['class' => 'yii\filters\AccessRule'];
    /**
	 * rules里装满了一系列的rule对象，或者能够生成rules对象的配置信息，数组格式。
     * @var array a list of access rule objects or configuration arrays for creating the rule objects.
	 * 如果是rule类的配置信息，那么会参考上一个ruleConfig属性。
	 * rule是需要我们在各个控制器的behavior方法里的AccessController过滤器里配置的，比如siteController
	 * 一个rule一般约束一个或几个具体的动作，多个rule对象各自约束的动作可以用actions指定
     * If a rule is specified via a configuration array, it will be merged with [[ruleConfig]] first
     * before it is used for creating the rule object.
     * @see ruleConfig
     */
    public $rules = [];


    /** 在init方法里，创建AccessRule类。
     * Initializes the [[rules]] array by instantiating rule objects from configurations.
     */
    public function init()
    {
        parent::init();
        $this->user = Instance::ensure($this->user, User::className());
        foreach ($this->rules as $i => $rule) {
            if (is_array($rule)) {
                $this->rules[$i] = Yii::createObject(array_merge($this->ruleConfig, $rule));
            }
        }
    }

    /**
     * 该方法就是每个过滤器发挥自己特性的地方了。
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * 开发人员可以写自己的过滤器类，而这个类的重点就是要写这个beforeAction方法来完成自己的逻辑
     * 对于当前过滤器AccessControl来说，它的逻辑大概是这样的：
     * 针对当前用户请求的路由动作，用指定的Rule对象来做验证，通过就往下走，否则就拒绝。
     * 真正的实现逻辑，是在Rule对象里完成的。走，去Rule类里看看
     * You may override this method to do last-minute preparation for the action.
     * @param Action $action the action to be executed.  $action是行内动作对象（独立动作可以吗？）
     * @return bool whether the action should continue to be executed.  返回值，
     */
    public function beforeAction($action)
    {
        $user = $this->user;
        $request = Yii::$app->getRequest();
        /* @var $rule AccessRule */
        foreach ($this->rules as $rule) {
			//根据配置好的rules数组，依次遍历，靠前的rule如果允许，则后续的rule不再验证
			//因为根据siteController::behaviors方法里rules的配置。
			//每个具体的rule都是指定一个动作或者多个动作，各个rule针对的动作不会相同。
			//所以有一个rule类匹配并验证该动作后的allow为真，那就不用看后续的rule了。
			//因为后续的rule不可能再匹配当前的动作了
			//通过查看AccessRule类的allows方法知道，allows方法返回三种值：
			//null，代表该rule没有匹配上，继续下一个rule的匹配
			//true,代表匹配上了，放行
			//false,代表匹配上了，拒绝，不放行
            if ($allow = $rule->allows($action, $user, $request)) {
                //放行
                return true;
            //返回false,意味着拒绝（规则肯定匹配上了）
            } elseif ($allow === false) {
                //优先使用规则类自定义的回调
                if (isset($rule->denyCallback)) {
                    call_user_func($rule->denyCallback, $rule, $action);
                //否则使用AccessController的自定义回调
                } elseif ($this->denyCallback !== null) {
                    call_user_func($this->denyCallback, $rule, $action);
                } else {
                //最后，规则类和AccessController都没有自定义的回调，那么就使用默认的回调处理
                    $this->denyAccess($user);
                }
                //拒绝，一律返回false，赋值给事件对象的isValid成员，意味着不放行。
                return false;
            }
        }
        /*规则为空；或者有规则，但是不匹配(allow方法返回null的情况）*/
        //一：  有自定义拒绝回调，是使用自定义规则回调
        if ($this->denyCallback !== null) {
            //还是调用denyCallback
            call_user_func($this->denyCallback, null, $action);
       //二： 否则，走默认的拒绝方法
        } else {
            $this->denyAccess($user);
        }
        //最终返回falsel,意味着不放行
        return false;
    }

    /**
     * 规则为空，或者有规则但是不匹配，或者匹配后返回false。
     * 都有可能执行这个方法。
     * 这个方法的逻辑是：
	 * 未登录，就重定向到登录页面
	 * 已登录，报禁止异常错误
     * Denies the access of the user.
     * The default implementation will redirect the user to the login page if he is a guest;
     * if the user is already logged, a 403 HTTP exception will be thrown.
     * @param User $user the current user
     * @throws ForbiddenHttpException if the user is already logged in.
     */
    protected function denyAccess($user)
    {
        if ($user->getIsGuest()) {
            $user->loginRequired();
        } else {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
    }
}
