<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Controller is the base class for classes containing controller logic.
 *
 * For more details and usage information on Controller, see the [guide article on controllers](guide:structure-controllers).
 *
 * @property Module[] $modules All ancestor modules that this controller is located within. This property is
 * read-only.
 * @property string $route The route (module ID, controller ID and action ID) of the current request. This
 * property is read-only.
 * @property string $uniqueId The controller ID that is prefixed with the module ID (if any). This property is
 * read-only.
 * @property View|\yii\web\View $view The view object that can be used to render views or view files.
 * @property string $viewPath The directory containing the view files for this controller.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Controller extends Component implements ViewContextInterface
{
    /**
     * @event ActionEvent an event raised right before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be false to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised right after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * @var string the ID of this controller.
     */
    public $id;
    /**
     * @var Module the module that this controller belongs to.
     */
    public $module;
    /**
     * @var string the ID of the action that is used when the action ID is not specified
     * in the request. Defaults to 'index'.
     */
    public $defaultAction = 'index';
    /**
     * null或者字符串或者false。指明用于当前控制器的布局视图名称
     * @var null|string|false the name of the layout to be applied to this controller's views.
     * 这个属性成员主要影响render()方法的行为
     * This property mainly affects the behavior of [[render()]].
     * 默认就是null,意味着实际的layout值应该继承自module对象的layout值
     * Defaults to null, meaning the actual layout value should inherit that from [[module]]'s layout value.
     * false则说明无需应用布局文件
     * If false, no layout will be applied.
     */
    public $layout;
    /**
     * @var Action the action that is currently being executed. This property will be set
     * by [[run()]] when it is called by [[Application]] to run an action.
     */
    public $action;

    /**
     * @var View the view object that can be used to render views or view files.
     */
    private $_view;
    /**
     * @var string the root directory that contains view files for this controller.
     */
    private $_viewPath;


    /**
     * @param string $id the ID of this controller.
     * @param Module $module the module that this controller belongs to.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($id, $module, $config = [])
    {
        $this->id = $id;
        $this->module = $module;
        parent::__construct($config);
    }

    /**
     * Declares external actions for the controller.
     * This method is meant to be overwritten to declare external actions for the controller.
     * It should return an array, with array keys being action IDs, and array values the corresponding
     * action class names or action configuration arrays. For example,
     *
     * ```php
     * return [
     *     'action1' => 'app\components\Action1',
     *     'action2' => [
     *         'class' => 'app\components\Action2',
     *         'property1' => 'value1',
     *         'property2' => 'value2',
     *     ],
     * ];
     * ```
     *
     * [[\Yii::createObject()]] will be used later to create the requested action
     * using the configuration provided here.
     */
    public function actions()
    {
        return [];
    }

    /**
     * Runs an action within this controller with the specified action ID and parameters.
     * If the action ID is empty, the method will use [[defaultAction]].
     * @param string $id the ID of the action to be executed.
     * @param array $params the parameters (name-value pairs) to be passed to the action.
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
     * @see createAction()
     */
    public function runAction($id, $params = [])
    {
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }

        Yii::trace('Route to run: ' . $action->getUniqueId(), __METHOD__);

        if (Yii::$app->requestedAction === null) {
            Yii::$app->requestedAction = $action;
        }

        $oldAction = $this->action;
        $this->action = $action;

        $modules = [];
        $runAction = true;

        // call beforeAction on modules  beforeAction也分级别哟。module级别的。下面是Controller级别的
        //无论哪一级别，因为它们都是Component的子类，所以共用一套事件代码
        foreach ($this->getModules() as $module) {
            if ($module->beforeAction($action)) {
                array_unshift($modules, $module);
            } else {
                $runAction = false;
                break;
            }
        }

        $result = null;
        //过滤器由beforeAction里的beforeAction事件触发而发挥作用
        if ($runAction && $this->beforeAction($action)) {
            // run the action，普通视图的渲染，布局视图的渲染完成，基本上就是一个action的结束了。
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

            // call afterAction on modules
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }

        if ($oldAction !== null) {
            $this->action = $oldAction;
        }

        return $result;
    }

    /**
     * Runs a request specified in terms of a route.
     * The route can be either an ID of an action within this controller or a complete route consisting
     * of module IDs, controller ID and action ID. If the route starts with a slash '/', the parsing of
     * the route will start from the application; otherwise, it will start from the parent module of this controller.
     * @param string $route the route to be handled, e.g., 'view', 'comment/view', '/admin/comment/view'.
     * @param array $params the parameters to be passed to the action.
     * @return mixed the result of the action.
     * @see runAction()
     */
    public function run($route, $params = [])
    {
        $pos = strpos($route, '/');
        if ($pos === false) {
            return $this->runAction($route, $params);
        } elseif ($pos > 0) {
            return $this->module->runAction($route, $params);
        }
        return Yii::$app->runAction(ltrim($route, '/'), $params);
    }

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[Action]] when it begins to run with the given parameters.
     * @param Action $action the action to be bound with parameters.
     * @param array $params the parameters to be bound to the action.
     * @return array the valid parameters that the action can run with.
     */
    public function bindActionParams($action, $params)
    {
        return [];
    }

    /**
     * Creates an action based on the given action ID.
     * The method first checks if the action ID has been declared in [[actions()]]. If so,
     * it will use the configuration declared there to create the action object.
     * If not, it will look for a controller method whose name is in the format of `actionXyz`
     * where `Xyz` stands for the action ID. If found, an [[InlineAction]] representing that
     * method will be created and returned.
     * @param string $id the action ID.
     * @return Action the newly created action instance. Null if the ID doesn't resolve into any action.
     */
    public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }

        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Yii::createObject($actionMap[$id], [$id, $this]);
        } elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }

    /**
     * This method is invoked right before an action is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     // your custom code here, if you want the code to run before action filters,
     *     // which are triggered on the [[EVENT_BEFORE_ACTION]] event, e.g. PageCache or AccessControl
     *
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // other custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to run.
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action is executed.
     *
     * The method will trigger the [[EVENT_AFTER_ACTION]] event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action the action just executed.
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        return $event->result;
    }

    /**
     * Returns all ancestor modules of this controller.
     * The first module in the array is the outermost one (i.e., the application instance),
     * while the last is the innermost one.
     * @return Module[] all ancestor modules that this controller is located within.
     */
    public function getModules()
    {
        $modules = [$this->module];
        $module = $this->module;
        while ($module->module !== null) {
            array_unshift($modules, $module->module);
            $module = $module->module;
        }
        return $modules;
    }

    /**
     * Returns the unique ID of the controller.
     * @return string the controller ID that is prefixed with the module ID (if any).
     */
    public function getUniqueId()
    {
        return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . '/' . $this->id;
    }

    /**
     * Returns the route of the current request.
     * @return string the route (module ID, controller ID and action ID) of the current request.
     */
    public function getRoute()
    {
        return $this->action !== null ? $this->action->getUniqueId() : $this->getUniqueId();
    }

    /**
     * Renders a view and applies layout if available.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - path alias (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of [[module]].
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     *
     * To determine which layout should be applied, the following two steps are conducted:
     *
     * 1. In the first step, it determines the layout name and the context module:
     *
     * - If [[layout]] is specified as a string, use it as the layout name and [[module]] as the context module;
     * - If [[layout]] is null, search through all ancestor modules of this controller and find the first
     *   module whose [[Module::layout|layout]] is not null. The layout and the corresponding module
     *   are used as the layout name and the context module, respectively. If such a module is not found
     *   or the corresponding layout is not a string, it will return false, meaning no applicable layout.
     *
     * 2. In the second step, it determines the actual layout file according to the previously found layout name
     *    and context module. The layout name can be:
     *
     * - a path alias (e.g. "@app/views/layouts/main");
     * - an absolute path (e.g. "/main"): the layout name starts with a slash. The actual layout file will be
     *   looked for under the [[Application::layoutPath|layout path]] of the application;
     * - a relative path (e.g. "main"): the actual layout file will be looked for under the
     *   [[Module::layoutPath|layout path]] of the context module.
     *
     * If the layout name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * These parameters will not be available in the layout.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file or the layout file does not exist.
     */
    public function render($view, $params = [])
    {
        //得到View组件，然后调用其render方法来渲染普通视图
        //并且增加了第三个参数$this,这就是视图处理过程中的上下文对象，也就是控制器对象
        $content = $this->getView()->render($view, $params, $this);
        //把渲染普通视图文件后的内容，当做参数传递进来，进而再去渲染布局视图
        return $this->renderContent($content);
    }

    /**通过应用一个布局文件来渲染一个静态字符串
     * Renders a static string by applying a layout.
     * @param string $content the static string being rendered  $content就是那个静态字符串（刚刚从视图中渲染而来）
     * 返回的内容，就是渲染布局文件后的结果，布局文件中有$content变量，就是用来替换参数$content的。
     * @return string the rendering result of the layout with the given static string as the `$content` variable.
     * If the layout is disabled, the string will be returned back.如果布局文件不启用。直接返回$content就行了，不用布局文件
     * 这，是不是某些ajax返回时无需布局视图，此时就可以直接返回结果，无需再次去寻找布局文件来渲染了。
     * @since 2.0.1
     */
    public function renderContent($content)
    {
        $layoutFile = $this->findLayoutFile($this->getView());
        if ($layoutFile !== false) {
            //布局视图，也是视图，所以还是继续使用View组件的renderFile方法来渲染呗，注意传递的第二个参数，仅仅就是刚刚普通视图渲染后的结果字符串$content
            return $this->getView()->renderFile($layoutFile, ['content' => $content], $this);
        }
        return $content;
    }

    /**
     * Renders a view without applying layout.
     * This method differs from [[render()]] in that it does not apply any layout.
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderPartial($view, $params = [])
    {
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * Renders a view file.
     * @param string $file the view file to be rendered. This can be either a file path or a path alias.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderFile($file, $params = [])
    {
        return $this->getView()->renderFile($file, $params, $this);
    }

    /**返回View组件，控制器哪里来的View组件，当然也是从$app应用对象而来了
     * Returns the view object that can be used to render views or view files.
     * The [[render()]], [[renderPartial()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     * @return View|\yii\web\View the view object that can be used to render views or view files.
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }
        return $this->_view;
    }

    /**
     * Sets the view object to be used by this controller.
     * @param View|\yii\web\View $view the view object that can be used to render views or view files.
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
     * Returns the directory containing view files for this controller.
     * The default implementation returns the directory named as controller [[id]] under the [[module]]'s
     * [[viewPath]] directory.
     * @return string the directory containing the view files for this controller.
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        }
        return $this->_viewPath;
    }

    /**
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidParamException if the directory is invalid
     * @since 2.0.7
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    /**找到应用的布局文件
     * Finds the applicable layout file.
     * @param View $view the view object to render the layout file.   $view是yii\web\view视图对象
     * @return string|bool the layout file path, or false if layout is not needed.返回字符串(布局文件路径）或布尔false,表示无需布局视图
     * Please refer to [[render()]] on how to specify this parameter.请参考render方法了解如何传递参数
     * @throws InvalidParamException if an invalid path alias is used to specify the layout.
     */
    public function findLayoutFile($view)
    {
        //此时控制器的module一般就是yii\web\Application，也就是所谓的$app了
        $module = $this->module;
        //当前控制器指定了布局文件
        if (is_string($this->layout)) {
            $layout = $this->layout;
        //默认当前控制器不指定布局文件的话，将是null
        } elseif ($this->layout === null) {
            //此时将从模块对象（yii\web\Application)上的layout成员属性上找布局文件，如果还没有，就再次向上去找
            while ($module !== null && $module->layout === null) {
                $module = $module->module;
            }
            //对basic来说，就是从这里找到的main视图
            if ($module !== null && is_string($module->layout)) {
                $layout = $module->layout;
            }
        }

        if (!isset($layout)) {
            return false;
        }
        //布局视图文件，也有可能是别名
        if (strncmp($layout, '@', 1) === 0) {
            $file = Yii::getAlias($layout);
        //是以单斜杠开头的路径
        } elseif (strncmp($layout, '/', 1) === 0) {
            $file = Yii::$app->getLayoutPath() . DIRECTORY_SEPARATOR . substr($layout, 1);
        } else {
            //有了布局视图名，然后根据路径，返回绝对路径（类似于普通视图找路径的逻辑）
            //布局视图文件是属于整个模块对象的，它将应用于整个模块下的控制器，所以是在模块的方法里找到布局视图文件。
            //比如，返回内容是：  "D:\wamp64\www\basic\views\layouts\main"
            $file = $module->getLayoutPath() . DIRECTORY_SEPARATOR . $layout;
        }
        //类似地，有扩展名就直接返回（这也说明了，我们完全可以在控制器的操作方法里的最后阶段返回视图时，视图文件的参数，完全可以自带扩展名
        //还能提高一点性能呢
        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        //加上视图组件成员defaultExtension指明的扩展后缀
        $path = $file . '.' . $view->defaultExtension;
        if ($view->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }
}
