<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\filters;

use yii\base\Component;
use yii\base\Action;
use yii\web\User;
use yii\web\Request;
use yii\base\Controller;

/**
 * 该类代表由AccessController过滤器定义的一系列规则rules的类
 * 它的属性就是一个个的规则。通过allows方法进行匹配
 * 该类不直接使用，而是在AccessControl过滤器中使用
 * This class represents an access rule defined by the [[AccessControl]] action filter
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AccessRule extends Component
{
    /**
     * 布尔，适用的规则是允许还是拒绝
     * @var bool whether this is an 'allow' rule or 'deny' rule.
     */
    public $allow;
    /**
	*  数组，actions规则，指出了当前规则类可以管辖的动作，比较的大小写敏感的
     * @var array list of action IDs that this rule applies to. The comparison is case-sensitive.
     * 如果没有设置或为空，意味着该规则适用所有请求的动作
     * If not set or empty, it means this rule applies to all actions.
     */
    public $actions;
    /**
	*  数组，控制器规则，给出控制器ID，指出当前规则类可以管辖的控制器（适用的控制器）
     * @var array list of the controller IDs that this rule applies to.
     * 控制器ID的比较是用[[\yii\base\Controller::uniqueId]]来比较的，所以每个控制器都带有模块前缀（有的话）
     * The comparison uses [[\yii\base\Controller::uniqueId]], so each controller ID is prefixed
     * 比如，应用主体里的product控制器，就写成['product']
     * with the module ID (if any). For a `product` controller in the application, you would specify
     * ,而如果product位于shop模块下，则应该这样写：['shop/product']
     * this property like `['product']` and if that controller is located in a `shop` module, this
     * would be `['shop/product']`.
     *
     * The comparison is case-sensitive.
     *
     * If not set or empty, it means this rule applies to all controllers.
     */
    public $controllers;
    /**
     * 角色规则，目前就两个
     * @var array list of roles that this rule applies to. Two special roles are recognized, and
     * 角色的验证是通过User::isGuest来完成的。
     * they are checked via [[User::isGuest]]:
     *
     * ?表示来宾用户，（未认证的，没登录的）
     * - `?`: matches a guest user (not authenticated yet)
     * @表示认证用户（已登录的）
     * - `@`: matches an authenticated user
     *如果你正在使用RBAC机制，你应该指定角色或者权限名
     * If you are using RBAC (Role-Based Access Control), you may also specify role or permission names.
     * 这时将会用User::can来验证，而不是上述的User::isGuest了。
     * In this case, [[User::can()]] will be called to check access.
     *如果没有设置或空，则意味着使用于所有角色
     * If this property is not set or empty, it means this rule applies to all roles.
     */
    public $roles;
    /**
     * IP规则
     * 数组，客户端IP。该规则适用的ip地址。
     * @var array list of user IP addresses that this rule applies to. An IP address
     * IP地址可以在结尾包含通配符"*"，用来匹配同样的前缀
     * can contain the wildcard `*` at the end so that it matches IP addresses with the same prefix.
     * 比如，"192.168.*"匹配所有的“192.168.xxx.xxx"
     * For example, '192.168.*' matches all IP addresses in the segment '192.168.'.
     * 没有设置或为空，意味着适用于所有IP地址
     * If not set or empty, it means this rule applies to all IP addresses.
     * @see Request::userIP
     */
    public $ips;
    /**
     * 数组，http的请求方法规则（比如GET,POST)
     * @var array list of request methods (e.g. `GET`, `POST`) that this rule applies to.
     * 没设置或为空，意味着适用于所有的http方法
     * If not set or empty, it means this rule applies to all request methods.
     * @see \yii\web\Request::method
     */
    public $verbs;
    /**
     * 回调函数，自定义规则的方式
     * @var callable a callback that will be called to determine if the rule should be applied.
     * 签名如下
     * The signature of the callback should be as follows:
     *
     * ```php
     * function ($rule, $action)
     * ```
     *
     * where `$rule` is this rule, and `$action` is the current [[Action|action]] object.
     * The callback should return a boolean value indicating whether this rule should be applied.
     */
    public $matchCallback;
    /**
     * 如果经过规则的判断后是拒绝（或者）规则不匹配，则执行这个拒绝回调函数
     * @var callable a callback that will be called if this rule determines the access to
     * 如果这个回调函数没有设置，那么将交给上层的AccessController来处理
     * the current action should be denied. If not set, the behavior will be determined by
     * [[AccessControl]].
     *回调函数的签名如下
     * The signature of the callback should be as follows:
     *
     * ```php
     * function ($rule, $action)
     * ```
     *
     * where `$rule` is this rule, and `$action` is the current [[Action|action]] object.
     */
    public $denyCallback;


    /**
	 * 重点看，rules的规则验证顺序。
	 * action---->Role--->IP-->Verb-->Controller-->Custom
     * Checks whether the Web user is allowed to perform the specified action.
     * @param Action $action the action to be performed
     * @param User $user the user object
     * @param Request $request
     * @return bool|null true if the user is allowed, false if the user is denied, null if the rule does not apply to the user
     */
    public function allows($action, $user, $request)
    {
        //当前请求的动作匹配不？
        if ($this->matchAction($action)
            //角色适用否？
            && $this->matchRole($user)
            //IP地址适用否？
            && $this->matchIP($request->getUserIP())
            //HTTP请求方法合适不？
            && $this->matchVerb($request->getMethod())
            //所属的控制器适用不？
            && $this->matchController($action->controller)
            //用回调函数实现的自定义规则满足不？
            && $this->matchCustom($action)
        ) {
            //上头的几个条件都满足后，返回true还是false，还得看allow成员属性
            //allow为true,则返回true,根据代码可知，这就赋值到事件对象的isValid属性，进而影响程序后续的走向。
            //如果allow为false,则返回false,则意味着不能往下走了。程序不再去执行动作。
            //可见allow是一个开关。true，意味着是限制，规则全匹配到才能放行；false，意味着全匹配到的反而不放行。（就是常说的取反吧）
            return $this->allow ? true : false;
        } else {
            return null;
        }
    }

    /**
     * @param Action $action the action
     * @return bool whether the rule applies to the action
     */
    protected function matchAction($action)
    {
        return empty($this->actions) || in_array($action->id, $this->actions, true);
    }

    /**
     * @param Controller $controller the controller
     * @return bool whether the rule applies to the controller
     */
    protected function matchController($controller)
    {
        return empty($this->controllers) || in_array($controller->uniqueId, $this->controllers, true);
    }

    /**
     * @param User $user the user object
     * @return bool whether the rule applies to the role
     */
    protected function matchRole($user)
    {
        //为空，则所有角色都匹配
        if (empty($this->roles)) {
            //当然返回true就行了
            return true;
        }
        foreach ($this->roles as $role) {
            //要求是来宾用户，那就去检测是否是来宾
            if ($role === '?') {
                if ($user->getIsGuest()) {
                    return true;
                }
            //认证用户，那就去检测是否是认证用户
            } elseif ($role === '@') {
                if (!$user->getIsGuest()) {
                    return true;
                }
            //RBAC，就调用can方法去验证（这是Yii框架默认自带的rbac验证机制，Home并是这样的，home是独自写了个过滤器来完成的）
            } elseif ($user->can($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $ip the IP address
     * @return bool whether the rule applies to the IP address
     */
    protected function matchIP($ip)
    {
        //为空，直接返回true
        if (empty($this->ips)) {
            return true;
        }
        //支持通配符。
        //意外收获是，php原生函数strncmp可以二进制安全地比较两个字符串的前N个字符
        //方便不？学习了。
        foreach ($this->ips as $rule) {
            if ($rule === '*' || $rule === $ip || (($pos = strpos($rule, '*')) !== false && !strncmp($ip, $rule, $pos))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $verb the request method.
     * @return bool whether the rule applies to the request
     */
    protected function matchVerb($verb)
    {
        return empty($this->verbs) || in_array(strtoupper($verb), array_map('strtoupper', $this->verbs), true);
    }

    /**
     * @param Action $action the action to be performed
     * @return bool whether the rule should be applied
     */
    protected function matchCustom($action)
    {
        return empty($this->matchCallback) || call_user_func($this->matchCallback, $this, $action);
    }
}
