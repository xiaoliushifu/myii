<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\UserException;

/**
 * ErrorAction类是为了使用指定的视图来展示应用主体的错误，
 * ErrorAction displays application errors using a specified view.
 *
 *使用ErrorAction类时，需走如下的步骤：
 * To use ErrorAction, you need to do the following steps:
 *首先，在控制器的actions()里声明一个ErrorAction类型的动作。比如在SiteController里
 * First, declare an action of ErrorAction type in the `actions()` method of your `SiteController`
 * 其他控制器里声明也是可以的
 * class (or whatever controller you prefer), like the following:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'error' => ['class' => 'yii\web\ErrorAction'],
 *     ];
 * }
 * ```
 *然后，创建为这个动作创建一个视图，如果路由是site/error,那么这个视图文件就是views/site/error.php
 * Then, create a view file for this action. If the route of your error action is `site/error`, then
 * 在这个视图里，有这么几个变量要说一下：
 * the view file should be `views/site/error.php`. In this view file, the following variables are available:
 *
 * - `$name`: the error name  错误名
 * - `$message`: the error message 错误消息
 * - `$exception`: the exception being handled  异常
 *最后，配置一个errorHandler组件如下：
 * Finally, configure the "errorHandler" application component as follows,
 *
 * ```php
 * 'errorHandler' => [
 *     'errorAction' => 'site/error',
 * ]
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0
 */
class ErrorAction extends Action
{
    /**
     * 视图名，如果没有设置这个视图名称，那么将取id的值
     * @var string the view file to be rendered. If not set, it will take the value of [[id]].
     * 这意味着，如果你命名这个action为error,那么这个视图名就是error。
     * That means, if you name the action as "error" in "SiteController", then the view name
     * 对应的视图文件就是"views/site/error.php".
     * would be "error", and the corresponding view file would be "views/site/error.php".
     */
    public $view;
    /**
     * @var string the name of the error when the exception name cannot be determined.
     * Defaults to "Error".
     */
    public $defaultName;
    /**
     * @var string the message to be displayed when the exception message contains sensitive information.
     * Defaults to "An internal server error occurred.".
     */
    public $defaultMessage;

    /**
     * @var \Exception the exception object, normally is filled on [[init()]] method call.
     * @see [[findException()]] to know default way of obtaining exception.
     * @since 2.0.11
     */
    protected $exception;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->exception = $this->findException();

        if ($this->defaultMessage === null) {
            $this->defaultMessage = Yii::t('yii', 'An internal server error occurred.');
        }

        if ($this->defaultName === null) {
            $this->defaultName = Yii::t('yii', 'Error');
        }
    }

    /**
     * 独立动作，特有的一个方法就是run
     * Runs the action.
     *
     * @return string result content
     */
    public function run()
    {
        if (Yii::$app->getRequest()->getIsAjax()) {
            return $this->renderAjaxResponse();
        }

        return $this->renderHtmlResponse();
    }

    /**
     * Builds string that represents the exception.
     * Normally used to generate a response to AJAX request.
     * @return string
     * @since 2.0.11
     */
    protected function renderAjaxResponse()
    {
        return $this->getExceptionName() . ': ' . $this->getExceptionMessage();
    }

    /**
     * Renders a view that represents the exception.
     * @return string
     * @since 2.0.11
     */
    protected function renderHtmlResponse()
    {
        return $this->controller->render($this->view ?: $this->id, $this->getViewRenderParams());
    }

    /**
     * Builds array of parameters that will be passed to the view.
     * @return array
     * @since 2.0.11
     */
    protected function getViewRenderParams()
    {
        return [
            'name' => $this->getExceptionName(),
            'message' => $this->getExceptionMessage(),
            'exception' => $this->exception,
        ];
    }

    /**
     * Gets exception from the [[yii\web\ErrorHandler|ErrorHandler]] component.
     * In case there is no exception in the component, treat as the action has been invoked
     * not from error handler, but by direct route, so '404 Not Found' error will be displayed.
     * @return \Exception
     * @since 2.0.11
     */
    protected function findException()
    {
        if (($exception = Yii::$app->getErrorHandler()->exception) === null) {
            $exception = new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }

        return $exception;
    }

    /**
     * Gets the code from the [[exception]].
     * @return mixed
     * @since 2.0.11
     */
    protected function getExceptionCode()
    {
        if ($this->exception instanceof HttpException) {
            return $this->exception->statusCode;
        }

        return $this->exception->getCode();
    }

    /**
     * Returns the exception name, followed by the code (if present).
     *
     * @return string
     * @since 2.0.11
     */
    protected function getExceptionName()
    {
        if ($this->exception instanceof Exception) {
            $name = $this->exception->getName();
        } else {
            $name = $this->defaultName;
        }

        if ($code = $this->getExceptionCode()) {
            $name .= " (#$code)";
        }

        return $name;
    }

    /**
     * Returns the [[exception]] message for [[yii\base\UserException]] only.
     * For other cases [[defaultMessage]] will be returned.
     * @return string
     * @since 2.0.11
     */
    protected function getExceptionMessage()
    {
        if ($this->exception instanceof UserException) {
            return $this->exception->getMessage();
        }

        return $this->defaultMessage;
    }
}
