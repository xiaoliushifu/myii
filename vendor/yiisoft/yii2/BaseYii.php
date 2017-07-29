<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\UnknownClassException;
use yii\log\Logger;
use yii\di\Container;

/**
 * Gets the application start timestamp.
 * 系统启动时间，代码需要使用的地方microtime(true)-YII_BEGIN_TIME即可：主要用于
 * 日志里打印时间统计
 * debug模块里统计时间
 */
defined('YII_BEGIN_TIME') or define('YII_BEGIN_TIME', microtime(true));
/**
 * This constant defines the framework installation directory.
 * Yii2_PATH是指Yii框架的目录,是具体的一个目录，不是应用程序目录。
 * 是yii2,也就是当前目录。注意区分框架与应用这两个概念的区别
 * 在自动加载时，装填映射文件（classes.php）时有使用
 */
defined('YII2_PATH') or define('YII2_PATH', __DIR__);
/**
 * This constant defines whether the application should be in debug mode or not. Defaults to false.
 * YII_DEBUG经全局搜索，主要是if(YII_DEBUG)的方式应用在代码中，
 * 1：打trace级别的日志，系统中用trace级别的log打印某些配置项，或者运行组件等。在该常量为true时才会打印出来。
 * 2：错误的输出格式更全面简易，
 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
/**
 * This constant defines in which environment the application is running. Defaults to 'prod', meaning production environment.
 * You may define this constant in the bootstrap script. The value could be 'prod' (production), 'dev' (development), 'test', 'staging', etc.
 * YII_ENV是个开关常量，不在代码中具体使用，主要影响如下布尔常量的定义：
 * YII_ENV_PROD  原生代码里没有用到，估计留给开发者使用
 * YII_ENV_DEV  影响一些组件或模块的引入，如debug模块，gii模块；还有一些模块是否预先加载，如gii模块提前加载
 * YII_ENV_TEST 暂不详
 * 如上的常量，会在代码中具体使用
 */
defined('YII_ENV') or define('YII_ENV', 'prod');
/**
 * Whether the the application is running in production environment
 */
defined('YII_ENV_PROD') or define('YII_ENV_PROD', YII_ENV === 'prod');
/**
 * Whether the the application is running in development environment
 */
defined('YII_ENV_DEV') or define('YII_ENV_DEV', YII_ENV === 'dev');
/**
 * Whether the the application is running in testing environment
 */
defined('YII_ENV_TEST') or define('YII_ENV_TEST', YII_ENV === 'test');

/**
 * This constant defines whether error handling should be enabled. Defaults to true.
 * 目前来看，该常量仅仅影响是否使用配置文件中的errHandler组件，加载到Yii::$app中。
 * 可以置为false，看看少了errHandler组件会有啥不同
 */
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', true);

