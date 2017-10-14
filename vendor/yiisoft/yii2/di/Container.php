<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use ReflectionClass;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**容器实现了wiki上所说的依赖注入
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 *  依赖注入容器本质上是一个对象，这个对象知道如何实例化和配置其他对象以及所有它依赖的对象
 * A dependency injection (DI) container is an object that knows how to instantiate and configure objects and
 * all their dependent objects. For more information about DI, please refer to
 * [Martin Fowler's article](http://martinfowler.com/articles/injection.html).
 *容器支持构造器注入和属性注入（虽然尚不明白）
 * Container supports constructor injection as well as property injection.
 *使用容器之前，首先你应该通过调用容器的set方法，来配置好某个类A的依赖项。
 * To use Container, you first need to set up the class dependencies by calling [[set()]].
 然后你调用get方法获得A，容器就自动帮你实例化依赖的对象，并注入它们到A中，再进行配置然后最终返回新创建的类A
 *明白了，前提是我得先把一个类的依赖配置好（set up）
 * You then call [[get()]] to create a new class object. Container will automatically instantiate
 * dependent objects, inject them into the object being created, configure and finally return the newly created object.
 *默认，BaseYii的属性$container是容器类的一个引用，它在BaseYii的createObject方法里使用，
 * By default, [[\Yii::$container]] refers to a Container instance which is used by [[\Yii::createObject()]]
 * 用来创建新的对象实例。创建对象时，你可以用BaseYii::createObject方法来代替new操作符
 * to create new object instances. You may use this method to replace the `new` operator
 * 使用这个方法有自动解析依赖并带有默认属性配置的好处
 * when creating a new object, which gives you the benefit of automatic dependency resolution and default
 * property configuration.
 *
 *例子开始了，很重要呀！
 * Below is an example of using Container:
 *
 * ```php
 * namespace app\models;
 *
 * use yii\base\Object;
 * use yii\db\Connection;
 * use yii\di\Container;
 *
 * interface UserFinderInterface
 * {
 *     function findUser();
 * }
 *
 * class UserFinder extends Object implements UserFinderInterface
 * {
 *     public $db;
 *
 *     public function __construct(Connection $db, $config = [])
 *     {
 *         $this->db = $db;
 *         parent::__construct($config);
 *     }
 *
 *     public function findUser()
 *     {
 *     }
 * }
 *
 * class UserLister extends Object
 * {
 *     public $finder;
 *
 *     public function __construct(UserFinderInterface $finder, $config = [])
 *     {
 *         $this->finder = $finder;
 *         parent::__construct($config);
 *     }
 * }
 *
 * $container = new Container;
 * $container->set('yii\db\Connection', [
 *     'dsn' => '...',
 * ]);
 * $container->set('app\models\UserFinderInterface', [
 *     'class' => 'app\models\UserFinder',
 * ]);
 * $container->set('userLister', 'app\models\UserLister');
 *
 * $lister = $container->get('userLister');
 *
 * // which is equivalent to:
 *
 * $db = new \yii\db\Connection(['dsn' => '...']);
 * $finder = new UserFinder($db);
 * $lister = new UserLister($finder);
 * ```
 *上面的例子似懂非懂，那我们就看看guide吧：
 * http://www.yiiframework.com/doc-2.0/guide-concept-di-container.html
 * For more details and usage information on Container, see the [guide article on di-containers](guide:concept-di-container).
 *
 * @property array $definitions The list of the object definitions or the loaded shared objects (type or ID =>
 * definition or instance). This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Container extends Component
{
    /**
	 * 存储单例对象数组，由其类型名作为下标存储
	 *依赖注入解析时(get方法)，优先从这个数组里查看是否有指定的类名或接口名
     * @var array singleton objects indexed by their types
     */
    private $_singletons = [];
    /**
	 *  存储对象的定义，对象尚未创建，下标是对象的类型
	 *  依赖注入解析时(get方法），优先级仅次于单例数组，单例数组里没有时，才会判断这个
	 * 数组里是否有指定类的定义信息
     * @var array object definitions indexed by their types
     */
    private $_definitions = [];
    /** 构造方法的参数，以对象的类型为下标，哪个对象？
     * @var array constructor parameters indexed by object types
     */
    private $_params = [];
    /**
	 * 缓存的反射类对象，下标是接口名或者类名，值是反射对象。是如下代码生成的：
	 * $reflection = new ReflectionClass($class)
	 * 那么属性中将会存储：$_reflections[$class]=$reflection
     * @var array cached ReflectionClass objects indexed by class/interface names
     */
    private $_reflections = [];
    /**缓存的依赖项，由类名或接口名为下标。
	 * 每个类名都关联一系列的构造函数的参数类型或构造函数参数的默认值
	 *  和上面的$_reflections属性是相辅相成的
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private $_dependencies = [];


    /** 返回一个实例对象
     * Returns an instance of the requested class.
     * 你可以提供构造函数的参数和对象配置项，这将在实例化对象时用得到
     * You may provide constructor parameters (`$params`) and object configurations (`$config`)
     * that will be used during the creation of the instance.
     * 如果一个类实现了\yii\base\Configurable接口，那么$config参数将作为类构造函数的最后一个参数
     * If the class implements [[\yii\base\Configurable]], the `$config` parameter will be passed as the last
	   否则，这些配置项将在实例化方法之后配置给当前新创建的对象
     * parameter to the class constructor; Otherwise, the configuration will be applied *after* the object is
     * instantiated.
     * 注意，如果这个类先前是通过setSingleton方法注册的，那么每次get时它将返回单例，不会重复创建对象
     * Note that if the class is declared to be singleton by calling [[setSingleton()]],
     * the same instance of the class will be returned each time this method is called.
	 * 这种情况下，构造函数的参数和对象的配置项仅仅在第一次实例化对象时使用
     * In this case, the constructor parameters and object configurations will be used
     * only if the class is instantiated the first time.
     *
	 *  对象在get之前，必须执行set或setSingleton完成注册（依赖注入的注册），Container容器才能自动进行依赖注入的解析，其实不完全是，比如errorHandler组件的实例化过程，就直接从get方法开始的，然后build.并没有事先set。
	 * errorHandler是在web/application执行构造函数之前实例化的
     * @param string $class the class name or an alias name (e.g. `foo`) that was previously registered via [[set()]]
     * or [[setSingleton()]].
	 * params参数是一个构造函数的参数列表，有序的提供给构造函数的声明里，注意参数的顺序
     * @param array $params a list of constructor parameter values. The parameters should be provided in the order
     * they appear in the constructor declaration. If you want to skip some parameters, you should index the remaining
     * ones with the integers that represent their positions in the constructor parameter list.
	 *  config参数是一系列的键值对，在初始化对象时使用
     * @param array $config a list of name-value pairs that will be used to initialize the object properties.
     * @return object an instance of the requested class.
     * @throws InvalidConfigException if the class cannot be recognized or correspond to an invalid definition
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     */
    public function get($class, $params = [], $config = [])
    {
        //如果是个单例，则从缓存里返回
        if (isset($this->_singletons[$class])) {
            // singleton
            return $this->_singletons[$class];
            //没有预先定义在_definitions里
        } elseif (!isset($this->_definitions[$class])) {
            //否则就去build
            return $this->build($class, $params, $config);
        }

        $definition = $this->_definitions[$class];

        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($class, $params));
            $object = call_user_func($definition, $this, $params, $config);
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            if ($concrete === $class) {
                $object = $this->build($class, $params, $config);
            } else {
                $object = $this->get($concrete, $params, $config);
            }
        } elseif (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        if (array_key_exists($class, $this->_singletons)) {
            // singleton
            $this->_singletons[$class] = $object;
        }

        return $object;
    }

    /**  注册一个类的定义信息到容器中
     * Registers a class definition with this container.
     *

	 *比如，注册一个类名
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * $container->set('yii\db\Connection');
     *
	 * 注册一个接口及接口的实现
     * // register an interface
     * // When a class depends on the interface, the corresponding class
     * // will be instantiated as the dependent object
     * $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
     *  注册一个类别名，注意不要和路径的别名搞混了，那个是带有‘@'字符前缀的，路径别名。
	 *  这里应该就是类别名
     * // register an alias name. You can use $container->get('foo')
     * // to create an instance of Connection
     * $container->set('foo', 'yii\db\Connection');
     *
     * // register a class with configuration. The configuration
     * // will be applied when the class is instantiated by get()
     * $container->set('yii\db\Connection', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *  注册一个类别名的类定义信息，这个类定义信息中必须要含有class下标指向的类
     * // register an alias name with class configuration
     * // In this case, a "class" element is required to specify the class
     * $container->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *注册一个php回调
     * // register a PHP callable
     * // The callable will be executed when $container->get('db') is called
     * $container->set('db', function ($container, $params, $config) {
     *     return new \yii\db\Connection($config);
     * });
     * ```
     *  注意，注册依赖注入时，如果之前注册过同名的，会被后来的同名的覆盖掉
	 *  这时可以通过调用has方法判断一下，之前有无别人注册过这个
     * If a class definition with the same name already exists, it will be overwritten with the new one.
     * You may use [[has()]] to check if a class definition already exists.
     * 参数一是类名，接口名或者类别名，类名一般是带有命名空间的
     * @param string $class class name, interface name or alias name
	 * 第二个参数，和第一个参数有关，可以有以下几种类型：
     * @param mixed $definition the definition associated with `$class`. It can be one of the following:
     * * php回调函数
     * - a PHP callable: The callable will be executed when [[get()]] is invoked. The signature of the callable
     *   should be `function ($container, $params, $config)`, where `$params` stands for the list of constructor
     *   parameters, `$config` the object configuration, and `$container` the container object. The return value
     *   of the callable will be returned by [[get()]] as the object instance requested.
	 *    一个配置项数组
     * - a configuration array: the array contains name-value pairs that will be used to initialize the property
     *   values of the newly created object when [[get()]] is called. The `class` element stands for the
     *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
	 *  一个代表类名，或者接口名，或者类别名的字符串
     * - a string: a class name, an interface name or an alias name.
	 * 第三个参数很重要，代表构造函数的签名里使用到的参数，这些参数会在get对象时传递到对象的构造函数中使用
     * @param array $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     */
    public function set($class, $definition = [], array $params = [])
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        unset($this->_singletons[$class]);
        return $this;
    }

    /**还是注册一个依赖注入，只不过是注册到_singleton数组里，且值是null
     * Registers a class definition with this container and marks the class as a singleton class.
     *这个方法的大多数地方与set方法一样，除了一点，将来每次get都返回一样的实例，单例
     * This method is similar to [[set()]] except that classes registered via this method will only have one
     * instance. Each time [[get()]] is called, the same instance of the specified class will be returned.
     *
     * @param string $class class name, interface name or alias name
     * @param mixed $definition the definition associated with `$class`. See [[set()]] for more details.
     * @param array $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     * @see set()
     */
    public function setSingleton($class, $definition = [], array $params = [])
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        $this->_singletons[$class] = null;
        return $this;
    }

    /** 
	* 判断一个对象的定义信息是否在_definitions里
     * Returns a value indicating whether the container has the definition of the specified name.
     * @param string $class class name, interface name or alias name
     * @return bool whether the container has the definition of the specified name..
     * @see set()
     */
    public function has($class)
    {
        return isset($this->_definitions[$class]);
    }

    /**
	* 判断是否在单例数组_singletons里，第二个参数可以进一步判断在单例数组里的话，是否已经实例化了？
     * Returns a value indicating whether the given name corresponds to a registered singleton.
     * @param string $class class name, interface name or alias name
     * @param bool $checkInstance whether to check if the singleton has been instantiated.
     * @return bool whether the given name corresponds to a registered singleton. If `$checkInstance` is true,
     * the method should return a value indicating whether the singleton has been instantiated.
     */
    public function hasSingleton($class, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_singletons[$class]) : array_key_exists($class, $this->_singletons);
    }

    /**
	* 清除先前注册的一个依赖注入
     * Removes the definition for the specified name.
     * @param string $class class name, interface name or alias name
     */
    public function clear($class)
    {
        unset($this->_definitions[$class], $this->_singletons[$class]);
    }

    /**正常化类的定义，不太明白呀
     * Normalizes the class definition.
     * @param string $class class name
     * @param string|array|callable $definition the class definition
     * @return array the normalized class definition
     * @throws InvalidConfigException if the definition is invalid.
     */
    protected function normalizeDefinition($class, $definition)
    {
        if (empty($definition)) {
            return ['class' => $class];
        } elseif (is_string($definition)) {
            return ['class' => $definition];
        } elseif (is_callable($definition, true) || is_object($definition)) {
            return $definition;
        } elseif (is_array($definition)) {
            if (!isset($definition['class'])) {
                if (strpos($class, '\\') !== false) {
                    $definition['class'] = $class;
                } else {
                    throw new InvalidConfigException("A class definition requires a \"class\" member.");
                }
            }
            return $definition;
        } else {
            throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
        }
    }

    /** 直接获得_definitions对象。
     * Returns the list of the object definitions or the loaded shared objects.
     * @return array the list of the object definitions or the loaded shared objects (type or ID => definition or instance).
     */
    public function getDefinitions()
    {
        return $this->_definitions;
    }

    /**产生一个指定类的实例
     * Creates an instance of the specified class.
	 * 这个方法是protected，是它实现依赖注入的解析，实例化这些依赖，注入到新创建的对象里
     * This method will resolve dependencies of the specified class, instantiate them, and inject
     * them into the new instance of the specified class.
     * @param string $class the class name
     * @param array $params constructor parameters
     * @param array $config configurations to be applied to the new instance
     * @return object the newly created instance of the specified class
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     */
    protected function build($class, $params, $config)
    {
        /* @var $reflection ReflectionClass */
        list ($reflection, $dependencies) = $this->getDependencies($class);

        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        if (!empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable')) {
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        } else {
            $object = $reflection->newInstanceArgs($dependencies);
			//注意，刚实例化$object,就立马遍历配置项进行类的初始化了呀，哈哈
            foreach ($config as $name => $value) {
                $object->$name = $value;
            }
            return $object;
        }
    }

    /**合并用户指定的构造函数参数到一个先前通过set注册的对象
     * Merges the user-specified constructor parameters with the ones registered via [[set()]].
     * @param string $class class name, interface name or alias name
     * @param array $params the constructor parameters
     * @return array the merged parameters
     */
    protected function mergeParams($class, $params)
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        } else {
            $ps = $this->_params[$class];
            foreach ($params as $index => $value) {
                $ps[$index] = $value;
            }
            return $ps;
        }
    }

    /**
     * protected的方法，返回$class的依赖项，要实例化时，构造函数所需的依赖
     * Returns the dependencies of the specified class.
     * 通过代码可知，实现原理就是：通过php反射机制，优先得到它的构造函数反射对象
     * 然后解析这个构造函数都需要哪几个什么类型的参数，是否是默认等。以此达到
     * 获得依赖的目的。
     * @param string $class class name, interface name or alias name
     * @return array the dependencies of the specified class.
     */
    protected function getDependencies($class)
    {
        //是否是曾经反射过的，缓存起来，避免下次再去反射，这就是缓冲思想
        //因为反射的代价应该很大（估计会搜索硬盘，读取类文件什么的，属于C底层的实现）
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        //初始化依赖数组
        $dependencies = [];
        //根据类名，创建对应类的反射对象ReflectionClass
        $reflection = new ReflectionClass($class);

        //类反射对象获得类的构造函数反射对象（ReflectionMethod)
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            //构造函数反射对象，
            //再通过遍历获得函数参数反射对象(ReflectionParameter)数组
            foreach ($constructor->getParameters() as $param) {
                //分别判断参数反射对象的性质
                //1 默认值
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                    //2 不是默认值，则根据类名c，再去实例化之
                } else {
                    $c = $param->getClass();
                    //依赖的实例化，使用Instance::of()方法，暂不知
                    $dependencies[] = Instance::of($c === null ? null : $c->getName());
                }
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /** 把依赖项解析出来并实例化它们
     * Resolves dependencies by replacing them with the actual object instances.
     * @param array $dependencies the dependencies
     * @param ReflectionClass $reflection the class reflection associated with the dependencies
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    protected function resolveDependencies($dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => $dependency) {
			//这个Instance,并没有被导入，是如何找到它的呢？是自动加载吗？
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    $dependencies[$index] = $this->get($dependency->id);
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }
        return $dependencies;
    }

    /** 调用一个回调，这是另一个返回对象的另一种形式，一种依赖注入里不同的方式
     * Invoke a callback with resolving dependencies in parameters.
     *
     * This methods allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allow calling function using named parameters.
     *
     * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * $formatString = function($string, \yii\i18n\Formatter $formatter) {
     *    // ...
     * }
     * Yii::$container->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
     * by the DI container as the second param to the callable.
     *
     * @param callable $callback callable to be invoked.
     * @param array $params The array of parameters for the function.
     * This can be either a list of parameters, or an associative array representing named function parameters.
     * @return mixed the callback return value.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @since 2.0.7
     */
    public function invoke(callable $callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
        } else {
            return call_user_func_array($callback, $params);
        }
    }

    /**
     * Resolve dependencies for a function.
     *这个方法实现了类似invoke的功能。
     * This method can be used to implement similar functionality as provided by [[invoke()]] in other
     * components.
     *
     * @param callable $callback callable to be invoked.
     * @param array $params The array of parameters for the function, can be either numeric or associative.
     * @return array The resolved dependencies.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @since 2.0.7
     */
    public function resolveCallableDependencies(callable $callback, $params = [])
    {
        if (is_array($callback)) {
            $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        } else {
            $reflection = new \ReflectionFunction($callback);
        }

        $args = [];

        $associative = ArrayHelper::isAssociative($params);

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (($class = $param->getClass()) !== null) {
                $className = $class->getName();
                if ($associative && isset($params[$name]) && $params[$name] instanceof $className) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                } elseif (!$associative && isset($params[0]) && $params[0] instanceof $className) {
                    $args[] = array_shift($params);
                } elseif (isset(Yii::$app) && Yii::$app->has($name) && ($obj = Yii::$app->get($name)) instanceof $className) {
                    $args[] = $obj;
                } else {
                    // If the argument is optional we catch not instantiable exceptions
                    try {
                        $args[] = $this->get($className);
                    } catch (NotInstantiableException $e) {
                        if ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw $e;
                        }
                    }

                }
            } elseif ($associative && isset($params[$name])) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif (!$associative && count($params)) {
                $args[] = array_shift($params);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $funcName = $reflection->getName();
                throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
            }
        }

        foreach ($params as $value) {
            $args[] = $value;
        }
        return $args;
    }

    /**
	* 注入一个类的定义到容器里，是set方法的一种封装而已。
     * Registers class definitions within this container.
     *
     * @param array $definitions array of definitions. There are two allowed formats of array.
     * The first format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: the definition associated with `$class`. Possible values are described in
     *    [[set()]] documentation for the `$definition` parameter. Will be passed to the [[set()]] method
     *    as the second argument `$definition`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'yii\web\Request' => 'app\components\Request',
     *     'yii\web\Response' => [
     *         'class' => 'app\components\Response',
     *         'format' => 'json'
     *     ],
     *     'foo\Bar' => function () {
     *         $qux = new Qux;
     *         $foo = new Foo($qux);
     *         return new Bar($foo);
     *     }
     * ]);
     * ```
     *
     * The second format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: array of two elements. The first element will be passed the [[set()]] method as the
     *    second argument `$definition`, the second one — as `$params`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'foo\Bar' => [
     *          ['class' => 'app\Bar'],
     *          [Instance::of('baz')]
     *      ]
     * ]);
     * ```
     *
     * @see set() to know more about possible values of definitions
     * @since 2.0.11
     */
    public function setDefinitions(array $definitions)
    {
        foreach ($definitions as $class => $definition) {
            if (count($definition) === 2 && array_values($definition) === $definition) {
                $this->set($class, $definition[0], $definition[1]);
                continue;
            }

            $this->set($class, $definition);
        }
    }

    /** 把类的定义信息注册到单例数组里，批量注册
     * Registers class definitions as singletons within this container by calling [[setSingleton()]]
     *
     * @param array $singletons array of singleton definitions. See [[setDefinitions()]]
     * for allowed formats of array.
     *
     * @see setDefinitions() for allowed formats of $singletons parameter
     * @see setSingleton() to know more about possible values of definitions
     * @since 2.0.11
     */
    public function setSingletons(array $singletons)
    {
        foreach ($singletons as $class => $definition) {
            if (count($definition) === 2 && array_values($definition) === $definition) {
                $this->setSingleton($class, $definition[0], $definition[1]);
                continue;
            }

            $this->setSingleton($class, $definition);
        }
    }
}
