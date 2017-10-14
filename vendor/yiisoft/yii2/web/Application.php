<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\helpers\Url;
use yii\base\InvalidRouteException;

/**
 * WebApplication 是所有web应用类的基类
 * Application is the base class for all web application classes.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 *ErrorHandler  应用主体的错误处理器组件，只读，组件都是php对象（类）
 * @property ErrorHandler $errorHandler The error handler application component. This property is read-only.
 * $homeUrl 主页的URL
 * @property string $homeUrl The homepage URL.
 * $request  请求组件，只读
 * @property Request $request The request component. This property is read-only.
 * $response  响应组件，只读
 * @property Response $response The response component. This property is read-only.
 * $session   session组件，只读
 * @property Session $session The session component. This property is read-only.
 * $user        User组件，只读
 * @property User $user The user component. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Application extends \yii\base\Application
{
    /**
     * 默认的路由，是"site"
     * @var string the default route of this application. Defaults to 'site'.
     */
    public $defaultRoute = 'site';
    /**
     * 数组形式的配置信息，用来指定一个控制器和动作，这将处理所有用户的请求。
     * @var array the configuration specifying a controller action which should handle
     * 当应用处于维护模式，需要指定一个动作来处理所有来自外部的请求时，才用得到
     * all user requests. This is mainly used when the application is in maintenance mode
     * and needs to handle all incoming requests via a single action.
     * 配置信息是个数组，第一个元素就是控制器和动作的路由信息
     * The configuration is an array whose first element specifies the route of the action.
     * 其他的元素（键值对）则是传递给这个路由动作的参数，如下：
     * The rest of the array elements (key-value pairs) specify the parameters to be bound
     * to the action. For example,
     *
     * ```php
     * [
     *     'offline/notice',  //路由信息
     *     'param1' => 'value1',  //notice方法的参数1
     *     'param2' => 'value2',  //notice方法的参数2
     * ]
     * ```
     *默认是null,表示未处在维护模式
     * Defaults to null, meaning catch-all is not used.
     */
    public $catchAll;
    /**
     * 当前处于激活状态的控制器
     * @var Controller the currently active controller instance
     */
    public $controller;


    /**
     * 在完成应用主体的实例化后，需要立即启动的组件或（模块）
     * @inheritdoc
     */
    protected function bootstrap()
    {
        //把request组件取出来，就为了设置两个别名吗，是的，这是web\application,不是console\application。
        $request = $this->getRequest();
        Yii::setAlias('@webroot', dirname($request->getScriptFile()));//可能是/
        Yii::setAlias('@web', $request->getBaseUrl());//可能是空字符串
        //这才是关键的一步，由父类完成引导
        parent::bootstrap();
    }

    /**
     * 请求的处理，就是从应用主体的这个方法开始的
     * Handles the specified request.
     * @param Request $request the request to be handled  Request组件
     * @return Response the resulting response
     * @throws NotFoundHttpException if the requested route is invalid
     */
    public function handleRequest($request)
    {
        //维护模式否？，一般都是null
        if (empty($this->catchAll)) {
            try {
                //解析请求时，获得原始的路由与请求参数(待学习Request时再细看）
                //无论原始请求URL是什么格式
                //（http://xxx.com/index.php?r=site/login)，
                //（http://xxx.com/site/login)，
                //都会把路由解析成 “控制器ID/动作ID"的格式
                list ($route, $params) = $request->resolve();
            } catch (UrlNormalizerRedirectException $e) {
                //这个异常，这么诡异，竟然没有报错这一说
                $url = $e->url;
                if (is_array($url)) {
                    if (isset($url[0])) {
                        // ensure the route is absolute
                        $url[0] = '/' . ltrim($url[0], '/');
                    }
                    $url += $request->getQueryParams();
                }
                return $this->getResponse()->redirect(Url::to($url, $e->scheme), $e->statusCode);
            }
        //维护模式时,无论外界http的请求路由是什么，在服务端都指定一个固定写死的路由来处理
        } else {
            //第一个元素就是路由信息（路由就是   "控制器/动作"的字符串)
            $route = $this->catchAll[0];
            //其他就是动作参数，先获得整个配置信息
            $params = $this->catchAll;
            //然后删除第一个元素
            unset($params[0]);
        }
        try {
            //打个日志
            Yii::trace("Route requested: '$route'", __METHOD__);
            //全局保存本次http请求的路由,字符串
            $this->requestedRoute = $route;
            //应用主体在搞定了路由之后，从这里开始真正处理http请求了
            //包含实例化控制器，再由控制器实例化动作对象，再去执行动作对象关联的方法，最后由视图组件渲染视图，返回渲染后的结果（字符串或响应组件对象Response）
            $result = $this->runAction($route, $params);
            //如果$result是个response组件对象，则直接返回。从整个流程来看，这里应该是response组件正式开始工作的地方吧？
            //之前的地方可能会出现echo,var_dump,print等，但都会因为开启输出缓存机制，而临时存储在php的输出缓存区里，并不是直接交给web服务器
            //请问，在什么时候开启的输出缓存机制？
            //经过一番自己的寻找，在到达视图解析之前根本就没有开启输出缓存(ob_start)，是"程序缓存"在作怪而已！
            if ($result instanceof Response) {
                return $result;
            } else {
            //否则就找来response组件，填充data成员，开始后续的格式化，header,cookie,content的处理。
                $response = $this->getResponse();
                //有可能$result就是个null呢，比如某个action没有返回值（php默认就是返回null)，或者明显地返回null
                if ($result !== null) {
                    //$result从这里赋值给response的data属性
                    $response->data = $result;
                }
                //无论如何，不是返回字符串，而是response组件
                return $response;
            }
        } catch (InvalidRouteException $e) {
            //搞定路由后，如果在处理请求的时候出现问题而报出的异常，多半是控制器找不到，方法没有，访问权限不对等等
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'), $e->getCode(), $e);
        }
    }

    //这个属性，为啥在这里声明呢？虽然语法上没有什么问题
    private $_homeUrl;

    /**
     * 返回主页的URL
     * 根据源码可知主页URL的来源
     * @return string the homepage URL
     */
    public function getHomeUrl()
    {
        if ($this->_homeUrl === null) {
            //urlManager组件给出主页url，尚对urlManager不熟
            if ($this->getUrlManager()->showScriptName) {
                return $this->getRequest()->getScriptUrl();
            } else {
                //通过request组件来获得主页url
                return $this->getRequest()->getBaseUrl() . '/';
            }
        } else {
            return $this->_homeUrl;
        }
    }

    /**
     * 也可以主动设置主页URL
     * @param string $value the homepage URL
     */
    public function setHomeUrl($value)
    {
        $this->_homeUrl = $value;
    }

    /**
     * 同理都是为__get魔术方法而用:Yii::$app->errorHandler
     * Returns the error handler component.
     * @return ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }

    /**
     * 同理都是为__get魔术方法而用:Yii::$app->request
     * Returns the request component.
     * @return Request the request component.
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * 同理都是为__get魔术方法而用:Yii::$app->response
     * Returns the response component.
     * @return Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * 同理都是为__get魔术方法而用:Yii::$app->session
     * Returns the session component.
     * @return Session the session component.
     */
    public function getSession()
    {
        return $this->get('session');
    }

    /**
     * 一般由Yii::$app->user开始的get魔术方法调用，但该方法既然是public，那我们既然知道了就可以提供效率地直接Yii::$app->getUser()
     * Returns the user component.
     * @return User the user component.
     */
    public function getUser()
    {
        return $this->get('user');
    }

    /**
     * @inheritdoc
     */
    public function coreComponents()
    {
        //核心组件是所有应用主体（web,console,api等）必会使用得到的组件
        //web\Application这个具体的应用主体还需要request,response,session等这些个组件
        //所以这是抽象应用主体（base\Application)和具体应用主体(Web\Application)组件的组合
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => 'yii\web\Request'],
            'response' => ['class' => 'yii\web\Response'],
            'session' => ['class' => 'yii\web\Session'],
            'user' => ['class' => 'yii\web\User'],
            'errorHandler' => ['class' => 'yii\web\ErrorHandler'],
        ]);
    }
}