/**
BaseYii是yii框架的核心助手类
 * 何时加载：在入口文件里被include。是紧接着PSR自动加载器被include之后的一行代码
 * BaseYii is the core helper class for the Yii framework.
 *
 * 不要直接使用BaseYii，而是，使用它的子类\Yii来扩展Baseyii的功能，覆盖BaseYii的某些方法等
 * Do not use BaseYii directly. Instead, use its child class [[\Yii]] which you can replace to
 * customize methods of BaseYii.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BaseYii
{
    /**该属性服务于Yii的自动加载机制
     * @var array class map used by the Yii autoloading mechanism.
	 *数组的key存放的是没有命名空间前缀的类名，数组的值是对应的类路径，或者类别名
     * The array keys are the class names (without leading backslashes), and the array values
     * are the corresponding class file paths (or path aliases). This property mainly affects
	 * $classMap属性在BaseYii被包含之后，由外部文件YII2_PATH/classes.php里的数组填充，是事先定义好的类名与路径对应* 关系, 看了一下数组里有322个元素
	 * 在autoload方法里会优先使用这个属性
     * how [[autoload()]] works.
     * @see autoload()
     */
    public static $classMap = [];

    /**全局静态属性，一般是console应用app，或者web应用app对象的实例
     * @var \yii\console\Application|\yii\web\Application the application instance
     */
    public static $app;

    /** 静态路径别名列表，和classMap属性相辅相成
	 * 默认Yii框架自带了一个路径别名，@yii表示的是D:.....\www\basic\vendor\yiisoft\yii2这个绝对字符串
	 * 注意，有可能是二维数组，暂时先记一下
     * @var array registered path aliases
     * @see getAlias()
     * @see setAlias()
     */
    public static $aliases = ['@yii' => __DIR__];

    /**
	 * 静态属性，它其实是一个引用指针，指向了一个类，该类是封装了依赖注入（DI）功能的容器类，
	 * 该属性是何时赋值的？入口文件的第6行代码里，随着Yii.php被include，通过new 容器类直接赋值的
	 * Yii::$container = new yii\di\Container();
	 * 就是上面那一行代码。
     * @var Container the dependency injection (DI) container used by [[createObject()]].
     * You may use [[Container::set()]] to set up the needed dependencies of classes and
     * their initial property values.
     * @see createObject()
     * @see Container
     */
    public static $container;


    /**
	 *  这个应该是框架自带的友好方法，跟应用没啥关系
     * Returns a string representing the current version of the Yii framework.
     * @return string the version of Yii framework
     */
    public static function getVersion()
    {
        return '2.0.11.2';
    }

    /**别名转换为真实的文件系统路径
     * Translates a path alias into an actual path.
     *
     * The translation is done according to the following procedure:
     * 带有“@”号的才算是别名，否则不是别名，直接返回对应的值，也就是文件系统路径，无需再次解析
     * 1. If the given alias does not start with '@', it is returned back without change;
	 * 否则按照最长别名优先原则来解析
     * 2. Otherwise, look for the longest registered alias that matches the beginning part
     *    of the given alias. If it exists, replace the matching part of the given alias with
     *    the corresponding registered path.
	 * 解析不了返回false或者抛异常呗
     * 3. Throw an exception or return false, depending on the `$throwException` parameter.
     * 比如@yii表示D:....\basic\vendor\yiisoft\yii2目录，那么@yii/web就是D:....\basic\vendor\yiisoft\yii2/web呗。
	 * （明白了吧，就是拼接）
     * For example, by default '@yii' is registered as the alias to the Yii framework directory,
     * say '/path/to/yii'. The alias '@yii/web' would then be translated into '/path/to/yii/web'.
     * 什么叫最长别名优先原则，比如有@foo和@foo/bar两个别名，现在要解析@foo/bar/config,那么最长优先
	 * 就是指在@foo和@foo/bar两者之间挑一个最长的，也就是@foo/bar(而不是@foo)来解析@foo/bar/config
     * If you have registered two aliases '@foo' and '@foo/bar'. Then translating '@foo/bar/config'
     * would replace the part '@foo/bar' (instead of '@foo') with the corresponding registered path.
     * This is because the longest alias takes precedence.
     *如果出现了@foo/barbar/config的话，那就得解析@foo（而不是@foo/bar）了，因为我们不能拆分@foo/barbar/config为
	 * @foo/bar和bar/config。barbar是不能拆的，你说呢？‘/'字符才算是分割符，注意。
     * However, if the alias to be translated is '@foo/barbar/config', then '@foo' will be replaced
     * instead of '@foo/bar', because '/' serves as the boundary character.
     *
	 * 注意，该方法仅仅是把别名解析出文件系统的绝对路径，该路径是否真实存在不得而知哟！
     * Note, this method does not check if the returned path exists or not.
     *
     * @param string $alias the alias to be translated.
     * @param bool $throwException whether to throw an exception if the given alias is invalid.
     * If this is false and an invalid alias is given, false will be returned by this method.
     * @return string|bool the path corresponding to the alias, false if the root alias is not previously registered.
     * @throws InvalidParamException if the alias is invalid while $throwException is true.
     * @see setAlias()

	 *这个方法叫getAlias，我觉得不太好，听名字好像是要获得别名（那个@开头的东西），
	 *而不是别名对应的文件系统路径。我觉得应该叫resolveAlias,或者getPathFromAlias更好一点
     */
    public static function getAlias($alias, $throwException = true)
    {
        if (strncmp($alias, '@', 1)) {
            // not an alias
            return $alias;
        }

        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            }

            foreach (static::$aliases[$root] as $name => $path) {
                if (strpos($alias . '/', $name . '/') === 0) {
                    return $path . substr($alias, strlen($name));
                }
            }
        }

        if ($throwException) {
            throw new InvalidParamException("Invalid path alias: $alias");
        }

        return false;
    }

    /**返回参数的root别名，啥叫root别名？
     * Returns the root alias part of a given alias.
	 * root别名需要首先通过setAlias注册，这里才能getRootAlias。还是不太明白
     * A root alias is an alias that has been registered via [[setAlias()]] previously.
     * If a given alias matches multiple root aliases, the longest one will be returned.
     * @param string $alias the alias
     * @return string|bool the root alias, or false if no root alias is found
     */
    public static function getRootAlias($alias)
    {
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

		//额外的话题，这里使用的是static::$aliases,而不是self::$aliases，知道区别不？
        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $root;
            }

            foreach (static::$aliases[$root] as $name => $path) {
                if (strpos($alias . '/', $name . '/') === 0) {
                    return $name;
                }
            }
        }

        return false;
    }

    /**
	*  注册路径别名 
	*  何时使用：在yii的高级模板里，入口文件会包含一个bootstrap.php，在这个文件里，使用
	 * 该方法设置了诸如 frontend,backend，common，console等别名对应的文件系统路径。
	 * 这是框架第一次使用这个方法。
     * Registers a path alias.
     * 路径别名是一个较短的字符表示一个较长的路径的机制
     * A path alias is a short name representing a long path (a file path, a URL, etc.)
	 * 比如属性$Aliases里的@yii就是别名
     * For example, we use '@yii' as the alias of the path to the Yii framework directory.
     * 路径别名必须以@开头，这样后续的解析规则才能进行，否则就乱套了。
	 * 调用该方法设置别名时，如果没有@字符，则setAlias方法体里也会加上的。
     * A path alias must start with the character '@' so that it can be easily differentiated
     * from non-alias paths.
     * 该方法并不判断文件系统路径是否真实存在，仅仅是把一个别名关联上一个文件系统路径而已
     * Note that this method does not check if the given path exists or not. All it does is
     * to associate the alias with the path.
     * 拖尾的'/'和'\'字符都会被trim掉
     * Any trailing '/' and '\' characters in the given path will be trimmed.
     *
     * @param string $alias the alias name (e.g. "@yii"). It must start with a '@' character.
     * It may contain the forward slash '/' which serves as boundary character when performing
     * alias translation by [[getAlias()]].
	 * 如果第二个参数是null的话，代表删除这个别名
     * @param string $path the path corresponding to the alias. If this is null, the alias will
     * be removed. Trailing '/' and '\' characters will be trimmed. This can be
     * 刚才一直说别名最终解析成文件系统路径(文件路径或目录路径），这其实只是大多数的用法，别名还可以解析成url。
     * - a directory or a file path (e.g. `/tmp`, `/tmp/main.txt`)
     * - a URL (e.g. `http://www.yiiframework.com`)
	 * 路径还可以再包含别名，那么这里就会先解析路径中包含的别名，再存到$aliases里，保证最终的别名值是不可再解析的* * 了
     * - a path alias (e.g. `@yii/base`). In this case, the path alias will be converted into the
     *   actual path first by calling [[getAlias()]].
     * 
     * @throws InvalidParamException if $path is an invalid alias.
     * @see getAlias()
     */
    public static function setAlias($alias, $path)
    {
		//使用php内置函数strncmp比较$alias的第一个字符是不是‘@’符号，据说比substr快呢！
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        $pos = strpos($alias, '/');
		/**哈哈，下面这行代码之前我看错了，应该是$pos和false全等比较，然后把比较结果返回给$root，
		而不是$root与$pos一块和false比较,又涨知识了，真好
		这行代码其实是解析路径别名里是否还有其他子路径别名的情况，如果有的话，先解析出跟路径别名$root,
		如果没有，那$root就是根路径别名
		*/
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
		//路径不为空是设置，路径为null时还有清除的功能呢
        if ($path !== null) {
			//解析$path是否还包含路径别名，若包含就递归再去解析
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
            if (!isset(static::$aliases[$root])) {
				//这是没有设置过这个路径别名，本次的路径别名里也不含子路径别名的情况
                if ($pos === false) {
                    static::$aliases[$root] = $path;
				//这是没有设置过路径别名，但本次的路径别名里包含子路径别名的情况（后续注意跟踪这例子）
                } else {
                    static::$aliases[$root] = [$alias => $path];
                }
            } elseif (is_string(static::$aliases[$root])) {
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [
                        $alias => $path,
                        $root => static::$aliases[$root],
                    ];
                }
            } else {
                static::$aliases[$root][$alias] = $path;
                krsort(static::$aliases[$root]);
            }
		//清除指定的路径别名
        } elseif (isset(static::$aliases[$root])) {
            if (is_array(static::$aliases[$root])) {
                unset(static::$aliases[$root][$alias]);
            } elseif ($pos === false) {
                unset(static::$aliases[$root]);
            }
        }
    }

    /**
	* Yii框架自己的加载器
     * Class autoload loader.
     * This method is invoked automatically when PHP sees an unknown class.
     * The method will attempt to include the class file according to the following procedure:
     * 加载顺序或者说寻找顺序是什么呢？
	 *  首先从classMap属性中寻找
     * 1. Search in [[classMap]];
	 * 带有命名空间前缀的，尝试使用关联的路径别名机制去寻找
	 * 理解这点很重要，命名空间和别名是对应的
     * 2. If the class is namespaced (e.g. `yii\base\Component`), it will attempt
     *    to include the file associated with the corresponding path alias
     *    (e.g. `@yii/base/Component.php`);
     * 符合PSR-4标准的php类文件组织形式
     * This autoloader allows loading classes that follow the [PSR-4 standard](http://www.php-fig.org/psr/psr-4/)
     * and have its top-level namespace or sub-namespaces defined as path aliases.
     *比如，寻找的类不在classMap中，而是一个带有命名空间前缀的yii\bootstrap\Good.php，那么将把yii\bootstrap当做是
	 * @yii/bootstrap别名，然后去解析别名，得到文件系统路径。
     * Example: When aliases `@yii` and `@yii/bootstrap` are defined, classes in the `yii\bootstrap` namespace
     * will be loaded using the `@yii/bootstrap` alias which points to the directory where bootstrap extension
     * files are installed and all classes from other `yii` namespaces will be loaded from the yii framework directory.
     *
     * Also the [guide section on autoloading](guide:concept-autoloading).
     *
     * @param string $className the fully qualified class name without a leading backslash "\"
     * @throws UnknownClassException if the class does not exist in the class file
     */
    public static function autoload($className)
    {
        if (isset(static::$classMap[$className])) {
            $classFile = static::$classMap[$className];
            if ($classFile[0] === '@') {
                $classFile = static::getAlias($classFile);
            }
        } elseif (strpos($className, '\\') !== false) {
            $classFile = static::getAlias('@' . str_replace('\\', '/', $className) . '.php', false);
            if ($classFile === false || !is_file($classFile)) {
                return;
            }
        } else {
            return;
        }

        include($classFile);

        if (YII_DEBUG && !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
            throw new UnknownClassException("Unable to find '$className' in file: $classFile. Namespace missing?");
        }
    }

    /**实例化对象的静态好方法，根据参数的配置
     * Creates a new object using the given configuration.
     *这个方法就是一个全新的new操作符一样。
     * You may view this method as an enhanced version of the `new` operator.
	 * 这个方法可以根据类名，一个数组配置，或者一个匿名函数类创建对象，非常方便
     * The method supports creating an object based on a class name, a configuration array or
     * an anonymous function.
     *
     * Below are some usage examples:
     *
     * ```php   直接用类名
     * // create an object using a class name
     * $object = Yii::createObject('yii\db\Connection');
     *
     * // create an object using a configuration array
	 * 用创建对象需要的配置信息来创建对象。这个要特别说明一点，yii框架配置文件里的各个组件，主要都是靠这个方法来
	 * 实例化的，非常的常用呀（只是框架封装的强，不看源码绝不会知道）
     * $object = Yii::createObject([
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     * 类名带参数
     * // create an object with two constructor parameters
     * $object = \Yii::createObject('MyClass', [$param1, $param2]);
     * ```
     * 这个方法的亮点是使用了依赖注入机制。当然这不是BaseYii类的功能，而是容器类的功能。
     * Using [[\yii\di\Container|dependency injection container]], this method can also identify
     * dependent objects, instantiate them and inject them into the newly created object.
     * 第一个参数的类型有三种：  字符串，数组，或者回调。
     * @param string|array|callable $type the object type. This can be specified in one of the following forms:
     *  字符串类型的话，肯定就是类名
     * - a string: representing the class name of the object to be created
	 * 数组的话，一定要有class下标。而其他的下标则代表是实例化class后要设置的属性及属性值。非常常用！
     * - a configuration array: the array must contain a `class` element which is treated as the object class,
     *   and the rest of the name-value pairs will be used to initialize the corresponding object properties
	 * PHP回调，一般是一个匿名函数或者是[类或对象，方法]这样格式的数组。而且必须返回一个对象.
     * - a PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
     *   The callable should return a new instance of the object being created.
     *
     * @param array $params the constructor parameters
     * @return object the created object
     * @throws InvalidConfigException if the configuration is invalid.
     * @see \yii\di\Container
     */
    public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return static::$container->invoke($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        }

        throw new InvalidConfigException('Unsupported configuration type: ' . gettype($type));
    }

	/*嘿嘿，这里还藏了一个私有属性哟，不仔细看还差点忽略了呢，暂时没有注释*/
    private static $_logger;

    /** 
	 * 把获取日志组件的方法写在了这里，看来日志服务真的是一个每个应用的基础呀，而且
	 * 代码写死了使用yii\log\Logger类作为日志组件。
     * @return Logger message logger
     */
    public static function getLogger()
    {
        if (self::$_logger !== null) {
            return self::$_logger;
        }

        return self::$_logger = static::createObject('yii\log\Logger');
    }

    /**
     * Sets the logger object.
     * @param Logger $logger the logger object.
     */
    public static function setLogger($logger)
    {
        self::$_logger = $logger;
    }

    /**
	* trace级别的日志，主要在开发中记录执行工作流
     * Logs a trace message.
     * Trace messages are logged mainly for development purpose to see
     * the execution work flow of some code.
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     */
    public static function trace($message, $category = 'application')
    {
        if (YII_DEBUG) {
            static::getLogger()->log($message, Logger::LEVEL_TRACE, $category);
        }
    }

    /**
     * Logs an error message.
     * An error message is typically logged when an unrecoverable error occurs
     * during the execution of an application.
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     */
    public static function error($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_ERROR, $category);
    }

    /**
     * Logs a warning message.
     * A warning message is typically logged when an error occurs while the execution
     * can still continue.
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     */
    public static function warning($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_WARNING, $category);
    }

    /**
     * Logs an informative message.
	 * info级别的日志，主要记录一下比较重要的信息
     * An informative message is typically logged by an application to keep record of
     * something important (e.g. an administrator logs in).
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     */
    public static function info($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_INFO, $category);
    }

    /**
	 * 标记一段要分析的代码块，可以嵌套标记
     * Marks the beginning of a code block for profiling.
     * This has to be matched with a call to [[endProfile]] with the same category name.
     * The begin- and end- calls must also be properly nested. For example,
     *
     * ```php
     * \Yii::beginProfile('block1');
     * // some code to be profiled
     *     \Yii::beginProfile('block2');
     *     // some other code to be profiled
     *     \Yii::endProfile('block2');
     * \Yii::endProfile('block1');
     * ```
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see endProfile()
     */
    public static function beginProfile($token, $category = 'application')
    {
        static::getLogger()->log($token, Logger::LEVEL_PROFILE_BEGIN, $category);
    }

    /**标记一段要分析的代码块的结尾
     * Marks the end of a code block for profiling.
     * This has to be matched with a previous call to [[beginProfile]] with the same category name.
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see beginProfile()
     */
    public static function endProfile($token, $category = 'application')
    {
        static::getLogger()->log($token, Logger::LEVEL_PROFILE_END, $category);
    }

    /**
	 * 返回一段HTML高亮的字符串，这是Yii框架的炫耀吗？？？？这还是首次见到呀
     * Returns an HTML hyperlink that can be displayed on your Web page showing "Powered by Yii Framework" information.
     * @return string an HTML hyperlink that can be displayed on your Web page showing "Powered by Yii Framework" information
     */
    public static function powered()
    {
        return \Yii::t('yii', 'Powered by {yii}', [
            'yii' => '<a href="http://www.yiiframework.com/" rel="external">' . \Yii::t('yii',
                    'Yii Framework') . '</a>'
        ]);
    }

    /** 
	 * 按照指定的语言翻译一段信息
     * Translates a message to the specified language.
     * 是一个快捷方式。也就是yii\i18n\I18N::translate()方法的快捷方式
     * This is a shortcut method of [[\yii\i18n\I18N::translate()]].
     *
     * The translation will be conducted according to the message category and the target language will be used.
     *
     * You can add parameters to a translation message that will be substituted with the corresponding value after
     * translation. The format for this is to use curly brackets around the parameter name as you can see in the following example:
     *注意例子非常重要，说一百个字，加上一个例子就能起到画龙点睛的作用！
     * ```php
     * $username = 'Alexander';
     * echo \Yii::t('app', 'Hello, {username}!', ['username' => $username]);
     * ```
     *
     * Further formatting of message parameters is supported using the [PHP intl extensions](http://www.php.net/manual/en/intro.intl.php)
     * message formatter. See [[\yii\i18n\I18N::translate()]] for more details.
     *
     * @param string $category the message category.
     * @param string $message the message to be translated.
     * @param array $params the parameters that will be used to replace the corresponding placeholders in the message.
     * @param string $language the language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return string the translated message.
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        if (static::$app !== null) {
            return static::$app->getI18n()->translate($category, $message, $params, $language ?: static::$app->language);
        }

        $placeholders = [];
        foreach ((array) $params as $name => $value) {
            $placeholders['{' . $name . '}'] = $value;
        }

        return ($placeholders === []) ? $message : strtr($message, $placeholders);
    }

    /**  
	* 这个配置方法也非常常用，尤其在上述createObject之后，可以配置这个新出炉的对象
	*  非常常见，而且如果这个对象内部实现了php的__set方法的话，更有意思
     * Configures an object with the initial property values.
     * @param object $object the object to be configured
     * @param array $properties the property initial values given in terms of name-value pairs.
     * @return object the object itself
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
	*返回对象的属性，嘿，php还有这个函数呢？
	 *在指定的上下文中调用的话，还能返回private的属性。
	 * 绝不止表面，详情去php.net查看。
     * Returns the public member variables of an object.
     * This method is provided such that we can get the public member variables of an object.
     * It is different from "get_object_vars()" because the latter will return private
     * and protected variables if it is called within the object itself.
     * @param object $object the object to be handled
     * @return array the public member variables of the object
     */
    public static function getObjectVars($object)
    {
        return get_object_vars($object);
    }
}
