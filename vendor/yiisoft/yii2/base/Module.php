<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use yii\di\ServiceLocator;

/**
 * Module是模块和应用主体（baseApplication)的基类
 * Module is the base class for module and application classes.
 *一个模块，就是一个子应用主体，可以独立地包含mvc元素
 * A module represents a sub-application which contains MVC elements by itself, such as
 * models, views, controllers, etc.
 *
 *模块可能包含  模块|子模块
 * A module may consist of [[modules|sub-modules]].
 *有些组件可以注册成模块，这样可以在模块里全局访问。不理解！
 * [[components|Components]] may be registered with the module so that they are globally
 * accessible within the module.
 *
 * For more details and usage information on Module, see the [guide article on modules](guide:structure-modules).
 *
 * 数组，$aliases   路径别名，数组的key就是别名（以@开始） 
 * @property array $aliases List of path aliases to be defined. The array keys are alias names (must start
 * 数组的值是路径或者还是别名。参考setAliases()，该属性只写
 * with `@`) and the array values are the corresponding paths or aliases. See [[setAliases()]] for an example.
 * This property is write-only.
 * 字符串，$basePath  模块的根目录
 * @property string $basePath The root directory of the module.
 * $controllerPath 包含控制器类的目录，只读属性
 * @property string $controllerPath The directory that contains the controller classes. This property is
 * read-only.
 * $layoutPath布局视图文件的根目录，默认就是[[viewPath]]/layouts
 * @property string $layoutPath The root directory of layout files. Defaults to "[[viewPath]]/layouts".
 * 数组  $modules 以模块id索引的模块们
 * @property array $modules The modules (indexed by their IDs).
 * $uniqueId  当前模块的唯一标识ID,只读
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * 字符串  $version  模块的版本，注意，这个属性并不是getter或者setter
 * @property string $version The version of this module. Note that the type of this property differs in getter
 * 请参考getVersion和setVersion两个方法
 * and setter. See [[getVersion()]] and [[setVersion()]] for details.
 * 字符串，viewPath ，视图文件的根目录，默认就是[[basePath]]/views
 * @property string $viewPath The root directory of view files. Defaults to "[[basePath]]/views".
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Module extends ServiceLocator
{
    /**
     * 定义两个事件名，一般是在控制器动作执行之前和之后
     * @event ActionEvent an event raised before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be `false` to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * 数组，自定义模块参数（键值对格式）
     * @var array custom module parameters (name => value).
     */
    public $params = [];
    /**
     * 处于同一个模块树里的，每个模块的唯一标识符。ID
     * @var string an ID that uniquely identifies this module among other modules which have the same [[module|parent]].
     */
    public $id;
    /**
     * Module   当前模块的父模块，null则说明当前模块没有父模块
     * @var Module the parent module of this module. `null` if this module does not have a parent.
     */
    public $module;
    /**
     * 布局视图文件所在的目录名，
     * @var string|bool the layout that should be applied for views within this module. This refers to a view name
     * 一般是相对于[[viewPath]]的。如果没有设置，那么向上寻找父级模块的
     * relative to [[layoutPath]]. If this is not set, it means the layout value of the [[module|parent module]]
     * 如果设置false,那么说明当前模块无需布局视图
     * will be taken. If this is `false`, layout will be disabled within this module.
     */
    public $layout;
    /**
     * 数组，控制器ID和控制器配置的映射
     * @var array mapping from controller ID to controller configurations.
     * 每个键值对，都为一个控制器指明其配置
     * Each name-value pair specifies the configuration of a single controller.
     * 一个控制器的配置可以是字符串也可以是数组
     * A controller configuration can be either a string or an array.
     * 如果是前者(字符串),那么字符串就应该控制器的命名空间全名
     * If the former, the string should be the fully qualified class name of the controller.
     * 如果是后者（数组），那么这个数组必须有class下标来指明控制器的全名，
     * If the latter, the array must contain a `class` element which specifies
     * 其他的元素则是初始化这个控制器的成员属性，比如：
     * the controller's fully qualified class name, and the rest of the name-value pairs
     * in the array are used to initialize the corresponding controller properties. For example,
     *
     * ```php
     * [
     *  字符串就是控制器的全名
     *   'account' => 'app\controllers\UserController',
     *   数组的
     *   'article' => [
     *      //必有的一个class下标
     *      'class' => 'app\controllers\PostController',
     *      其他的元素
     *      'pageTitle' => 'something new',
     *   ],
     * ]
     * ```
     */
    public $controllerMap = [];
    /**
     * 字符串，控制器所处的命名空间
     * @var string the namespace that controller classes are in.
     * 当加载控制器时，命名空间会用得到
     * This namespace will be used to load controller classes by prepending it to the controller
     * class name.
     * 如果这个属性没有设置，那么将采用`controllers`作为子命名空间,放在模块的命名空间之下。
     * If not set, it will use the `controllers` sub-namespace under the namespace of this module.
     * 比如，如果当前模块的命名空间是`foo\bar`,那么默认的控制器的命名空间将是foo\bar\controllers
     * For example, if the namespace of this module is `foo\bar`, then the default
     * controller namespace would be `foo\bar\controllers`.
     *
     *请参考guide(guide:concept-autoloading)来了解命名空间和如何加载
     * See also the [guide section on autoloading](guide:concept-autoloading) to learn more about
     * defining namespaces and how classes are loaded.
     */
    public $controllerNamespace;
    /**
     * 字符串，当前模块的默认路由，默认就是default
     * @var string the default route of this module. Defaults to `default`.
     * 该路由可能包含子模块ID,控制器ID,动作ID
     * The route may consist of child module ID, controller ID, and/or action ID.
     * 比如： help,    post/create       admin/post/create
     * For example, `help`, `post/create`, `admin/post/create`.
     * 如果动作ID没有给出，则从它下属的控制器的defaultAction中取得
     * If action ID is not given, it will take the default value as specified in
     * [[Controller::defaultAction]].
     */
    public $defaultRoute = 'default';

    /**
     * 当前模块的根目录
     * @var string the root directory of the module.
     */
    private $_basePath;
    /**
     * 字符串，当前模块包含视图文件的根目录
     * @var string the root directory that contains view files for this module
     */
    private $_viewPath;
    /**
     * 字符串，当前模块包含布局视图文件的根目录
     * @var string the root directory that contains layout view files for this module.
     */
    private $_layoutPath;
    /**
     * 数组，当前模块的子模块
     * @var array child modules of this module
     */
    private $_modules = [];
    /**
     * 字符串|回调函数  当前模块的版本
     * @var string|callable the version of this module.
     * Version可以用PHP回调函数表示，回调函数接收模块实例作为参数，应该返回一个字符串的版本信息，比如
     * Version can be specified as a PHP callback, which can accept module instance as an argument and should
     * return the actual version. For example:
     *
     * ```php
     * function (Module $module) {
     *     //return string|int
     * }
     * ```
     *
     *如果没有设置，将使用defaultVersion()方法来决定版本信息
     * If not set, [[defaultVersion()]] will be used to determine actual value.
     *
     * @since 2.0.11
     */
    private $_version;


    /**
     * 构造函数
     * Constructor.
     * @param string $id the ID of this module.   $id   模块ID
     * @param Module $parent the parent module (if any).  父模块
     * $config  键值对配置信息，用来初始化模块的成员属性
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($id, $parent = null, $config = [])
    {
        $this->id = $id;
        //存放父模块对象的引用
        $this->module = $parent;
        parent::__construct($config);
    }

    /**
     * 返回当前被请求的模块类的实例（模块被请求？）
     * Returns the currently requested instance of this module class.
     * 如果当前类没有请求，则返回null
     * If the module class is not currently requested, `null` will be returned.
     * 这个方法就是为了在模块里访问模块实例而提供的
     * This method is provided so that you access the module instance from anywhere within the module.
     * @return static|null the currently requested instance of this module class, or `null` if the module class is not requested.
     */
    public static function getInstance()
    {
        $class = get_called_class();
        return isset(Yii::$app->loadedModules[$class]) ? Yii::$app->loadedModules[$class] : null;
    }

    /**
     * 设置模块实例，最常见的就是添加web\application实例了。全局应用主体。
     * Sets the currently requested instance of this module class.
     * @param Module|null $instance the currently requested instance of this module class.
     * If it is `null`, the instance of the calling class will be removed, if any.
     */
    public static function setInstance($instance)
    {
        //null，就是删除模块
        if ($instance === null) {
            unset(Yii::$app->loadedModules[get_called_class()]);
        } else {
            Yii::$app->loadedModules[get_class($instance)] = $instance;
        }
    }

    /**
     * 初始化模块
     * Initializes the module.
     *该方法就在模块刚创建，并初始化成员属性之后执行，
     * This method is called after the module is created and initialized with property values
     * 默认的实现，就是初始化控制器的命名空间（如果初始化时没有指定）
     * given in configuration. The default implementation will initialize [[controllerNamespace]]
     * if it is not set.
     *
     * If you override this method, please make sure you call the parent implementation.
     */
    public function init()
    {
        if ($this->controllerNamespace === null) {
            $class = get_class($this);
            if (($pos = strrpos($class, '\\')) !== false) {
                $this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
            }
        }
    }

    /**
     * 返回模块ID，在当前应用主体下所有模块里的唯一标识符
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * 注意，如果模块是一个应用主体，那么返回空字符串
     * Note that if the module is an application, an empty string will be returned.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return $this->module ? ltrim($this->module->getUniqueId() . '/' . $this->id, '/') : $this->id;
    }

    /**
     * 返回模块的根目录
     * Returns the root directory of the module.
     * 默认就是包含当前模块的目录
     * It defaults to the directory containing the module class file.
     * @return string the root directory of the module.
     */
    public function getBasePath()
    {
        if ($this->_basePath === null) {
            //反射当前对象
            $class = new \ReflectionClass($this);
            //getFileName返回的到底是什么呢？
            $this->_basePath = dirname($class->getFileName());
        }

        return $this->_basePath;
    }

    /**
     * 设置这个模块（一般是web\application）的根目录
     * Sets the root directory of the module.
     * 这个方法只能在构造方法的开始处调用，否则就容易坏事
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the module. This can be either a directory name or a path alias.
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        //有可能含有别名，获取其最终的不含别名的路径信息
        $path = Yii::getAlias($path);
        //奇怪吗，phar://是啥玩意？一搜吓一跳，原来它是PHP 5.3引入的，类似于java的jar打包程序，用C写的。
        //phar可以把一个php应用程序或php模块打包为一个.phar的文件，方便转移和被其他php应用程序引入，还可以引入phar文件里的php文件
        //详情请查看php手册
        //获取规范后的路径，realpath
        $p = strncmp($path, 'phar://', 7) === 0 ? $path : realpath($path);
        if ($p !== false && is_dir($p)) {
            $this->_basePath = $p;
        } else {
            throw new InvalidParamException("The directory does not exist: $path");
        }
    }

    /**根据控制器命名空间，返回控制器所在的目录
     * Returns the directory that contains the controller classes according to [[controllerNamespace]].
     * 注意，为了正确返回值，必须定义控制器根命名空间定义别名
     * Note that in order for this method to return a value, you must define
     * an alias for the root namespace of [[controllerNamespace]].
     * @return string the directory that contains the controller classes.
     * @throws InvalidParamException if there is no alias defined for the root namespace of [[controllerNamespace]].
     */
    public function getControllerPath()
    {
        return Yii::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace));
    }

    /**
     * Returns the directory that contains the view files for this module.
     * @return string the root directory of view files. Defaults to "[[basePath]]/views".
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            //视图目录，是以基本目录为基础，下面的views目录。
            $this->_viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
        }
        return $this->_viewPath;
    }

    /**
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidParamException if the directory is invalid.
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    /**返回布局文件的路径，布局视图文件也是视图，故根基还是从MVC的视图目录下寻找
     * Returns the directory that contains layout view files for this module.
     * @return string the root directory of layout files. Defaults to "[[viewPath]]/layouts".
     */
    public function getLayoutPath()
    {
        if ($this->_layoutPath === null) {
            //布局视图目录是在视图目录下
            $this->_layoutPath = $this->getViewPath() . DIRECTORY_SEPARATOR . 'layouts';
        }

        return $this->_layoutPath;
    }

    /**
     * Sets the directory that contains the layout files.
     * @param string $path the root directory or path alias of layout files.
     * @throws InvalidParamException if the directory is invalid
     */
    public function setLayoutPath($path)
    {
        $this->_layoutPath = Yii::getAlias($path);
    }

    /**
     * Returns current module version.
     * If version is not explicitly set, [[defaultVersion()]] method will be used to determine its value.
     * @return string the version of this module.
     * @since 2.0.11
     */
    public function getVersion()
    {
        if ($this->_version === null) {
            //通过这个函数确定
            $this->_version = $this->defaultVersion();
        } else {
            //不是标量，那就是回调函数
            if (!is_scalar($this->_version)) {
                $this->_version = call_user_func($this->_version, $this);
            }
        }
        return $this->_version;
    }

    /**
     * 设置当前模块的版本。模块版本？有啥用？没看出来。
     * Sets current module version.
     * @param string|callable $version the version of this module.
     * Version can be specified as a PHP callback, which can accept module instance as an argument and should
     * return the actual version. For example:
     *
     * ```php
     * function (Module $module) {
     *     //return string
     * }
     * ```
     *
     * @since 2.0.11
     */
    public function setVersion($version)
    {
        $this->_version = $version;
    }

    /**
     * Returns default module version.
     * Child class may override this method to provide more specific version detection.
     * @return string the version of this module.
     * @since 2.0.11
     */
    protected function defaultVersion()
    {
        //没有父级目录，那就是1.0
        if ($this->module === null) {
            return '1.0';
        }
        //有父级目录，则使用父级目录的版本信息
        return $this->module->getVersion();
    }

    /**
     * 设置路径别名
     * Defines path aliases.
     * 该方法会调用全局助手类Yii::setAlias()来注册路径别名，或者说是一个快捷方式。
     * This method calls [[Yii::setAlias()]] to register the path aliases.
     * 该方法就是为了在配置模块时可以定义路径别名
     * This method is provided so that you can define path aliases when configuring a module.
     * @property array list of path aliases to be defined. The array keys are alias names
     * (must start with `@`) and the array values are the corresponding paths or aliases.
     * See [[setAliases()]] for an example.
     * @param array $aliases list of path aliases to be defined. The array keys are alias names
     * (must start with `@`) and the array values are the corresponding paths or aliases.
     * For example,
     *
     * ```php
     * [
     *     '@models' => '@app/models', // an existing alias
     *     '@backend' => __DIR__ . '/../backend',  // a directory
     * ]
     * ```
     */
    public function setAliases($aliases)
    {
        foreach ($aliases as $name => $alias) {
            //最终还是借用全局助手类Yii来定义的别名
            Yii::setAlias($name, $alias);
        }
    }

    /**
     * 检测是否有指定模块ID的子模块存在
     * Checks whether the child module of the specified ID exists.
     * 支持子模块与子孙模块的检测
     * This method supports checking the existence of both child and grand child modules.
     * @param string $id module ID. For grand child modules, use ID path relative to this module (e.g. `admin/content`).
     * 返回布尔值，包括加载的和未加载的模块都检测
     * @return bool whether the named module exists. Both loaded and unloaded modules
     * are considered.
     */
    public function hasModule($id)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module  子模块，就去掉前缀，再去解析
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? false : $module->hasModule(substr($id, $pos + 1));
        }
        return isset($this->_modules[$id]);
    }

    /**
     * 根据模块ID检索子模块。支持检索子模块和孙模块
     * Retrieves the child module of the specified ID.
     * This method supports retrieving both child modules and grand child modules.
     * @param string $id module ID (case-sensitive). To retrieve grand child modules,
     * use ID path relative to this module (e.g. `admin/content`).
     * @param bool $load whether to load the module if it is not yet loaded.
     * @return Module|null the module instance, `null` if the module does not exist.
     * @see hasModule()
     */
    public function getModule($id, $load = true)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module  递归自己
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? null : $module->getModule(substr($id, $pos + 1), $load);
        }
        //目前只有debug和gii两个模块
        if (isset($this->_modules[$id])) {
            //已经实例化的，直接返回
            if ($this->_modules[$id] instanceof Module) {
                return $this->_modules[$id];
            } elseif ($load) {
              //否则就去加载模块，然后再返回
                Yii::trace("Loading module: $id", __METHOD__);
                /* @var $module Module */
                $module = Yii::createObject($this->_modules[$id], [$id, $this]);
                $module->setInstance($module);
                return $this->_modules[$id] = $module;
            }
        }

        return null;
    }

    /**
     * 为当前模块添加一个子模块，注意是子模块
     * Adds a sub-module to this module.
     * 模块ID
     * @param string $id module ID.  
     * 对象，数组，null  $module   表示将要添加的子模块，可以是如下形式：
     * @param Module|array|null $module the sub-module to be added to this module. This can
     * be one of the following:
     *
     * - a [[Module]] object   对象
     * - a configuration array: when [[getModule()]] is called initially, the array  可以实例化一个模块的数组形式的配置信息
     *   will be used to instantiate the sub-module
     * - `null`: the named sub-module will be removed from this module  删除由模块ID指定的子模块
     */
    public function setModule($id, $module)
    {
        if ($module === null) {
            //null 意味着删除
            unset($this->_modules[$id]);
        } else {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * 返回子模块
     * Returns the sub-modules in this module.
     * 是否只返回已加载的子模块，若参数是false
     * @param bool $loadedOnly whether to return the loaded sub-modules only. If this is set `false`,
     * 则注册到当前模块的所有子模块都返回，无论它们是否已经加载。
     * then all sub-modules registered in this module will be returned, whether they are loaded or not.
     * 已加载的模块返回的是PHP对象，而未加载的模块则是数组类型的配置信息
     * Loaded modules will be returned as objects, while unloaded modules as configuration arrays.
     * @return array the modules (indexed by their IDs).
     * 一个典型的代码
     */
    public function getModules($loadedOnly = false)
    {
        //有条件的话，那就得使用if开启一个分支判断
        if ($loadedOnly) {
            //先初始化返回的数组
            $modules = [];
            //然后依次遍历数组元素
            foreach ($this->_modules as $module) {
                //这里还可以为每个元素增加条件判断
                if ($module instanceof Module) {
                    $modules[] = $module;
                }
            }
            //最终返回筛选好的模块数组
            return $modules;
        }
        //否则就全部返回
        return $this->_modules;
    }

    /**
     * 为当前模块注册子模块们，注意方法名是加s的
     * Registers sub-modules in the current module.
     *每个子模块应该都是一个键值对，键就是模块的ID而值就是模块（对象或数组形式的配置信息）
     * Each sub-module should be specified as a name-value pair, where
     * name refers to the ID of the module and value the module or a configuration
     * 如果是数组的话，后期会调用Yii::createObject()方法来实例化这个模块
     * array that can be used to create the module. In the latter case, [[Yii::createObject()]]
     * will be used to create the module.
     *如果模块ID重复，那么新的模块将会静默地覆盖旧的
     * If a new sub-module has the same ID as an existing one, the existing one will be overwritten silently.
     *下面的注册模块的例子：
     * The following is an example for registering two sub-modules:
     *
     * ```php
     * [
     *     'comment' => [
     *         'class' => 'app\modules\comment\CommentModule',
     *         'db' => 'db',
     *     ],
     *     'booking' => ['class' => 'app\modules\booking\BookingModule'],
     * ]
     * ```
     *
     * @param array $modules modules (id => module configuration or instances).
     */
    public function setModules($modules)
    {
        //直接遍历，一个个添加就行了
        foreach ($modules as $id => $module) {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * webApplication的runAction方法，走到了这里，又让人联想到了应用主体和模块的关系
     * Runs a controller action specified by a route.
     * 该方法解析路由并创建对应的子模块，控制器和动作实例
     * This method parses the specified route and creates the corresponding child module(s), controller and action
     * 然后再调用控制器的runAction方法[[Controller::runAction()]]，传递参数来执行。
     * instances. It then calls [[Controller::runAction()]] to run the action with the given parameters.
     * 如果路由为空，将使用defaultRoute指定的默认路由
     * If the route is empty, the method will use [[defaultRoute]].
     * @param string $route the route that specifies the action.
     * @param array $params the parameters to be passed to the action
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested route cannot be resolved into an action successfully.
     */
    public function runAction($route, $params = [])
    {
        //先创建控制器，根据路由创建，虽然方法名是createController,但是返回的却是数组
        //数组里包含有控制器和动作信息
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Controller */
            //控制器对象，和动作ID（字符串）
            list($controller, $actionID) = $parts;
            //当前活跃的控制器，都会在应用主体里全局保存，这种保存旧控制器的方式，是递归的思想吧
            $oldController = Yii::$app->controller;
            Yii::$app->controller = $controller;
            //控制器在这里接管，开始处理http请求，就到了控制器的天下了
            $result = $controller->runAction($actionID, $params);
            //再把旧的控制器取回来
            if ($oldController !== null) {
                Yii::$app->controller = $oldController;
            }
            //最终返回控制器run Action返回的结果
            return $result;
        }
        //否则报错，路由（模块ID/控制器ID/动作ID)字符串
        $id = $this->getUniqueId();
        throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
    }

    /**
     * 根据给定的路由字符串创建控制器实例
     * Creates a controller instance based on the given route.
     *路由应该是相对于当前模块的。    该方法实现了如下的算法来解析路由
     * The route should be relative to this module. The method implements the following algorithm
     * to resolve the given route:
     * 一 如果路由为空，则用模块的成员defaultRoute指定的默认路由（其实是控制器site,没有动作信息）
     * 1. If the route is empty, use [[defaultRoute]];
     * 二 如果路由的第一段是个有效的模块ID（在[[modules]]里声明过的）,
     * 2. If the first segment of the route is a valid module ID as declared in [[modules]],
     *   那么就使用余下的路由信息作为实参，调用该模块的createController()方法
     *    call the module's `createController()` with the rest part of the route;
     * 三  如果路由的第一段在controllerMap映射里找到，那么就根据映射对应的配置信息来创建控制器
     * 3. If the first segment of the route is found in [[controllerMap]], create a controller
     *    based on the corresponding configuration found in [[controllerMap]];
     * 四 如果给定的路由是 ‘abc/def/xyz'格式的字符串，那么将会在控制器命名空间下尝试abc\DefController控制器
     * 4. The given route is in the format of `abc/def/xyz`. Try either `abc\DefController`
     * 或者abc\def\XyxController控制器，
     *    or `abc\def\XyzController` class within the [[controllerNamespace|controller namespace]].
     * 上述四步的任何一步解析出控制器对象，他将和剩余的路由信息组织成一个数组返回，剩余的路由信息
     * 就是动作ID。否则就返回false
     * If any of the above steps resolves into a controller, it is returned together with the rest
     * part of the route which will be treated as the action ID. Otherwise, `false` will be returned.
     *
     *一定要理解传递进来的参数$route的信息，对于理解该方法非常有帮助
     * @param string $route the route consisting of module, controller and action IDs.
     * @return array|bool If the controller is created successfully, it will be returned together
     * with the requested action ID. Otherwise `false` will be returned.
     * 报异常，无效配置异常，这个异常非常常见，可以在开发中考虑使用之。
     * @throws InvalidConfigException if the controller class and its file do not match.
     */
    public function createController($route)
    {
        //空字符串，意味着外界没有给出路由，那么使用内部自定义的路由
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        
        // double slashes or leading/ending slashes may cause substr problem
        //去掉两端的斜杠
        $route = trim($route, '/');
        //不能出现连续的两个斜杠
        if (strpos($route, '//') !== false) {
            return false;
        }

        //有斜杠，则说明有控制器ID，动作ID
        if (strpos($route, '/') !== false) {
            //只取得第一段信息给$id(因为第一个就是控制器的名字呗），剩余的保留为数组的第二个元素，赋值为$route，还是使用PHP原生函数。
            list ($id, $route) = explode('/', $route, 2);
        } else {
         //否则，估计就仅仅是控制器信息，而没有动作信息的那种（比如来自默认路由的site)
            $id = $route;
            $route = '';
        }


		//注意，这里先判断控制器映射，再去判断Module。
        // module and controller map take precedence
        //是否在控制器映射里（这是应用自定义控制器别名的好地方吧？）
        if (isset($this->controllerMap[$id])) {
            //处在映射里的控制器信息，使用Yii全局助手类来实例化这个控制器
            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
            //注意，第一个元素是控制器对象，第二个元素是剩余的路由信息，字符串。
            return [$controller, $route];
        }
        //路由的第一段也许是个模块ID
        $module = $this->getModule($id);
        if ($module !== null) {
            //获得了模块之后，再去创建它下属的控制器信息
            return $module->createController($route);
        }
        //子命名空间的那种了吧？
        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }
        //一般情况下$route已经是割掉第一段的剩余部分，比如site/contact的contact
        $controller = $this->createControllerByID($id);
        //如果上一步创建控制器失败，路由还有剩余，那就再次去创建控制器，这次参数有变化。
        //两次创建控制器的机会。
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerByID($id . '/' . $route);
            $route = '';
        }
        //如果两次创建的控制器都是null,那就路到尽头，只得返回false了。
        //正确的返回格式，是个数组，第一个元素是控制器对象，第二个元素是剩余路由信息，字符串。比如site/contact的contact
        return $controller === null ? false : [$controller, $route];
    }

    /**
     * 根据控制器ID创建控制器实例
     * Creates a controller based on the given controller ID.
     *
     *控制器ID是相对于当前模块的，且对应的控制器类应该在
     * The controller ID is relative to this module. The controller class
     * [[controllerNamespace]].命名空间下
     * should be namespaced under [[controllerNamespace]].
     *该方法不检查模块祖先或者控制器映射
     * Note that this method does not check [[modules]] or [[controllerMap]].
     *
     * @param string $id the controller ID.
     * @return Controller the newly created controller instance, or `null` if the controller ID is invalid.
     * @throws InvalidConfigException if the controller class and its file name do not match.
     * This exception is only thrown when in debug mode.
     */
    public function createControllerByID($id)
    {
        //带有斜杠的字符串，可能是有控制器的子命名空间吧
        $pos = strrpos($id, '/');
        //site控制器就会走这一段,比如是site字符串。还有equipment-deliver字符串
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }

        //可见控制器命名里出现的字符，是小写字母a-z,数字0-9,连字符-，下划线_
        if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
            return null;
        }
        //site控制器，一般没有前缀，不会走这个判断分支
        if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
            return null;
        }
        //这里就是把"bind-pos"这样的字符串，改成"BindPosController"这样的字符串
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
        //在上述处理的基础上，增加命名空间前缀
        $className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix)  . $className, '\\');
        //经上述操作后，若还有连字符-，或者使用php原生函数class_exists来判断是否已经定义该类。不满足则控制器创建失败
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }
        //检测要实例化的控制器，是否是yii\base\Controller的子类或者实现过该接口
        if (is_subclass_of($className, 'yii\base\Controller')) {
            //可以去实例化这个类了，带着两个参数，这两个参数干啥？
            $controller = Yii::createObject($className, [$id, $this]);
            //再次验证实例化的类的名字，是不是最初的字符串类名完全一样。
            return get_class($controller) === $className ? $controller : null;
        } elseif (YII_DEBUG) {
            throw new InvalidConfigException("Controller class must extend from \\yii\\base\\Controller.");
        }
        return null;
    }

    /**
     * 该方法就是在动作执行之前被调用，当前模块里的动作
     * This method is invoked right before an action within this module is executed.
     *
     *该方法触发EVENT_BEFORE_ACTION事件，事件返回的结果将影响接下来的动作是否执行
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *如果后续的动作不执行，那么客户端的请求就应该在这个方法里处理完成，可以是给予必要是输出，或者重定向
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * 要不然响应将是空的
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     *如果子类想覆盖该方法，那么代码该是下面这样的：
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // your custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * 该方法就是在动作刚刚执行完之后触发
     * This method is invoked right after an action within this module is executed.
     *触发[[EVENT_AFTER_ACTION]]事件，方法的返回值，将用作动作的返回值，也就是说，这是一个修改动作返回结果的好机会。
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
        //动作结果，交给了事件对象的result成员
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        //最终还是返回这个成员，但是在trigger里是有机会更改result的。
        return $event->result;
    }
}
