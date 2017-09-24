<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**Application是所有应用主体的基类
 * Application is the base class for all application classes.
 *先去官网看看有关应用主体的介绍
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
     * 应用主体的名字
     * @var string the application name.
     */
    public $name = 'My Application';
    /**
     * 字符集属性
     * @var string the charset currently used for the application.
     */
    public $charset = 'UTF-8';
    /**
     * 终端用户使用的语言属性，强烈建议符合IETF语言标签。
     * @var string the language that is meant to be used for end users. It is recommended that you
     * use [IETF language tags](http://en.wikipedia.org/wiki/IETF_language_tag). For example, `en` stands
     * for English, while `en-US` stands for English (United States).
     * en英文，en-US美式英文，zh-CN简体中文，zh中文
     * @see sourceLanguage 参考这个
     */
    public $language = 'en-US';
    /**
     * 应用主体的原语言，主要指的是提示信息和视图文件里写入的文字的语言
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
     * 不使用布局时，在返回ajax的响应时非常有用
     */
    public $layout = 'main';
    /**
     * @var string the requested route
     * 字符串，请求路径，含控制器和动作信息，如  "stockin/create"
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
     *在bootstrap()中，每个组件或模块将会实例化，若某个组件实现了BootstrapInterface接口，还会调用其bootstrap方法
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
     * 根据Yii官网所说，这里的模块，应该是包含当前应用主体的子模块
     * 深入理解模块和应用主体的概念和关系非常重要
     * 根据我的想法和思考，大概是这样的：
     * web\Application是应用主体，console\Application也是应用主体，API\Application也是应用主体
     * 目前想来，就是只有这三种应用主体了
     * 那么模块呢？模块是一个独立的软件单元，它们就附着在应用主体下，包含MVC目录和其他支持的组件，模块还可以有自己的子模块。说到包含mvc，三个应用主体都有，那应用主体
     * 岂不是符合模块的概念？不错，从这个角度来看，可以把的应用主体和模块等同起来。（base\Application继承Module可以看出点证据）
     * 官网也说过，模块就是小应用主体
     * 我们知道web\Application里可以加载模块，比如gii,rbac等，这些其实是子模块，此时的父模块，就是其应用主体本身而已。
     * 它俩的区别吧，官网有句话：应用主体可以单独部署，但是模块不可以，必须属于某个应用主体
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
        //把自己$this,通过Module继承而来的静态方法放到自己的静态属性Loadmodules中，
        //webApp属于（Module)模块的一种
        static::setInstance($this);

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
     * 它初始化了几个重要的应用属性（按照官网的说法，这些属于高级属性）
     * It initializes several important application properties.
     * 子类如果覆盖这个方法时，请记得调用父类的方法
     * If you override this method, please make sure you call the parent implementation.
     * @param array $config the application configuration
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     * 注意，设置的三个路径都不是application而是其父类Module的，比如_basePath,_vendorPath,_runtimePath
     * 还确定了时区，利用date_default_timezone_set()
     * 设置了容器静态类的属性（如果有的话）
     * 以上的设置都是设置完之后unset $config
     * populate(填充了)几个核心组件（这些组件写死在base\Application基类里的）
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

        //把核心组件和配置文件里的组件合并起来
        // merge core components with custom components
        foreach ($this->coreComponents() as $id => $component) {
            //配置文件里的组件和核心组件是否重名了，重名的话，优先使用配置文件里的，因为配置文件属于动态的
            //配置文件就是随时可以更改配置的，而更改核心组件列表就算是更改程序源代码了。虽然实现效果都可以，但是
            //一般还是在配置文件中做更改，尽量不要动程序源代码，避免合作开发时产生问题。如果自己一个人开发，想干啥就干啥
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            //组件一定要有class
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
        //开始推土机过程，推土机形象不？
        $this->bootstrap();
    }

    /**
     * 初始化扩展，并执行bootstrap里的组件,这是应用主体启动之初做得事情（比如log组件，gii模块）
     * Initializes extensions and executes bootstrap components.
     * 该方法在init()里调用，在应用主体配置完成之后调用
     * This method is called by [[init()]] after the application has been fully configured.
     * 若有覆盖该方法，请确保调用父类的实现
     * If you override this method, make sure you also call the parent implementation.
     */
    protected function bootstrap()
    {
        //读取扩展里的组件
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
                //官网说明，如果组件和模块同名，那么优先组件。这从代码就能看出来，第一个if分支，比第二个else if分支优先高。
                if ($this->has($class)) {//服务定位器中是否已注册
                    $component = $this->get($class);//得到这个实例化的组件（其中有服务定位器模式的实现）
                } elseif ($this->hasModule($class)) {//再看Module中是否有（这下看出组件优先级比模块高了吧?)
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
     * 注册错误处理器组件，作为项目整体的错误处理器对象
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
            //添加一个组件
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            //取得一个组件，并调用其register()方法
            $this->getErrorHandler()->register();
        }
    }

    /**
     * 返回模块的唯一标识符，在当前应用主体里的所有模块（再次说明模块是依附于应用主体的）
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * 由于当前对象是应用主体(不是模块），该方法总是返回''
     * Since this is an application instance, it will always return an empty string.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return '';
    }

    /**
     * 设置应用主体的根目录，和@app别名
     * Sets the root directory of the application and the @app alias.
     * 该方法只在构造函数的开始部分调用一次
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the application.  根目录
     * @property string the root directory of the application.
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        //在父类Module中设置应用主体的根目录
        parent::setBasePath($path);
        //全局助手类Yii设置根目录别名
        Yii::setAlias('@app', $this->getBasePath());
    }

    /**
     * 执行应用主体（任务）
     * Runs the application.
     * 这就是应用主体解析客户端http请求，处理请求，过滤，事件，响应等一系列后续处理的开始
     * This is the main entrance of an application.
     * 0表示正常结束  非0表示非正常。
     * @return int the exit status (0 means normal, non-zero values mean abnormal)
     */
    public function run()
    {
        try {

            $this->state = self::STATE_BEFORE_REQUEST;//标志应用主体的状态
            $this->trigger(self::EVENT_BEFORE_REQUEST);//触发事件beforeRequest,底层使用php原生函数call_user_func

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);//触发事件afterRequest

            $this->state = self::STATE_SENDING_RESPONSE;
            //最终调用send方法，完成内容的发送,如果之前已经sent，则不会再次输出。
            $response->send();

            $this->state = self::STATE_END;
            //正常来说，一旦返回就到了入口文件了，也就是说本次http请求就结束了，或者说浏览器请求的目的已经达到。
            //但是，接下来还有shutdown函数，但这是服务端自己的事了，已经与浏览器无关了，但是保不齐shutdown函数里也可能有echo之类的东西，未尝不可。
            return $response->exitStatus;

        } catch (ExitException $e) {

            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;

        }
    }

    /**
     * 处理http请求
     * Handles the specified request.
     *该方法应该返回一个Response实例或其子类
     * This method should return an instance of [[Response]] or its child class
     * 返回的对象表示请求的处理结果
     * which represents the handling result of the request.
     *
     *$request  request组件，表示将要处理的http请求
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    abstract public function handleRequest($request);

    private $_runtimePath;

    /**返回runtime目录
     * Returns the directory that stores runtime files.
     * @return string the directory that stores runtime files.
     * Defaults to the "runtime" subdirectory under [[basePath]].默认的runtime目录就是BasePath的子目录
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath === null) {
            //BasePath是重中之重，好多其他的目录，都是基于它创建
            $this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
        }

        return $this->_runtimePath;
    }

    /**
     * 设置runtime目录及别名（默认由应用主题启动之初调用，但该方法是public的，故如果修改runtime目录，
     * 可以在外部直接调用该方法生效
     * Sets the directory that stores runtime files.
     * @param string $path the directory that stores runtime files.
     */
    public function setRuntimePath($path)
    {
        //可以使用别名来设置runtime目录（不必是文件系统的绝对路径）
        $this->_runtimePath = Yii::getAlias($path);
        Yii::setAlias('@runtime', $this->_runtimePath);
    }

    private $_vendorPath;

    /**
     * 获得vendor路径
     * Returns the directory that stores vendor files.
     * @return string the directory that stores vendor files.
     * Defaults to "vendor" directory under [[basePath]].默认是basePath子目录
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
        //vendor路径的别名
        Yii::setAlias('@vendor', $this->_vendorPath);
        //bower目录别名（bower属于npm的插件，可以安装Jquery,bootstrap等，由twitter开发）
        Yii::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        //npm目录的别名（npm是Node的包管理器）
        Yii::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }

    /**
     * 返回应用主体使用的时区
     * Returns the time zone used by this application.
     * 简单地包装了php原生函数date_default_timezone_get()
     * This is a simple wrapper of PHP function date_default_timezone_get().
     * 如果没有在php.ini或者应用主体配置文件中设置，那么就会设置成UTC
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
     * 设置应用主体的时区
     * Sets the time zone used by this application.
     * 也是简单地使用php原生函数 date_default_timezone_set()
     * This is a simple wrapper of PHP function date_default_timezone_set().
     * 可以去php官网查看可用的时区列表
     * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
     * @param string $value the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    /**
     * 返回数据库连接组件db，已实例化的对象
     * Returns the database connection component.
     * @return \yii\db\Connection the database connection.
     */
    public function getDb()
    {
        return $this->get('db');
    }

    /**
     * 返回日志组件的实例，组件名是log，实际是dispatcher类。
     * Returns the log dispatcher component.
     * @return \yii\log\Dispatcher the log dispatcher application component.
     */
    public function getLog()
    {
        return $this->get('log');
    }

    /**
     * 返回错误处理器组件实例（web的，console的）
     * Returns the error handler component.
     * @return \yii\web\ErrorHandler|\yii\console\ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }

    /**
     * 返回缓存组件，如果组件还未启用（not enabled）则返回null（在组件类里，第二个参数false表示找不到组件时返回null而不是抛出异常）
     * Returns the cache component.
     * @return \yii\caching\Cache the cache application component. Null if the component is not enabled.
     */
    public function getCache()
    {
        return $this->get('cache', false);
    }

    /**返回格式化组件实例，格式化一般用于处理国际化涉及的语言字符，货币符号等
     * Returns the formatter component.
     * @return \yii\i18n\Formatter the formatter application component.
     */
    public function getFormatter()
    {
        return $this->get('formatter');
    }

    /**
     * 返回请求组件实例（web的，console的）
     * Returns the request component.
     * @return \yii\web\Request|\yii\console\Request the request component.
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * 返回响应组件实例（web的，console的）
     * Returns the response component.
     * @return \yii\web\Response|\yii\console\Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * 返回View组件实例，该实例用来渲染许多视图文件
     * Returns the view object.
     * @return View|\yii\web\View the view application component that is used to render various view files.
     */
    public function getView()
    {
        return $this->get('view');
    }

    /**
     * 返回URL管理器实例，属于应用主体级别，本身也是个组件
     * Returns the URL manager for this application.
     * @return \yii\web\UrlManager the URL manager for this application.
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }

    /**返回国际化（i18n)组件实例
     * Returns the internationalization (i18n) component
     * @return \yii\i18n\I18N the internationalization application component.
     */
    public function getI18n()
    {
        return $this->get('i18n');
    }

    /**
     * 返回邮件组件实例
     * Returns the mailer component.
     * @return \yii\mail\MailerInterface the mailer application component.
     */
    public function getMailer()
    {
        return $this->get('mailer');
    }

    /**
     * 返回权限管理器组件实例，属于应用主体级别。
     * Returns the auth manager for this application.
     * 不启用时返回null（第二个参数是false),不是默认的抛异常
     * @return \yii\rbac\ManagerInterface the auth manager application component.
     * Null is returned if auth manager is not configured.
     */
    public function getAuthManager()
    {
        return $this->get('authManager', false);
    }

    /**
     * 返回前端资源（Asset)组件实例
     * Returns the asset manager.
     * @return \yii\web\AssetManager the asset manager application component.
     */
    public function getAssetManager()
    {
        return $this->get('assetManager');
    }

    /**
     * 返回安全组件实例
     * Returns the security component.
     * @return \yii\base\Security the security application component.
     */
    public function getSecurity()
    {
        return $this->get('security');
    }

    /**
     * 返回核心组件列表（一个应用主体必备的几个组件）
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
     * 终止应用主体的运行
     * Terminates the application.
     * 该方法替代了php原生函数exit(),用来确保应用主体的生命周期走完全程后再终止应用主体的运行
     * This method replaces the `exit()` function by ensuring the application life cycle is completed
     * （可以理解为软终止，不是强制终止）
     * before terminating the application.
     * 终止退出码，默认是0，非0表示非正常退出
     * @param int $status the exit status (value 0 means normal exit while other values mean abnormal exit).
     * 响应实体，如果没有设置的话，默认使用应用主体处理过程中的响应组件
     * @param Response $response the response to be sent. If not set, the default application [[response]] component will be used.
     * @throws ExitException if the application is in testing mode 应用主体运行在测试模式时抛出异常
     */
    public function end($status = 0, $response = null)
    {
        //切换应用主体状态[为处理完客户端的请求]（不管实际是否处理完客户端请求)
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        //还没有到发送响应的状态，并且应用主体还未结束。才能根据响应实例，向客户端发送http响应内容
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
     * 配置全局助手类YII的属性container
     * Configures [[Yii::$container]] with the $config
     *这个属性是服务器模式里容器的引用，可以据此实例化应用组件等其他php类，有解析依赖功能（控制反转）
     * @param array $config values given in terms of name-value pairs
     * @since 2.0.11
     */
    public function setContainer($config)
    {
        Yii::configure(Yii::$container, $config);
    }
}
