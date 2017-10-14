<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * 控制器是包含控制器逻辑的基类
 * Controller is the base class for classes containing controller logic.
 *
 * For more details and usage information on Controller, see the [guide article on controllers](guide:structure-controllers).
 *$modules 所有祖先模块，当前控制器所属的模块，只读
 * @property Module[] $modules All ancestor modules that this controller is located within. This property is
 * read-only.
 * $route路由  包含（模块ID,控制器ID,动作ID)。当前http请求的路由。只读
 * @property string $route The route (module ID, controller ID and action ID) of the current request. This
 * property is read-only.
 * $uniqueId  带有模块前缀的控制器ID  只读
 * @property string $uniqueId The controller ID that is prefixed with the module ID (if any). This property is
 * read-only.
 * $view  视图对象，用来渲染html或视图文件的
 * @property View|\yii\web\View $view The view object that can be used to render views or view files.
 * $viewPath  视图目录，为当前控制器包含视图文件的目录（一般每个控制器都有自己独立的视图目录）
 * @property string $viewPath The directory containing the view files for this controller.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Controller extends Component implements ViewContextInterface
{
    /**
     * 事件名字，该事件就是要在执行控制器动作之前触发
     * @event ActionEvent an event raised right before executing a controller action.
     * 注意，是beforeAction，不是beforeController。可见，控制器主要就是使用【动作】这个基本单元来处理http请求的
     * You may set [[ActionEvent::isValid]] to be false to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**事件
     * @event ActionEvent an event raised right after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * 控制器的ID
     * @var string the ID of this controller.
     */
    public $id;
    /**
     * 控制器所属的模块（理解这个有助于理解应用主体和模块的关系）
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
     * 当前正在处理的inlineAction对象，默认是null
     * @var Action the action that is currently being executed. This property will be set
     * by [[run()]] when it is called by [[Application]] to run an action.
     */
    public $action;

    /**
     * 视图对象
     * @var View the view object that can be used to render views or view files.
     */
    private $_view;
    /**
     * 当前控制器的视图路径（每个控制器的视图路径都不一样的）
     * @var string the root directory that contains view files for this controller.
     */
    private $_viewPath;


    /**
     * @param string $id the ID of this controller. 控制器ID
     * @param Module $module the module that this controller belongs to. 控制器所属的模块
     * @param array $config name-value pairs that will be used to initialize the object properties. 初始化控制器对象的配置
     */
    public function __construct($id, $module, $config = [])
    {
        $this->id = $id;
        $this->module = $module;
        parent::__construct($config);
    }

    /**
     * 为控制器声明外部的动作（独立动作，不是actionXXXX的那种操作）
     * Declares external actions for the controller.
     * This method is meant to be overwritten to declare external actions for the controller.
     * 应该返回一个数组，数组的key是操作名称（可任意命名），数组的值是类名或可配置数组。比如：
     * It should return an array, with array keys being action IDs, and array values the corresponding
     * action class names or action configuration arrays. For example,
     *
     * ```php
     * return [
     *     'action1' => 'app\components\Action1',//独立操作的类名
     *     'action2' => [
     *         'class' => 'app\components\Action2',//配置数组
     *         'property1' => 'value1',
     *         'property2' => 'value2',
     *     ],
     * ];
     * ```
     *独立动作应该指明一个类，一般这种操作可以由多个控制器共用，actionXXX这种的称为内联动作，内联动作一般只属于某个控制器
     * [[\Yii::createObject()]] will be used later to create the requested action
     * using the configuration provided here.
     */
    public function actions()
    {
        return [];
    }

    /**
     * 执行动作方法，根据动作ID和传递的参数（控制器根据路由实例化后，就是以这个方法为入口开始干活的）
     * Runs an action within this controller with the specified action ID and parameters.
     * 如果动作ID为空，则使用默认动作[[defaultAction]].
     * If the action ID is empty, the method will use [[defaultAction]].
     * @param string $id the ID of the action to be executed.
     * @param array $params the parameters (name-value pairs) to be passed to the action.
     * @return mixed the result of the action.  返回动作执行的结果，一般是渲染后的HTML
     * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
     * @see createAction()  参考createAction()方法
     */
    public function runAction($id, $params = [])
    {
        //获得动作对象（独立动作，或者行内动作，动作都有哪些成员呢？）
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }
        //打个日志
        Yii::trace('Route to run: ' . $action->getUniqueId(), __METHOD__);

        if (Yii::$app->requestedAction === null) {
            //全局保存当前正在处理的动作对象
            Yii::$app->requestedAction = $action;
        }
        //保存当前action。当前？难道这也有递归不成？
        $oldAction = $this->action;
        //赋值给当前的控制器，动作对象即保存在全局，也保存在所属控制器
        $this->action = $action;

        $modules = [];
        $runAction = true;

        // call beforeAction on modules  beforeAction也分级别哟。module级别的。下面是Controller级别的
        //无论哪一级别，因为它们都是Component的子类，所以共用一套事件代码
        //当前控制器所属的模块祖先们，看看它们有没有beforeAction事件处理
        foreach ($this->getModules() as $module) {
            if ($module->beforeAction($action)) {
                //有的话，就保留这个模块，后来居上。因为后续还有afterAction呢
                array_unshift($modules, $module);
            } else {
                //模块级别的事件结果返回false,则后续的控制器级别的beforeAction也就不得触发了
                $runAction = false;
                break;
            }
        }
        //初始化执行动作的结果变量$result
        $result = null;
        //模块级别事件处理器OK（$runAction为真）,才能去执行控制器级别的事件处理器
        if ($runAction && $this->beforeAction($action)) {
            // run the action，普通视图的渲染，布局视图的渲染完成，基本上就是一个action的结束了。
            //带着参数去执行动作逻辑。并获得方法的返回结果$result
            $result = $action->runWithParams($params);

            //触发afterAction事件去，带着结果$result，这就是注入自定义代码的机会。
            $result = $this->afterAction($action, $result);

            // call afterAction on modules
            //那些执行过beforeAction的模块，还要执行afterAction
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }
        //递归回来，这种场景，还是很少见的吧？
        if ($oldAction !== null) {
            $this->action = $oldAction;
        }

        return $result;
    }

    /**
     * 根据路由执行请求（这是控制器执行动作的开始）
     * Runs a request specified in terms of a route.
     * 路由可以是控制器里的动作ID，或者完整的路由（模块ID,控制器ID,动作ID)
     * The route can be either an ID of an action within this controller or a complete route consisting
     * 如果路由以"/"开始，那么将从应用主体开始解析，否则从当前控制器的父模块开始解析
     * of module IDs, controller ID and action ID. If the route starts with a slash '/', the parsing of
     * the route will start from the application; otherwise, it will start from the parent module of this controller.
     * @param string $route the route to be handled, e.g., 'view', 'comment/view', '/admin/comment/view'.
     * @param array $params the parameters to be passed to the action.
     * @return mixed the result of the action.
     * @see runAction()
     */
    public function run($route, $params = [])
    {
        //有没有斜杠开始？
        $pos = strpos($route, '/');
        if ($pos === false) {
            return $this->runAction($route, $params);
        //有的话，就用模块去调用
        } elseif ($pos > 0) {
            return $this->module->runAction($route, $params);
        }
        //最后，应用主体调用
        return Yii::$app->runAction(ltrim($route, '/'), $params);
    }

    /**
     * 为动作绑定参数  （行内动作，独立动作，都会通过这个方法来绑定参数）
     * Binds the parameters to the action.
     * 该方法在Action对象中，当需要调用指定控制器的方法的开始处，传递参数时调用。
     * 这是控制器基类，故具体实现在子类web\Controller中，并不在这里。
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
     * 根据动作ID创建动作对象（独立动作或行内动作）
     * Creates an action based on the given action ID.
     * 该方法首先检查动作ID是否在actions()里声明过，
     * The method first checks if the action ID has been declared in [[actions()]]. If so,
     * 如果是，则使用在那声明的配置信息来创建动作对象
     * it will use the configuration declared there to create the action object.
     * 如果不是，则再去寻找行内动作，也就是actionXXX这样的方法。其中XXX就是动作ID
     * If not, it will look for a controller method whose name is in the format of `actionXyz`
     * 找到这样的方法,就创建InlineAction对象
     * where `Xyz` stands for the action ID. If found, an [[InlineAction]] representing that
     * method will be created and returned.
     * @param string $id the action ID.
     * @return Action the newly created action instance. Null if the ID doesn't resolve into any action.
     */
    public function createAction($id)
    {
        //没有给出动作ID，那么就用默认动作，一般是index，是在对顶层的base\Controller指定的，
        //由于是public,故继承的子类可以自行修改
        if ($id === '') {
            $id = $this->defaultAction;
        }
        //自己调用actions方法，根据结果来判断是独立的还是行内的，看来是独立动作优先于行内动作呀
        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            //Yii助手类创建独立动作对象
            return Yii::createObject($actionMap[$id], [$id, $this]);
        //从正则来看，动作ID区分大小写
        } elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            //连字符连接的两个单词首字母大写，然后加个action前缀。equ-deliver=====>EquDeliver
            $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
            //方法不再当前的控制器时就返回null
            if (method_exists($this, $methodName)) {
                //这里直接使用反射机制得到控制器动作的反射对象，从而解析整个动作对象的可访问性，参数啊什么的
                $method = new \ReflectionMethod($this, $methodName);
                //再次说明方法名要规范（虽然php的函数名是不区分大小写的）
                if ($method->isPublic() && $method->getName() === $methodName) {
                    //实例化行内动作对象
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }

    /**
     * 该方法就是为了触发beforeAction事件的（web\Controller里也有beforeAction()，但该方法里仍然调用父类的beforeAction,下面
     * 的注释也说了，这是实现子类必须要做的）
     * This method is invoked right before an action is executed.
     *
     *方法的返回值，将会影响动作对象是否执行
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *如果动作对象无需执行，那么http请求应该在beforeAction里处理
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     *
     *如果要覆盖这个方法，那么请确保新方法像下面这样：
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     // your custom code here, if you want the code to run before action filters,
     *     // which are triggered on the [[EVENT_BEFORE_ACTION]] event, e.g. PageCache or AccessControl
     *      //确保调用beforeAction方法，来触发beforeAction事件
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
        //触发Action事件，传递动作对象特有的ActionEvent对象。
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * 方法就在执行完动作逻辑后触发
     * This method is invoked right after an action is executed.
     *
     *该方法触发afterAction事件，方法的返回值不应该是事件的返回值，而是动作的返回值
     * The method will trigger the [[EVENT_AFTER_ACTION]] event. The return value of the method
     * will be used as the action return value.
     *如果想覆盖这个方法，那么代码应该像下面这样的：
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *      //必须触发afterAction事件（不要因为覆盖，把执行事件的机会给剥夺了）
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action the action just executed.
     * @param mixed $result the action return result. 动作的返回值
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;//动作的返回值，保存到事件对象的retult成员里
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        //这里仍然返回动作的结果，不是afterAction事件的结果
        return $event->result;
    }

    /**
     * 返回当前控制器所属的所有祖先模块（一般就是web\Application了）
     * Returns all ancestor modules of this controller.
     * 数组里第一个模块就是最外层的（最顶级的模块，一般是应用主体）
     * The first module in the array is the outermost one (i.e., the application instance),
     * 而数组最后的元素就是最近的模块祖先
     * while the last is the innermost one.
     * @return Module[] all ancestor modules that this controller is located within.
     */
    public function getModules()
    {
        //第一个就是控制器的直接上属模块
        $modules = [$this->module];
        $module = $this->module;
        //只要该模块上属有模块（即模块的模块）
        while ($module->module !== null) {
            //把模块的父级模块添加到数组的第一个位置
            array_unshift($modules, $module->module);
            //指针上移一位
            $module = $module->module;
        }
        return $modules;
    }

    /**
     * 当前控制器ID
     * Returns the unique ID of the controller.
     * @return string the controller ID that is prefixed with the module ID (if any).
     */
    public function getUniqueId()
    {
        //属主模块是应用主体的话，就直接是控制器ID，否则就是带有模块前缀的控制器ID
        return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . '/' . $this->id;
    }

    /**
     * 返回当前http请求的路由
     * Returns the route of the current request.
     * @return string the route (module ID, controller ID and action ID) of the current request.
     */
    public function getRoute()
    {
        //有动作对象的话，路由就精确到动作
        //否则就精确到控制器
        return $this->action !== null ? $this->action->getUniqueId() : $this->getUniqueId();
    }

    /**
     * 渲染视图，并在可能的情况下应用布局视图（看了这个方法的源码之后，就知道了，控制器不会渲染视图，而是由视图对象去渲染视图，
     * 所以，控制器的render方法，也是快捷方式，最终还是由视图对象的renderXXX()方法完成渲染逻辑）
     * Renders a view and applies layout if available.
     *视图参数可以是如下的格式之一：
     * The view to be rendered can be specified in one of the following formats:
     *
     * - path alias (e.g. "@app/views/site/index");  路径别名
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes. 应用主体下的绝对路径
     * 实际的视图文件将从Application::viewPath下寻找
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash. 模块下的绝对路径
     * 实际的视图文件将从Module::viewPath下寻找
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of [[module]].
     *   相对路径，比如index,实际的视图文件将从viewPath下寻找
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     *
     *如何确定应用哪个布局视图？由接下来的两步走战略：
     * To determine which layout should be applied, the following two steps are conducted:
     *
     *第一步：取决于布局视图名和上下文模块
     * 1. In the first step, it determines the layout name and the context module:
     *
     * - If [[layout]] is specified as a string, use it as the layout name and [[module]] as the context module;
     * - If [[layout]] is null, search through all ancestor modules of this controller and find the first
     *   module whose [[Module::layout|layout]] is not null. The layout and the corresponding module
     *   are used as the layout name and the context module, respectively. If such a module is not found
     *   or the corresponding layout is not a string, it will return false, meaning no applicable layout.
     *第二步，根据第一步得到的布局视图名和上下文模块
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
        //此时，程序就到了视图对象了
        $content = $this->getView()->render($view, $params, $this);
        //把渲染普通视图文件后的内容，当做参数传递进来，进而再去渲染布局视图
        return $this->renderContent($content);
    }

    /**通过应用一个布局文件来渲染一个静态字符串$content
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
     * 直接渲染一个没有布局视图的普通视图
     * Renders a view without applying layout.
     * 该方法不同于render方法的是，它不会应用任何布局视图
     * This method differs from [[render()]] in that it does not apply any layout.
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderPartial($view, $params = [])
    {
        //既然不必渲染布局视图，那就直接return就行了，不必再renderContent()了。干脆！
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * 渲染视图文件
     * 其实是视图对象的方法renderFile()的快捷方式
     * Renders a view file.
     * @param string $file the view file to be rendered. This can be either a file path or a path alias.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderFile($file, $params = [])
    {
        //还是转而去调用视图对象的方法吧，哈哈
        return $this->getView()->renderFile($file, $params, $this);
    }

    /**
     * 返回View组件，控制器哪里来的View组件，当然也是从$app应用对象而来了
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
     * 设置当前控制器应用的视图组件对象
     * Sets the view object to be used by this controller.
     * @param View|\yii\web\View $view the view object that can be used to render views or view files.
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
     * 返回当前控制器的视图路径。
     * Returns the directory containing view files for this controller.
     * 默认的实现，就是由模块下的控制器ID命名的目录名
     * The default implementation returns the directory named as controller [[id]] under the [[module]]'s
     * [[viewPath]] directory.
     * @return string the directory containing the view files for this controller.
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            //当前的模块，一般就是应用主体，遇到不是应用主体的再说
            $this->_viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        }
        return $this->_viewPath;
    }

    /**
     * 直接设置视图路径，一般由内部调用，
     * 当然，改方法是public，所以也可以在外部直接调用，临时修改视图文件的路径
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidParamException if the directory is invalid
     * @since 2.0.7
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    /**找到应用的布局文件（返回它的绝对路径）
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
        //也许不需要布局视图，那就返回false得了
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
