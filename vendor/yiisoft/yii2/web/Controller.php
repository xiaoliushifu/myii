<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\InlineAction;
use yii\helpers\Url;

/**
 * Controller就是web控制器的基础类
 * Controller is the base class of web controllers.
 *
 * For more details and usage information on Controller, see the [guide article on controllers](guide:structure-controllers).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Controller extends \yii\base\Controller
{
    /**
     * 是否开启csrf验证，默认就是true，开启
     * @var bool whether to enable CSRF validation for the actions in this controller.
     * CSRF validation is enabled only when both this property and [[\yii\web\Request::enableCsrfValidation]] are true.
     */
    public $enableCsrfValidation = true;
    /**
     * 数组，绑定到当前动作的参数
     * @var array the parameters bound to the current action.
     */
    public $actionParams = [];


    /**
     * 为响应AJAX请求而渲染一个视图（不用问，肯定与非ajax响应不同）
     * Renders a view in response to an AJAX request.
     *该方法类似于父类的renderPartial方法，不同点是。该方法会注入带有JS/CSS脚本和文件的渲染结果到视图中
     * This method is similar to [[renderPartial()]] except that it will inject into
     * the rendering result with JS/CSS scripts and files which are registered with the view.
     * 也是基于这个原因，开发人员应该使用这个方法而不是使用renderPartial方法来响应一个ajax请求的视图。
     * For this reason, you should use this method instead of [[renderPartial()]] to render
     * a view to respond to an AJAX request.
     *
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * 这只是一个快捷方式而已
     */
    public function renderAjax($view, $params = [])
    {
        //还是使用视图组件的方法
        return $this->getView()->renderAjax($view, $params, $this);
    }

    /**
     * 用JSON格式发送数据
     * Send data formatted as JSON.
     *该方法其实是一个快捷方式。
     * This method is a shortcut for sending data formatted as JSON. It will return
     * 在配置完响应组件Response的$format，设置完$data后，返回Response组件
     * the [[Application::getResponse()|response]] application component after configuring
     * the [[Response::$format|format]] and setting the [[Response::$data|data]] that should
     * 使用时如下：
     * be formatted. A common usage will be:
     *
     * ```php
     * return $this->asJson($data);
     * ```
     *
     * @param mixed $data the data that should be formatted.
     * @return Response a response that is configured to send `$data` formatted as JSON.
     * @since 2.0.11  哪个版本才有的
     * @see Response::$format
     * @see Response::FORMAT_JSON
     * @see JsonResponseFormatter
     */
    public function asJson($data)
    {
        $response = Yii::$app->getResponse();
        //设置格式
        $response->format = Response::FORMAT_JSON;
        //设置响应内容
        $response->data = $data;
        return $response;
    }

    /**
     * 按照XML格式发送数据（与上述asJSON类似，就不多说了）
     * Send data formatted as XML.
     *
     * This method is a shortcut for sending data formatted as XML. It will return
     * the [[Application::getResponse()|response]] application component after configuring
     * the [[Response::$format|format]] and setting the [[Response::$data|data]] that should
     * be formatted. A common usage will be:
     *
     * ```php
     * return $this->asXml($data);
     * ```
     *
     * @param mixed $data the data that should be formatted.
     * @return Response a response that is configured to send `$data` formatted as XML.
     * @since 2.0.11
     * @see Response::$format
     * @see Response::FORMAT_XML
     * @see XmlResponseFormatter
     */
    public function asXml($data)
    {
        $response = Yii::$app->getResponse();
        //设置格式
        $response->format = Response::FORMAT_XML;
        //设置数据
        $response->data = $data;
        return $response;
    }

    /**
     * 为动作绑定执行参数，这是控制器子类继承控制器基类必须实现的方法
     * Binds the parameters to the action.
     * 该方法在base\Action里调用
     * This method is invoked by [[\yii\base\Action]] when it begins to run with the given parameters.
     * 该方法检测动作需要的参数名，按需返回。
     * This method will check the parameter names that the action requires and return
     * 有任何丢失不见的参数，则报异常
     * the provided parameters according to the requirement. If there is any missing parameter,
     * an exception will be thrown.
     * @param \yii\base\Action $action the action to be bound with parameters  要被绑定参数的动作对象
     * @param array $params the parameters to be bound to the action  要被绑定的参数（根本来源是$_GET吧？）
     * @return array the valid parameters that the action can run with.  数组，里面是动作执行时有效的参数
     * @throws BadRequestHttpException if there are missing or invalid parameters. 参数无效或丢失参数时报异常
     */
    public function bindActionParams($action, $params)
    {
        //行内动作？
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
       //否则就是独立动作，二选一
       //无论是行内动作还是独立动作，都是用反射机制。
       //反射机制可以不用开发人员观看代码，就能在程序上知道这个方法的参数,从而可以正确地调用这个方法，这就是反射机制的厉害之处!
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        //初始化解析出参数的数组
        //索引数组的
        $args = [];
        //丢失参数的
        $missing = [];
        //关联数组的
        $actionParams = [];
        //反射对象的方法getParameters()，可以返回这个方法的形式参数列表，没有参数的返回空
        foreach ($method->getParameters() as $param) {
            //每个形式参数也是一个反射对象，进而再次调用它的方法getName()可以获得这个形参的名字
            $name = $param->getName();
            //方法需要的形参，是否在外部传递而来的参数里？
            if (array_key_exists($name, $params)) {
                //形参是数组吗？
                if ($param->isArray()) {
                    $args[] = $actionParams[$name] = (array) $params[$name];
               //不是二维数组（大多数方法会走这个分支）
                } elseif (!is_array($params[$name])) {
                    $args[] = $actionParams[$name] = $params[$name];
                //否则就要报异常了
                } else {
                    throw new BadRequestHttpException(Yii::t('yii', 'Invalid data received for parameter "{param}".', [
                        'param' => $name,
                    ]));
                }
                unset($params[$name]);
            //或者是这个形参有默认值？
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $actionParams[$name] = $param->getDefaultValue();
            } else {
            //最后就是没有这个参数了，丢失
                $missing[] = $name;
            }
        }
        //有丢失参数，就要报异常
        if (!empty($missing)) {
            throw new BadRequestHttpException(Yii::t('yii', 'Missing required parameters: {params}', [
                'params' => implode(', ', $missing),
            ]));
        }
        //关联数组，保存到控制器的参数里
        $this->actionParams = $actionParams;
        //返回索引数组参数
        return $args;
    }

    /**
     * beforeAction
     * @inheritdoc  csrf是在beforeAction中完成的。每个action都有判断
	 * csrf只是其次，重要的是通过parent::beforeAction()触发了BEFORE_ACTION_EVENT这个事件，进而引发了好多功能
     */
    public function beforeAction($action)
    {
        //触发父类，也就是base\Controller的beforeAction事件
        //yii\base\Controller注释里已经说明，必须调用父类的beforeAction，因为要触发beforeAction事件，该事件涉及了很多功能。
        if (parent::beforeAction($action)) {
            //开启csrf，没有其他异常或错误时，就去验证csrf
            if ($this->enableCsrfValidation && Yii::$app->getErrorHandler()->exception === null && !Yii::$app->getRequest()->validateCsrfToken()) {
                throw new BadRequestHttpException(Yii::t('yii', 'Unable to verify your data submission.'));
            }
            return true;
        }
        
        return false;
    }

    /**
     * 跳转到指定的URL
     * Redirects the browser to the specified URL.
     * 该方法是Response组件的方法redirect()的快捷方式
     * This method is a shortcut to [[Response::redirect()]].
     *可以直接在action中返回Response组件
     * You can use it in an action by returning the [[Response]] directly:
     *
     * ```php
     * 停止执行action往后的代码，跳转到登录页
     * // stop executing this action and redirect to login page
     * return $this->redirect(['login']);
     * ```
     *跳转的URL格式如下：
     * @param string|array $url the URL to be redirected to. This can be in one of the following formats:
     *
     * - a string representing a URL (e.g. "http://example.com")   字符串，绝对URL(以http://开头的）
     * - a string representing a URL alias (e.g. "@example.com")  字符串，URL别名
     * - an array in the format of `[$route, ...name-value pairs...]` (e.g. `['site/index', 'ref' => 1]`) 数组，格式自己看
     *   [[Url::to()]] will be used to convert the array into a URL. 内容将会使用Url::to()来转换成URL
     *任何相对URL都会增加本次http请求里的host头字段值作为前缀，使之成为绝对URL
     * Any relative URL will be converted into an absolute one by prepending it with the host info
     * of the current request.
     *
     * @param int $statusCode the HTTP status code. Defaults to 302.
     * See <http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html>
     * for details about HTTP status code
     * 返回Response组件对象
     * @return Response the current response object
     */
    public function redirect($url, $statusCode = 302)
    {
        //最终还是靠Response组件的redirect方法完成的，果然控制器里的这个就是个快捷方式
        return Yii::$app->getResponse()->redirect(Url::to($url), $statusCode);
    }

    /**把浏览器重定向到主页（快捷方式）
     * Redirects the browser to the home page.
     *
     * You can use this method in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and redirect to home page
     * return $this->goHome();
     * ```
     *
     * @return Response the current response object
     */
    public function goHome()
    {
        //最终还是由Response组件完成的，快捷方式
        return Yii::$app->getResponse()->redirect(Yii::$app->getHomeUrl());
    }

    /**
     * 重定向到上一个请求的URL
     * Redirects the browser to the last visited page.
     *
     * You can use this method in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and redirect to last visited page
     * return $this->goBack();
     * ```
     *为了起作用，必须事先在合适的地方调用setReturnUrl完成设置要跳转的URL才行。
     * For this function to work you have to [[User::setReturnUrl()|set the return URL]] in appropriate places before.
     *
     * @param string|array $defaultUrl the default return URL in case it was not set previously.
     * 如果为null或没有设置返回url，则用主页URL（即goback将会跳转到主页）
     * If this is null and the return URL was not set previously, [[Application::homeUrl]] will be redirected to.
     * 请参考User组件来查看如何设置return URL
     * Please refer to [[User::setReturnUrl()]] on accepted format of the URL.
     * @return Response the current response object
     * @see User::getReturnUrl()
     */
    public function goBack($defaultUrl = null)
    {
        return Yii::$app->getResponse()->redirect(Yii::$app->getUser()->getReturnUrl($defaultUrl));
    }

    /**
     * 刷新当前请求
     * Refreshes the current page.
     * 该方法是Response::refresh()的快捷方式
     * This method is a shortcut to [[Response::refresh()]].
     *
     * You can use it in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and refresh the current page
     * return $this->refresh();
     * ```
     *
     * @param string $anchor the anchor that should be appended to the redirection URL.
     * Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
     * @return Response the response object itself
     */
    public function refresh($anchor = '')
    {
        return Yii::$app->getResponse()->redirect(Yii::$app->getRequest()->getUrl() . $anchor);
    }
}
