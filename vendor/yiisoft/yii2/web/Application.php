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
 * Application is the base class for all web application classes.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 *
 * @property ErrorHandler $errorHandler The error handler application component. This property is read-only.
 * @property string $homeUrl The homepage URL.
 * @property Request $request The request component. This property is read-only.
 * @property Response $response The response component. This property is read-only.
 * @property Session $session The session component. This property is read-only.
 * @property User $user The user component. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Application extends \yii\base\Application
{
    /**
     * @var string the default route of this application. Defaults to 'site'.
     */
    public $defaultRoute = 'site';
    /**
     * @var array the configuration specifying a controller action which should handle
     * all user requests. This is mainly used when the application is in maintenance mode
     * and needs to handle all incoming requests via a single action.
     * The configuration is an array whose first element specifies the route of the action.
     * The rest of the array elements (key-value pairs) specify the parameters to be bound
     * to the action. For example,
     *
     * ```php
     * [
     *     'offline/notice',
     *     'param1' => 'value1',
     *     'param2' => 'value2',
     * ]
     * ```
     *
     * Defaults to null, meaning catch-all is not used.
     */
    public $catchAll;
    /**
     * @var Controller the currently active controller instance
     */
    public $controller;


    /**
     * @inheritdoc
     */
    protected function bootstrap()
    {
        //把request组件取出来，就为了设置两个别名吗，是的，这是web\application,不是console\application。
        $request = $this->getRequest();
        Yii::setAlias('@webroot', dirname($request->getScriptFile()));
        Yii::setAlias('@web', $request->getBaseUrl());
        //再去引导其他
        parent::bootstrap();
    }

    /**
     * Handles the specified request.
     * @param Request $request the request to be handled
     * @return Response the resulting response
     * @throws NotFoundHttpException if the requested route is invalid
     */
    public function handleRequest($request)
    {
        if (empty($this->catchAll)) {
            try {
                list ($route, $params) = $request->resolve();
            } catch (UrlNormalizerRedirectException $e) {
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
        } else {
            $route = $this->catchAll[0];
            $params = $this->catchAll;
            unset($params[0]);
        }
        try {
            Yii::trace("Route requested: '$route'", __METHOD__);
            $this->requestedRoute = $route;
            //从action中的返回的$result
            $result = $this->runAction($route, $params);
            //如果是个response组件对象，则直接返回。从整个流程来看，这里应该是response组件正式开始工作的地方吧？
            //之前的地方可能会出现echo,var_dump,print等，但都会因为开启输出缓存机制，而临时存储在php的输出缓存区里，并不是直接交给web服务器
            if ($result instanceof Response) {
                return $result;
            } else {
            //否则就找来response组件，填充data成员，开始后续的格式化，header,cookie,content的处理。
                $response = $this->getResponse();
                if ($result !== null) {
                    //$result从这里赋值给response的data属性
                    $response->data = $result;
                }
                //无论如何，不是返回字符串，而是response组件
                return $response;
            }
        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'), $e->getCode(), $e);
        }
    }

    private $_homeUrl;

    /**
     * @return string the homepage URL
     */
    public function getHomeUrl()
    {
        if ($this->_homeUrl === null) {
            if ($this->getUrlManager()->showScriptName) {
                return $this->getRequest()->getScriptUrl();
            } else {
                return $this->getRequest()->getBaseUrl() . '/';
            }
        } else {
            return $this->_homeUrl;
        }
    }

    /**
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
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => 'yii\web\Request'],
            'response' => ['class' => 'yii\web\Response'],
            'session' => ['class' => 'yii\web\Session'],
            'user' => ['class' => 'yii\web\User'],
            'errorHandler' => ['class' => 'yii\web\ErrorHandler'],
        ]);
    }
}
