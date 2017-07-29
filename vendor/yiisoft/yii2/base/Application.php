<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Application is the base class for all application classes.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 *资源包管理器组件，只读
 * @property \yii\web\AssetManager $assetManager The asset manager application component. This property is
 * read-only.
 * rbac管理器接口组件，只读
 * @property \yii\rbac\ManagerInterface $authManager The auth manager application component. Null is returned
 * if auth manager is not configured. This property is read-only.
 * 应用的根目录，注意这里的应用是baisic,而目录是web。也就是说，basic这个应用的根目录是web
 * @property string $basePath The root directory of the application. 
 * 缓存组件 只读
 * @property \yii\caching\Cache $cache The cache application component. Null if the component is not enabled.
 * This property is read-only.
 * 数组键值对 只写
 * @property array $container Values given in terms of name-value pairs. This property is write-only.
 * 数据库服务器连接组件 只读
 * @property \yii\db\Connection $db The database connection. This property is read-only.
 * 错误处理器组件，只读
 * @property \yii\web\ErrorHandler|\yii\console\ErrorHandler $errorHandler The error handler application
 * component. This property is read-only.
 * 国际格式化组件，只读
 * @property \yii\i18n\Formatter $formatter The formatter application component. This property is read-only.
 * 国际化组件，只读
 * @property \yii\i18n\I18N $i18n The internationalization application component. This property is read-only.
 * 日志派发组件，只读
 * @property \yii\log\Dispatcher $log The log dispatcher application component. This property is read-only.
 * 邮件应用组件，只读
 * @property \yii\mail\MailerInterface $mailer The mailer application component. This property is read-only.
 * web请求或控制器请求组件，只读
 * @property \yii\web\Request|\yii\console\Request $request The request component. This property is read-only.
 * web响应或控制台响应组件，只读
 * @property \yii\web\Response|\yii\console\Response $response The response component. This property is
 * read-only.
 * runtimePath属性，标识runtime目录所在的路径，一般在basePath下
 * @property string $runtimePath The directory that stores runtime files. Defaults to the "runtime"
 * subdirectory under [[basePath]].
 * 安全相关的组件，只读
 * @property \yii\base\Security $security The security application component. This property is read-only.
 * 时区属性
 * @property string $timeZone The time zone used by this application.
 * 该模块的唯一ID，只读
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * Url管理器组件，只读
 * @property \yii\web\UrlManager $urlManager The URL manager for this application. This property is read-only.
 * vendorPath属性，标识vendor目录所在的路径,一般在basePath下
 * @property string $vendorPath The directory that stores vendor files. Defaults to "vendor" directory under
 * [[basePath]].
 * 视图组件，用来渲染视图文件，只读
 * @property View|\yii\web\View $view The view application component that is used to render various view
 * files. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class Application extends Module
{
    /**
     * @event Event an event raised before the application starts to handle a request.
     * 事件常量
     */
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    /**
     * @event Event an event raised after the application successfully handles a request (before the response is sent out).
     */
    const EVENT_AFTER_REQUEST = 'afterRequest';
    /**
     * Application state used by [[state]]: application just started.
     * 应用状态常量，供state属性使用。7个常量，意味着有7种应用状态
     */
    const STATE_BEGIN = 0;
    /**
     * Application state used by [[state]]: application is initializing.
     */
    const STATE_INIT = 1;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_BEFORE_REQUEST]].
     */
    const STATE_BEFORE_REQUEST = 2;
    /**
     * Application state used by [[state]]: application is handling the request.
     */
    const STATE_HANDLING_REQUEST = 3;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_AFTER_REQUEST]]..
     */
    const STATE_AFTER_REQUEST = 4;
    /**
     * Application state used by [[state]]: application is about to send response.
     */
    const STATE_SENDING_RESPONSE = 5;
    /**
     * Application state used by [[state]]: application has ended.
     */
    const STATE_END = 6;

    /**
     * @var string the namespace that controller classes are located in.
     * This namespace will be used to load controller classes by prepending it to the controller class name.
     * The default namespace is `app\controllers`.
     *控制器命名空间，主要影响控制器的自动加载
     * Please refer to the [guide about class autoloading](guide:concept-autoloading.md) for more details.
     */
    public $controllerNamespace = 'app\\controllers';
    /**
     * @var string the application name.
     */
    public $name = 'My Application';
    /**
     * @var string the charset currently used for the application.
     */
    public $charset = 'UTF-8';
    /**
     * @var string the language that is meant to be used for end users. It is recommended that you
     * use [IETF language tags](http://en.wikipedia.org/wiki/IETF_language_tag). For example, `en` stands
     * for English, while `en-US` stands for English (United States).
     * en英文，en-US美式英文，zh-CN简体中文，zh中文
     * @see sourceLanguage
     */
    public $language = 'en-US';
    /**
     * @var string the language that the application is written in. This mainly refers to
     * the language that the messages and view files are written in.
     * @see language
     * 主要应用于视图和日志里的内容
     */
    public $sourceLanguage = 'en-US';
    /**
     * @var Controller the currently active controller instance
     * 当前处于激活状态的控制器
     */
    public $controller;
    /**
     * @var string|bool the layout that should be applied for views in this application. Defaults to 'main'.
     * If this is false, layout will be disabled.
     * 布局视图文件，为其他视图中使用。false时将不使用布局视图
     */
    public $layout = 'main';
    /**
     * @var string the requested route
     * 请求路径，含控制器和动作信息
     */
    public $requestedRoute;
    /**
     * @var Action the requested Action. If null, it means the request cannot be resolved into an action.
     * 请求的方法
     */
    public $requestedAction;
    /**
     * @var array the parameters supplied to the requested action.
     * 请求get参数
     */
    public $requestedParams;
    /**
     * @var array list of installed Yii extensions. Each array element represents a single extension
     * with the following structure:
     *下面是每个已安装的yii扩展的结构
     * ```php
     * [
     *     'name' => 'extension name',
     *     'version' => 'version number',
     *     'bootstrap' => 'BootstrapClassName',  // optional, may also be a configuration array
     *     'alias' => [
     *         '@alias1' => 'to/path1',
     *         '@alias2' => 'to/path2',
     *     ],
     * ]
     * ```
     *比如vendor/yiisoft目录下的yii2-gii,yii2-debug都是扩展。
     * The "bootstrap" class listed above will be instantiated during the application
     * bootstrap数组里的组件或模块将会在应用启动时的bootstrap()过程里实例化
     * 如果这些启动的组件或模块实现了boostrapInterface接口的bootstrap方法，实例化后会调用
     * [[bootstrap()|bootstrapping process]]. If the class implements [[BootstrapInterface]],
     * its [[BootstrapInterface::bootstrap()|bootstrap()]] method will be also be called.
     *
     *如果配置文件中没有配置的话，这个属性将会由yii框架目录里的extensions.php文件来填充
     * If not set explicitly in the application config, this property will be populated with the contents of
     * `@vendor/yiisoft/extensions.php`.
     */
    public $extensions;
    /**
     * $bootstrap组件数组，表示在bootstrap()里会运行的一系列组件或模块
     * @var array list of components that should be run during the application [[bootstrap()|bootstrapping process]].
     *每个组件的格式有：
     *组件ID或模块ID
     *class 告知的类
     *类属性的配置子数组
     * Each component may be specified in one of the following formats:
     *
     * - an application component ID as specified via [[components]].
     * - a module ID as specified via [[modules]].
     * - a class name.
     * - a configuration array.
     *在bootstrap()中，每个组件将会实例化，若某个组件实现了BootstrapInterface接口，还会调用bootstrap方法
     * During the bootstrapping process, each component will be instantiated. If the component class
     * implements [[BootstrapInterface]], its [[BootstrapInterface::bootstrap()|bootstrap()]] method
     * will be also be called.
     */
    public $bootstrap = [];
    /**
     * 标识了当前应用在整个请求处理的生命周期里的状态，7个状态之一
     * @var int the current application state during a request handling life cycle.
     * 该属性由应用来管理，开发者不要修改，只读
     * This property is managed by the application. Do not modify this property.
     * 
     */
    public $state;
    /**类名作为下标，模块实例作为值的数组
     * 一系列已经加载了的模块，这里要区别模块和组件
     * @var array list of loaded modules indexed by their class names.
     */
    public $loadedModules = [];


    /**
     * Constructor.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     * Note that the configuration must contain both [[id]] and [[basePath]].
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function __construct($config = [])
    {
        Yii::$app = $this;
        static::setInstance($this);//把自己$this放到自己的静态属性中

        $this->state = self::STATE_BEGIN;
        //设置路径，路径别名，配置容器类container属性，
        //加载核心组件到$config中，$config是引用传参
        $this->preInit($config);
        //反射ErrorHandler，并注册它全权处理异常，错误，shutdown,$config还是引用传参
        $this->registerErrorHandler($config);
        //设置模块和应用组件（利用__set()魔术方法），并在init方法里bootsrap，
        //应用组件放到了服务定位器里，模块放到了Module的数组里
        Component::__construct($config);
    }

    /**
     * 预初始应用，注意此时“应用”是个名词
     * Pre-initializes the application.
     * 这个方法在构造方法里的开始
     * This method is called at the beginning of the application constructor.
     * 它初始化了几个重要的应用属性
     * It initializes several important application properties.
     * 子类如果覆盖这个方法时，请记得调用父类的方法
     * If you override this method, please make sure you call the parent implementation.
     * @param array $config the application configuration
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     * 注意，设置的三个路径都不是application而是其父类Module的，比如_basePath,_vendorPath,_runtimePath
     * 还确定了时区，利用date_default_timezone_set()
     * 设置了容器静态类的属性（如果有的话）
     * 以上的设置都是设置完之后unset
     * populate(填充了)几个核心组件（写死在base\Application基类里的）
     */
    public function preInit(&$config)
    {
        if (!isset($config['id'])) {
            throw new InvalidConfigException('The "id" configuration for the Application is required.');
        }
        if (isset($config['basePath'])) {
            //设置根路径，但是设置父类_basePath属性，不是$basePath。然后设置了@app别名
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new InvalidConfigException('The "basePath" configuration for the Application is required.');
        }

        if (isset($config['vendorPath'])) {
            //不但设置vendorPath
            //还会设置@vendor,@bower,@npm别名
            $this->setVendorPath($config['vendorPath']);
            unset($config['vendorPath']);
        } else {
            // set "@vendor"
            //仅仅设置vendorPath
            $this->getVendorPath();
        }
        if (isset($config['runtimePath'])) {
            //按照参数指定，设置根目录
            $this->setRuntimePath($config['runtimePath']);
            unset($config['runtimePath']);
        } else {
            // set "@runtime"
            //根据当前应用（basic)设置根目录
            $this->getRuntimePath();
        }

        //优先配置文件中的时区设置
        if (isset($config['timeZone'])) {
            $this->setTimeZone($config['timeZone']);
            unset($config['timeZone']);
        //或者php.ini里的时区设置
        } elseif (!ini_get('date.timezone')) {
            $this->setTimeZone('UTC');
        }

        if (isset($config['container'])) {
            //设置容器container的属性，而非设置容器container对象
            $this->setContainer($config['container']);

            unset($config['container']);
        }

        // merge core components with custom components
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->state = self::STATE_INIT;
        $this->bootstrap();
    }

    /**
     * 初始化扩展，并执行bootstrap里的组件
     * Initializes extensions and executes bootstrap components.
     * This method is called by [[init()]] after the application has been fully configured.
     * If you override this method, make sure you also call the parent implementation.
     */
    protected function bootstrap()
    {
        //读取组件
        if ($this->extensions === null) {
            $file = Yii::getAlias('@vendor/yiisoft/extensions.php');
            $this->extensions = is_file($file) ? include($file) : [];
        }
        //遍历扩展
        foreach ($this->extensions as $extension) {
            //当前扩展可以有别名
            if (!empty($extension['alias'])) {
                foreach ($extension['alias'] as $name => $path) {
                    Yii::setAlias($name, $path);
                }
            }
            //当前扩展是否有自己的bootstrap,经查看最初的Yii框架里自带的每个扩展都没有bootstrap
            if (isset($extension['bootstrap'])) {
                $component = Yii::createObject($extension['bootstrap']);
                if ($component instanceof BootstrapInterface) {
                    Yii::trace('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                    $component->bootstrap($this);
                } else {
                    Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
                }
            }
        }
        //开始配置文件里的bootstrap
        foreach ($this->bootstrap as $class) {
            $component = null;
            if (is_string($class)) {
                if ($this->has($class)) {//服务定位器中是否已注册
                    $component = $this->get($class);//得到这个实例化的组件（其中有服务定位器模式的实现）
                } elseif ($this->hasModule($class)) {//再看Module中是否有
                    $component = $this->getModule($class);
                } elseif (strpos($class, '\\') === false) {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                }
            }
            //如果上面的步骤没有实例化某一个组件，那就在这里补充实例化一下
            if (!isset($component)) {
                $component = Yii::createObject($class);
            }
            //如果实现了BootstrapInterface接口的话，就去执行这个方法
            if ($component instanceof BootstrapInterface) {
                Yii::trace('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                $component->bootstrap($this);
            } else {
                Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
            }
        }
    }

    /**
     * Registers the errorHandler component as a PHP error handler.
     * @param array $config application config
     */
    protected function registerErrorHandler(&$config)
    {
        if (YII_ENABLE_ERROR_HANDLER) {
            if (!isset($config['components']['errorHandler']['class'])) {
                echo "Error: no errorHandler component is configured.\n";
                exit(1);
            }
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            $this->getErrorHandler()->register();
        }
    }

    /**
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * Since this is an application instance, it will always return an empty string.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return '';
    }

    /**
     * Sets the root directory of the application and the @app alias.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the application.
     * @property string the root directory of the application.
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        parent::setBasePath($path);
        Yii::setAlias('@app', $this->getBasePath());
    }

    /**
     * Runs the application.
     * This is the main entrance of an application.
     * @return int the exit status (0 means normal, non-zero values mean abnormal)
     */
    public function run()
    {
        try {

            $this->state = self::STATE_BEFORE_REQUEST;//标志状态
            $this->trigger(self::EVENT_BEFORE_REQUEST);//触发事件call_user_func

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;

        } catch (ExitException $e) {

            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;

        }
    }

    /**
     * Handles the specified request.
     *
     * This method should return an instance of [[Response]] or its child class
     * which represents the handling result of the request.
     *
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    abstract public function handleRequest($request);

    private $_runtimePath;

    /**
     * Returns the directory that stores runtime files.
     * @return string the directory that stores runtime files.
     * Defaults to the "runtime" subdirectory under [[basePath]].
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath === null) {
            $this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
        }

        return $this->_runtimePath;
    }

    /**
     * Sets the directory that stores runtime files.
     * @param string $path the directory that stores runtime files.
     */
    public function setRuntimePath($path)
    {
        $this->_runtimePath = Yii::getAlias($path);
        Yii::setAlias('@runtime', $this->_runtimePath);
    }

    private $_vendorPath;

    /**
     * Returns the directory that stores vendor files.
     * @return string the directory that stores vendor files.
     * Defaults to "vendor" directory under [[basePath]].
     */
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }

        return $this->_vendorPath;
    }

    /**
     * Sets the directory that stores vendor files.
     * @param string $path the directory that stores vendor files.
     */
    public function setVendorPath($path)
    {
        $this->_vendorPath = Yii::getAlias($path);
        Yii::setAlias('@vendor', $this->_vendorPath);
        Yii::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        Yii::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }

    /**
     * Returns the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_get().
     * If time zone is not configured in php.ini or application config,
     * it will be set to UTC by default.
     * @return string the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     */
    public function getTimeZone()
    {
        return date_default_timezone_get();
    }

    /**
     * Sets the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_set().
     * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
     * @param string $value the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    /**
     * Returns the database connection component.
     * @return \yii\db\Connection the database connection.
     */
    public function getDb()
    {
        return $this->get('db');
    }

    /**
     * Returns the log dispatcher component.
     * @return \yii\log\Dispatcher the log dispatcher application component.
     */
    public function getLog()
    {
        return $this->get('log');
    }

    /**
     * Returns the error handler component.
     * @return \yii\web\ErrorHandler|\yii\console\ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }

    /**
     * Returns the cache component.
     * @return \yii\caching\Cache the cache application component. Null if the component is not enabled.
     */
    public function getCache()
    {
        return $this->get('cache', false);
    }

    /**
     * Returns the formatter component.
     * @return \yii\i18n\Formatter the formatter application component.
     */
    public function getFormatter()
    {
        return $this->get('formatter');
    }

    /**
     * Returns the request component.
     * @return \yii\web\Request|\yii\console\Request the request component.
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Returns the response component.
     * @return \yii\web\Response|\yii\console\Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * Returns the view object.
     * @return View|\yii\web\View the view application component that is used to render various view files.
     */
    public function getView()
    {
        return $this->get('view');
    }

    /**
     * Returns the URL manager for this application.
     * @return \yii\web\UrlManager the URL manager for this application.
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }

    /**
     * Returns the internationalization (i18n) component
     * @return \yii\i18n\I18N the internationalization application component.
     */
    public function getI18n()
    {
        return $this->get('i18n');
    }

    /**
     * Returns the mailer component.
     * @return \yii\mail\MailerInterface the mailer application component.
     */
    public function getMailer()
    {
        return $this->get('mailer');
    }

    /**
     * Returns the auth manager for this application.
     * @return \yii\rbac\ManagerInterface the auth manager application component.
     * Null is returned if auth manager is not configured.
     */
    public function getAuthManager()
    {
        return $this->get('authManager', false);
    }

    /**
     * Returns the asset manager.
     * @return \yii\web\AssetManager the asset manager application component.
     */
    public function getAssetManager()
    {
        return $this->get('assetManager');
    }

    /**
     * Returns the security component.
     * @return \yii\base\Security the security application component.
     */
    public function getSecurity()
    {
        return $this->get('security');
    }

    /**
     * Returns the configuration of core application components.
     * @see set()
     */
    public function coreComponents()
    {
        return [
            'log' => ['class' => 'yii\log\Dispatcher'],
            'view' => ['class' => 'yii\web\View'],
            'formatter' => ['class' => 'yii\i18n\Formatter'],
            'i18n' => ['class' => 'yii\i18n\I18N'],
            'mailer' => ['class' => 'yii\swiftmailer\Mailer'],
            'urlManager' => ['class' => 'yii\web\UrlManager'],
            'assetManager' => ['class' => 'yii\web\AssetManager'],
            'security' => ['class' => 'yii\base\Security'],
        ];
    }

    /**
     * Terminates the application.
     * This method replaces the `exit()` function by ensuring the application life cycle is completed
     * before terminating the application.
     * @param int $status the exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param Response $response the response to be sent. If not set, the default application [[response]] component will be used.
     * @throws ExitException if the application is in testing mode
     */
    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ? : $this->getResponse();
            $response->send();
        }

        if (YII_ENV_TEST) {
            throw new ExitException($status);
        } else {
            exit($status);
        }
    }

    /**
     * Configures [[Yii::$container]] with the $config
     *
     * @param array $config values given in terms of name-value pairs
     * @since 2.0.11
     */
    public function setContainer($config)
    {
        Yii::configure(Yii::$container, $config);
    }
}
