<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

/**
 * Session提供了会话数据管理及相关的配置
 * Session provides session data management and the related configurations.
 *Session是一个Web应用的组件，它可以通过`Yii::$app->session`来访问
 * Session is a Web application component that can be accessed via `Yii::$app->session`.
 *开启会话，就调用open()方法即可;结束和发送会话数据就调用close();销毁会话数据，就用destroy()方法
 * To start the session, call [[open()]]; To complete and send out session data, call [[close()]];
 * To destroy the session, call [[destroy()]].
 *
 *Session的使用就像一个数组一样（因为它实现了ArrayAccess接口的四个方法），例如：
 * Session can be used like an array to set and get session data. For example,
 *
 * ```php
 * $session = new Session;
 * $session->open();
 * 实现了ArrayAccess接口的四个方法，才可以这样访问
 * $value1 = $session['name1'];  // get session variable 'name1'
 * $value2 = $session['name2'];  // get session variable 'name2'
 * //遍历session遍历
 * foreach ($session as $name => $value) // traverse all session variables
 * $session['name3'] = $value3;  // set session variable 'name3'
 * ```
 *Session可以扩展支持其他的存储引擎
 * Session can be extended to support customized session storage.
 * 想扩展的话，就覆盖useCustomStorage方法，其中就是要覆盖那六个方法
 * To do so, override [[useCustomStorage]] so that it returns true, and
 * override these methods with the actual logic about using custom storage:
 * [[openSession()]], [[closeSession()]], [[readSession()]], [[writeSession()]],
 * [[destroySession()]] and [[gcSession()]].
 *
 *Session也支持一种特定的会话数据，叫做flash messages 闪存信息
 * Session also supports a special type of session data, called *flash messages*.
 * 所谓闪存信息，是仅仅在当前和下一次请求中有效，这之后这个闪存信息就在session文件中删除了
 * A flash message is available only in the current request and the next request.
 * 闪存信息在展示确认（成功）等逻辑的信息时是非常有用的。
 * After that, it will be deleted automatically. Flash messages are particularly
 * 如何使用闪存信息？两个方法：设置闪存，就用setFlash(),获取闪存就用getFlash()即可。
 * useful for displaying confirmation messages. To use flash messages, simply
 * call methods such as [[setFlash()]], [[getFlash()]].
 *
 * For more details and usage information on Session, see the [guide article on sessions](guide:runtime-sessions-cookies).
 *
 *$allFlashes 是所有闪存信息  只读
 * @property array $allFlashes Flash messages (key => message or key => [message1, message2]). This property
 * is read-only.
 * $cookieParams 是会话cookie的参数  只读
 * @property array $cookieParams The session cookie parameters. This property is read-only.
 * $count   session变量的数量，只读
 * @property int $count The number of session variables. This property is read-only.
 * $flash 指明闪存信息的key。注意，闪存信息和正常的session变量共享一样的命名空间（同一个session文件中），所以有可能正常的会话变量会
 * @property string $flash The key identifying the flash message. Note that flash messages and normal session
 * 覆盖掉闪存信息的key,$flash是只写的
 * variables share the same name space. If you have a normal session variable using the same name, its value will
 * be overwritten by this method. This property is write-only.
 * $gCProbability 垃圾收集进程触发的几率（百分比）在每次session初始化时。默认是1，也就是1%的几率触发。
 * @property float $gCProbability The probability (percentage) that the GC (garbage collection) process is
 * started on every session initialization, defaults to 1 meaning 1% chance.
 * $hasSessionId  是否当前请求发送着会话ID名，默认就是PHPSESSID，熟悉吧？
 * @property bool $hasSessionId Whether the current request has sent the session ID.
 * $id，当前的会话ID
 * @property string $id The current session ID.
 * $isActive  是否当前已经开启会话，是否是session_start()。只读。
 * @property bool $isActive Whether the session has started. This property is read-only.
 * $iterator  会话变量的迭代器
 * @property SessionIterator $iterator An iterator for traversing the session variables. This property is
 * read-only.
 * $name  当前session的名称
 * @property string $name The current session name.
 * $savePath   session文件的文件路径
 * @property string $savePath The current session save path, defaults to '/tmp'.
 * $timeout   超时时间，已超时的会话变量将视为垃圾被清除，默认是1440，来自php.ini
 * @property int $timeout The number of seconds after which data will be seen as 'garbage' and cleaned up. The
 * default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
 * $useCookies  该布尔值表名是否使用cookie来存储Session ID
 * @property bool|null $useCookies The value indicating whether cookies should be used to store session IDs.
 * $useCustomStorage  是否使用自定义session 存储引擎   只读
 * @property bool $useCustomStorage Whether to use custom storage. This property is read-only.
 * $useTransparentSessionID   布尔值，是否使用透明Session ID，默认false
 * @property bool $useTransparentSessionID Whether transparent sid support is enabled or not, defaults to
 * false.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Session extends Component implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * 在session文件中存储闪存信息的变量名，默认是__flash
     * @var string the name of the session variable that stores the flash message data.
     */
    public $flashParam = '__flash';
    /**
     * 实现指定接口的Session存储引擎，或者配置数组。如果需要自定义就写上
     * @var \SessionHandlerInterface|array an object implementing the SessionHandlerInterface or a configuration array. If set, will be used to provide persistency instead of build-in methods.
     */
    public $handler;

    /**
     * @var array parameter-value pairs to override default session cookie parameters that are used for session_set_cookie_params() function
     * Array may have the following possible keys: 'lifetime', 'path', 'domain', 'secure', 'httponly'
     * @see http://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    private $_cookieParams = ['httponly' => true];


    /**
     * Initializes the application component.
     * This method is required by IApplicationComponent and is invoked by application.
     */
    public function init()
    {
        parent::init();
        //一开始，就注册了shutdown函数
        //可以多次调用 register_shutdown_function() ，这些被注册的回调会按照他们注册时的顺序被依次调用
        //我得注意一下，是session组件先调用shutdown函数，还是log组件先调用shutdown函数的，留给下次吧。
        register_shutdown_function([$this, 'close']);
        //判断Session是否已经开启
        if ($this->getIsActive()) {
            Yii::warning('Session is already started', __METHOD__);
            //有关闪存信息的处理
            $this->updateFlashCounters();
        }
    }

    /**
     * 返回布尔值，表名是否使用自定义的Session存储引擎
     * Returns a value indicating whether to use custom session storage.
     * 如果子类需要自定义存储引擎，那么应该覆盖这个方法，返回true。并实现自定义的存储引擎那六个方法
     * This method should be overridden to return true by child classes that implement custom session storage.
     * 如何实现自定义存储引擎？请覆盖：openSession，xxxx这六个方法
     * To implement custom session storage, override these methods: [[openSession()]], [[closeSession()]],
     * [[readSession()]], [[writeSession()]], [[destroySession()]] and [[gcSession()]].
     * @return bool whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return false;
    }

    /**
     * 开启session,所谓开启session，就是session_start(),一旦开启后，
     * 后续可以通过session_status()来查看当前是否已开启
     * Starts the session.
     */
    public function open()
    {
        if ($this->getIsActive()) {
            return;
        }

        //注册session存储引擎（默认啥都不写，就是php.ini的file)
        $this->registerSessionHandler();
        //会话开始之前，设置会话cookie的几个参数
        $this->setCookieParamsInternal();
        //会话开始就是调用session_start()
        @session_start();

        if ($this->getIsActive()) {
            Yii::info('Session started', __METHOD__);
            $this->updateFlashCounters();
        } else {
            $error = error_get_last();
            $message = isset($error['message']) ? $error['message'] : 'Failed to start session.';
            Yii::error($message, __METHOD__);
        }
    }

    /**
     * 注册会话处理器
     * Registers session handler.
     * handler为空，没有别的，默认就是php.ini里配置的files，否则请自行覆盖handler属性即可，
     * 比如想配置为redis，memcache都行
     * @throws \yii\base\InvalidConfigException
     */
    protected function registerSessionHandler()
    {
        if ($this->handler !== null) {
            //不看不知道，原来session在PHP 5.4时还有新特性了
            /*
             * PHP 5.4 开始，可以使用直接传递一个实现了SessionHandlerInterface接口的对象的方式，来注册自定义会话存储函数
             * 那六个函数，就是这个对象的六个方法
             */
            if (!is_object($this->handler)) {
                $this->handler = Yii::createObject($this->handler);
            }
            if (!$this->handler instanceof \SessionHandlerInterface) {
                throw new InvalidConfigException('"' . get_class($this) . '::handler" must implement the SessionHandlerInterface.');
            }
            YII_DEBUG ? session_set_save_handler($this->handler, false) : @session_set_save_handler($this->handler, false);
            //或者使用较早的php方法，直接在session_set_save_handler()函数传递六个函数
        } elseif ($this->getUseCustomStorage()) {
            if (YII_DEBUG) {
                session_set_save_handler(
                    [$this, 'openSession'],
                    [$this, 'closeSession'],
                    [$this, 'readSession'],
                    [$this, 'writeSession'],
                    [$this, 'destroySession'],
                    [$this, 'gcSession']
                );
            } else {
                @session_set_save_handler(
                    [$this, 'openSession'],
                    [$this, 'closeSession'],
                    [$this, 'readSession'],
                    [$this, 'writeSession'],
                    [$this, 'destroySession'],
                    [$this, 'gcSession']
                );
            }
        }
    }

    /**
     * Ends the current session and store session data.
     * 主动存储会话数据，然后关闭开启的会话。
     * 正常情况下，我们无需手动关闭会话，当脚本结束时会自动保存会话数据并关闭当前会话。
     * 只有在某些出现并发锁的场景时，才会需要手动关闭session会话。
     */
    public function close()
    {
        if ($this->getIsActive()) {
            YII_DEBUG ? session_write_close() : @session_write_close();
        }
    }

    /**
     * 注意，Yii框架的destroy里 是开启然后再关闭。为什么不直接关闭呢？
     * 这是因为，使用close，可以把$_SESSION中的内容先保存到session文件中并关闭会话进程。
     * 再开启会话句柄后，使用session_unset()可以清空$_SESSION和session文件里的内容
     * 然后，使用session_destroy()再把session文件也删除掉，并关闭会话进程
     * 经过上述的三个步骤，就把所有的会话数据清除了，$_SESSION也空数组了，session文件也没有了。会话进程也关闭了。
     * 再开启，就得session_start()了。
     * Frees all session variables and destroys all data registered to a session.
     */
    public function destroy()
    {
        if ($this->getIsActive()) {
            //获取当前会话ID
            $sessionId = session_id();
            //保存所有Session数据到session文件中并关闭Session文件句柄
            $this->close();
            //重新开启会话之前，设置会话ID,这样打开的会话ID,和会话关闭前一样。
            $this->setId($sessionId);
            $this->open();
            //清除（释放）当前会话的所有会话变量,注意$_SESSION和session文件的内容都会清空，但是session文件仍然保留（空文件）
            session_unset();
            //删除session文件，但是不删除$_SESSION里的内容
            session_destroy();
            $this->setId($sessionId);
        }
    }

    /**查看当前的session状态
     * 有三个状态：
     * PHP_SESSION_ACTIVE   会话是启用的，而且存在当前会话（session_start()之后是这个状态）。
     * PHP_SESSION_NONE     会话是启用的，但不存在当前会话（没有session_start(),或者session关闭了又）
     * 还有一个是PHP_SESSION_DISABLED，暂不清楚
     * @return bool whether the session has started
     */
    public function getIsActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    private $_hasSessionId;

    /**返回值，表明当前请求是否发送来了sessionID
     * Returns a value indicating whether the current request has sent the session ID.
     * 默认通过session名从cookie和$_GET中检查
     * The default implementation will check cookie and $_GET using the session name.
     * 如果通过其他方式的话，请覆盖该方法自行实现
     * If you send session ID via other ways, you may need to override this method
     * 或者，通过调用setHasSessionId来明确是否有Session ID发送过来
     * or call [[setHasSessionId()]] to explicitly set whether the session ID is sent.
     * @return bool whether the current request has sent the session ID.
     */
    public function getHasSessionId()
    {
        if ($this->_hasSessionId === null) {
            //获取会话session的cookie名字，一般是PHPSESSID
            $name = $this->getName();
            $request = Yii::$app->getRequest();
            if (!empty($_COOKIE[$name]) && ini_get('session.use_cookies')) {
                $this->_hasSessionId = true;
            } elseif (!ini_get('session.use_only_cookies') && ini_get('session.use_trans_sid')) {
                $this->_hasSessionId = $request->get($name) != '';
            } else {
                $this->_hasSessionId = false;
            }
        }

        return $this->_hasSessionId;
    }

    /**
     * Sets the value indicating whether the current request has sent the session ID.
     * This method is provided so that you can override the default way of determining
     * whether the session ID is sent.
     * @param bool $value whether the current request has sent the session ID.
     */
    public function setHasSessionId($value)
    {
        $this->_hasSessionId = $value;
    }

    /**
     * 取得会话ID,什么是会话id,就是session文件的文件名，知道了吧？
     * Gets the session ID.
     * This is a wrapper for [PHP session_id()](http://php.net/manual/en/function.session-id.php).
     * @return string the current session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * 设置会话ID，在开启新会话之前有效
     * Sets the session ID.
     * This is a wrapper for [PHP session_id()](http://php.net/manual/en/function.session-id.php).
     * @param string $value the session ID for the current session
     */
    public function setId($value)
    {
        session_id($value);
    }

    /**
     * 用新的SessionID，替换当前的SessionID，在处理Web应用的时候，理解这个PHP函数很重要
     * Updates the current session ID with a newly generated one .
     * Please refer to <http://php.net/session_regenerate_id> for more details.
     * 当参数为true时，会删除旧的session文件
     * @param bool $deleteOldSession Whether to delete the old associated session file or not.
     * 注意，在session会话为开启状态时才有必要删除旧的session文件，才能调用session_regenerate_id()，根本没有session_start的话，
     * 自然就不必更新服务端的session文件里
     * 这个函数仅仅更新SessionID(服务端session文件名）(客户端的cookie在请求结束后也随着更新）,
     * 不会更新session文件里的内容，$_SESSION不受影响.。
     */
    public function regenerateID($deleteOldSession = false)
    {
        if ($this->getIsActive()) {
            // add @ to inhibit possible warning due to race condition
            // https://github.com/yiisoft/yii2/pull/1812
            if (YII_DEBUG && !headers_sent()) {
                session_regenerate_id($deleteOldSession);
            } else {
                @session_regenerate_id($deleteOldSession);
            }
        }
    }

    /**
     * 获得当前的session 名字，什么是session 名字，就是那个PHPSESSID.熟悉了吧？其实是一个cookie名字而已，
     * 只不过这个cookie的值是服务端session文件的名字，也就是所谓的session ID.
     * session名字存储session ID
     * Gets the name of the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @return string the current session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * 设置session名字，默认就是PHPSESSID
     * Sets the name for the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @param string $value the session name for the current session, must be an alphanumeric string.
     * It defaults to "PHPSESSID".
     */
    public function setName($value)
    {
        session_name($value);
    }

    /**
     * 在使用默认的Files存储会话数据时，这将返回文件系统的路径；
     * 如果不使用默认的Files，那么返回的值会根据会话存储引擎而有所不同，比如memcache会返回套接字。
     * Gets the current session save path.
     * This is a wrapper for [PHP session_save_path()](http://php.net/manual/en/function.session-save-path.php).
     * @return string the current session save path, defaults to '/tmp'.
     */
    public function getSavePath()
    {
        return session_save_path();
    }

    /**
     * 设置会话数据文件的文件系统保存路径
     * Sets the current session save path.
     * This is a wrapper for [PHP session_save_path()](http://php.net/manual/en/function.session-save-path.php).
     * @param string $value the current session save path. This can be either a directory name or a path alias.
     * @throws InvalidParamException if the path is not a valid directory
     */
    public function setSavePath($value)
    {
        $path = Yii::getAlias($value);
        if (is_dir($path)) {
            session_save_path($path);
        } else {
            throw new InvalidParamException("Session save path is not a valid directory: $value");
        }
    }

    /**
     * 返回会话cookie参数，会话cookie,就是cookie值是SESSION ID的cookie。
     * 哪些参数，手册上说有5个参数。['lifetime','path','domain','secure','httponly']
     * @return array the session cookie parameters.
     * @see http://php.net/manual/en/function.session-get-cookie-params.php
     */
    public function getCookieParams()
    {
        return array_merge(session_get_cookie_params(), array_change_key_case($this->_cookieParams));
    }

    /**
     * 手动为会话cookie配置字段，默认就一个httponly=>true
     * Sets the session cookie parameters.
     * The cookie parameters passed to this method will be merged with the result
     * of `session_get_cookie_params()`.
     * @param array $value cookie parameters, valid keys include: `lifetime`, `path`, `domain`, `secure` and `httponly`.
     * @throws InvalidParamException if the parameters are incomplete.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    public function setCookieParams(array $value)
    {
        $this->_cookieParams = $value;
    }

    /**
     * 启动session之前设置会话cookie某些字段，会话cookie即session cookie。是特殊的一个cookie。
     * Sets the session cookie parameters.
     * 在会话开启之前调用
     * This method is called by [[open()]] when it is about to open the session.
     * @throws InvalidParamException if the parameters are incomplete.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    private function setCookieParamsInternal()
    {
        $data = $this->getCookieParams();
        if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
            session_set_cookie_params($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly']);
        } else {
            throw new InvalidParamException('Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.');
        }
    }

    /**
     * 返回布尔值，指明是否使用cookie存储会话ID
     * Returns the value indicating whether cookies should be used to store session IDs.
     * @return bool|null the value indicating whether cookies should be used to store session IDs.
     * @see setUseCookies()
     */
    public function getUseCookies()
    {
        if (ini_get('session.use_cookies') === '0') {
            return false;
        } elseif (ini_get('session.use_only_cookies') === '1') {
            return true;
        } else {
            return null;
        }
    }

    /**
     * Sets the value indicating whether cookies should be used to store session IDs.
     * Three states are possible:
     *  使用cookie存储会话ID，并且只用这一种方式存储会话ID
     * - true: cookies and only cookies will be used to store session IDs.
     * false,不使用cookie来存储会话ID
     * - false: cookies will not be used to store session IDs.
     * null,尽可能使用cookie存储会话ID,若不行，则使用其他机制存储会话ID,比如透明会话ID方式
     * - null: if possible, cookies will be used to store session IDs; if not, other mechanisms will be used (e.g. GET parameter)
     *我们看到，它是在php程序中，使用ini_set函数来设置php.ini中的原始选项。
     * @param bool|null $value the value indicating whether cookies should be used to store session IDs.
     */
    public function setUseCookies($value)
    {
        if ($value === false) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        } elseif ($value === true) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
        } else {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '0');
        }
    }

    /**
     * 获得每次初始化会话数据时，启动垃圾回收机制的概率
     * @return float the probability (percentage) that the GC (garbage collection) process is started on every session initialization, 
     * defaults to 1 meaning 1% chance.
     */
    public function getGCProbability()
    {
        return (float) (ini_get('session.gc_probability') / ini_get('session.gc_divisor') * 100);
    }

    /**
     * 设置垃圾回收机制的概率
     * @param float $value the probability (percentage) that the GC (garbage collection) process is started on every session initialization.
     * @throws InvalidParamException if the value is not between 0 and 100.
     */
    public function setGCProbability($value)
    {
        if ($value >= 0 && $value <= 100) {
            // percent * 21474837 / 2147483647 ≈ percent * 0.01
            ini_set('session.gc_probability', floor($value * 21474836.47));
            ini_set('session.gc_divisor', 2147483647);
        } else {
            throw new InvalidParamException('GCProbability must be a value between 0 and 100.');
        }
    }

    /**
     * 是否可以使用透明会话ID传送方式
     * @return bool whether transparent sid support is enabled or not, defaults to false.
     */
    public function getUseTransparentSessionID()
    {
        return ini_get('session.use_trans_sid') == 1;
    }

    /**
     * 设置是否使用透明会话ID的传送方式
     * @param bool $value whether transparent sid support is enabled or not.
     */
    public function setUseTransparentSessionID($value)
    {
        ini_set('session.use_trans_sid', $value ? '1' : '0');
    }

    /**
     * 垃圾回收机制，从会话开启之后，多长时间没有会话动作，则把会话数据当做垃圾删除。
     * 默认就是1440秒。发呆时间。
     * @return int the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * The default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
     */
    public function getTimeout()
    {
        return (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * 设置发呆超时时间。
     * @param int $value the number of seconds after which data will be seen as 'garbage' and cleaned up
     */
    public function setTimeout($value)
    {
        ini_set('session.gc_maxlifetime', $value);
    }

    /**
     * 开启会话处理器
     * Session open handler.
     * 当使用自定义存储引擎时，应该使用这个方法开启自己的会话。
     * 因为不同的存储引擎，开启会话的方式是不同的。默认的Files就使用open方式就可以了。
     * 不要直接调用这个方法。
     * This method should be overridden if [[useCustomStorage]] returns true.
     * Do not call this method directly.
     * @param string $savePath session save path
     * @param string $sessionName session name
     * @return bool whether session is opened successfully
     */
    public function openSession($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Session close handler.
     * 自定义存储引擎时，关闭会话进程的方法。由子类来实现。
     * 该方法不能直接调用，是由自定义会话存储引擎来调用的。也就是关闭session会话，或者脚本结束时调用。
     * This method should be overridden if [[useCustomStorage]] returns true.
     * Do not call this method directly.
     * @return bool whether session is closed successfully
     */
    public function closeSession()
    {
        return true;
    }

    /**
     * Session read handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * 该方法不能直接调用，是由自定义会话存储引擎来调用的。
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        return '';
    }

    /**
     * Session write handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * 该方法不能直接调用，是由自定义会话存储引擎来调用的。
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return bool whether session write is successful
     */
    public function writeSession($id, $data)
    {
        return true;
    }

    /**
     * Session destroy handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * 该方法不能直接调用，是由自定义会话存储引擎来调用的。
     * Do not call this method directly.
     * @param string $id session ID
     * @return bool whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        return true;
    }

    /**
     * Session GC (garbage collection) handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * 该方法不能直接调用，是由自定义会话存储引擎来调用的。
     * Do not call this method directly.
     * @param int $maxLifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return bool whether session is GCed successfully
     */
    public function gcSession($maxLifetime)
    {
        return true;
    }

    /**
     * Returns an iterator for traversing the session variables.
     * This method is required by the interface [[\IteratorAggregate]].
     * @return SessionIterator an iterator for traversing the session variables.
     */
    public function getIterator()
    {
        $this->open();
        return new SessionIterator;
    }

    /**
     * 返回当前会话数据的数量。
     * 我们知道，当开启会话后，原来session文件里的内容，将会存储到$_SESSION中，
     * 这样我们就可以在程序中操作会话数据了。
     * Returns the number of items in the session.
     * @return int the number of session variables
     */
    public function getCount()
    {
        $this->open();
        return count($_SESSION);
    }

    /**
     * 还是封装而已。
     * 不,这是实现PHP的Countable接口必须要实现的方法
     * 孤陋寡闻了吧？
     * Returns the number of items in the session.
     * This method is required by [[\Countable]] interface.
     * @return int number of items in the session.
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * 根据session变量名获取session变量的值
     * Returns the session variable value with the session variable name.
     * 当session变量不存在时，返回第二个参数$defaultValue
     * If the session variable does not exist, the `$defaultValue` will be returned.
     * @param string $key the session variable name
     * @param mixed $defaultValue the default value to be returned when the session variable does not exist.
     * @return mixed the session variable value, or $defaultValue if the session variable does not exist.
     * 注意理解这个概念，$_SESSION中的数据是怎么来的？是从session文件中来的，在开启会话之初由PHP自动填充到$_SESSION中，
     * 在开启会话之前，往$_SESSION中虽然可以设置数据，但是一旦开启会话，就会全部清空之前往$_SESSION中设置过的数据。
     * 使得$_SESSION中只保存session文件里的数据。
     * 总结：session文件是静态的，存储在文件系统里的；
     * 而$_SESSION是从session文件里读出来的内容副本，保存到内存中，在程序运行过程中，可以随意增删，一般都不会影响
     * 文件系统里的那个session文件，直到脚本结束或者session_write_close()把$_SESSION中的数据同步到文件系统的session里，来进行
     * 持久化保存。
     */
    public function get($key, $defaultValue = null)
    {
        $this->open();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
    }

    /**
     * 设置session变量，会覆盖旧的同名变量
     * Adds a session variable. 
     * If the specified name already exists, the old value will be overwritten.
     * @param string $key session variable name
     * @param mixed $value session variable value
     */
    public function set($key, $value)
    {
        $this->open();
        $_SESSION[$key] = $value;
    }

    /**
     * 用unset($_SESSION['xxx'])方式删除某个session变量，并返回这个变量值。
     * 没有就不删除，且返回null
     * Removes a session variable.
     * @param string $key the name of the session variable to be removed
     * @return mixed the removed value, null if no such session variable.
     */
    public function remove($key)
    {
        $this->open();
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);

            return $value;
        } else {
            return null;
        }
    }

    /**删除所有的session变量，注意是遍历一个个会话变量，然后使用unset($_SESSION['xxx']),
     * 而不是unset($_SESSION)
     * Removes all session variables
     * 这只是把内存中的会话数据（$_SESSION）清空了，文件系统的session文件尚完好无损呢。
     */
    public function removeAll()
    {
        $this->open();
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * 是否内存中有某个会话变量
     * @param mixed $key session variable name
     * @return bool whether there is the named session variable
     */
    public function has($key)
    {
        $this->open();
        return isset($_SESSION[$key]);
    }

    /**更新闪存信息的使用量，删除过时的闪存信息
     * Updates the counters for flash messages and removes outdated flash messages.
     * 这个方法不能手动调用，应该在init方法里自动调用，且被调用一次
     * This method should only be called once in [[init()]].
     */
    protected function updateFlashCounters()
    {
        $counters = $this->get($this->flashParam, []);
        if (is_array($counters)) {
            foreach ($counters as $key => $count) {
                //大于0的都删除掉（说明已经访问过了）
                if ($count > 0) {
                    unset($counters[$key], $_SESSION[$key]);
                //等于0的，是说上一个请求设置的闪存信息，无论下次请求是否访问，都必须删除。
                } elseif ($count == 0) {
                    $counters[$key]++;
                }
            }
            //继续存储在session中，留给下次init的判断。
            $_SESSION[$this->flashParam] = $counters;
        } else {
            // fix the unexpected problem that flashParam doesn't return an array
            unset($_SESSION[$this->flashParam]);
        }
    }

    /**
     * 返回闪存会话信息
     * Returns a flash message.
     * @param string $key the key identifying the flash message
     * @param mixed $defaultValue value to be returned if the flash message does not exist.
     * @param bool $delete whether to delete this flash message right after this method is called.
     * If false, the flash message will be automatically deleted in the next request.
     * @return mixed the flash message or an array of messages if addFlash was used
     * @see setFlash()
     * @see addFlash()
     * @see hasFlash()
     * @see getAllFlashes()
     * @see removeFlash()
     */
    public function getFlash($key, $defaultValue = null, $delete = false)
    {
        $counters = $this->get($this->flashParam, []);
        if (isset($counters[$key])) {
            $value = $this->get($key, $defaultValue);
            if ($delete) {
                $this->removeFlash($key);
                //在设置闪存信息时，标识访问过后再删除，否则一直存在session中，无论后续多少请求。
            } elseif ($counters[$key] < 0) {
                // mark for deletion in the next request
                $counters[$key] = 1;
                $_SESSION[$this->flashParam] = $counters;
            }

            return $value;
        } else {
            return $defaultValue;
        }
    }

    /**
     * 返回所有的闪存信息
     * Returns all flash messages.
     *
     *可以在视图文件里使用
     * You may use this method to display all the flash messages in a view file:
     *
     * ```php
     * <?php
     * foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
     *     echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
     * } ?>
     * ```
     *
     * With the above code you can use the [bootstrap alert][] classes such as `success`, `info`, `danger`
     * as the flash message key to influence the color of the div.
     *如果使用addFlash()方法存储闪存信息时，$message必须是数组，必须注意这个数据类型的不同。
     * Note that if you use [[addFlash()]], `$message` will be an array, and you will have to adjust the above code.
     *
     * [bootstrap alert]: http://getbootstrap.com/components/#alerts
     *
     * @param bool $delete whether to delete the flash messages right after this method is called.
     * If false, the flash messages will be automatically deleted in the next request.
     * @return array flash messages (key => message or key => [message1, message2]).
     * @see setFlash()  第一次设置闪存信息，默认counter是-1
     * @see addFlash()
     * @see getFlash() 第二次请求中读取闪存信息，标记counter置为1。第三次请求中，在updateFlashCounter()方法里完成删除。
     * @see hasFlash()
     * @see removeFlash()
     */
    public function getAllFlashes($delete = false)
    {
        //从__flash里读取出会话数据的变量名（下标），而且这个下标的值是一个标识，标识当前下标是否有效
        //（大于1则无效）
        //虽然他们都存储在一个session文件里。
        $counters = $this->get($this->flashParam, []);
        $flashes = [];
        foreach (array_keys($counters) as $key) {
            if (array_key_exists($key, $_SESSION)) {
                $flashes[$key] = $_SESSION[$key];
                if ($delete) {
                    //删除闪存的下标，也删除闪存的信息
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($counters[$key] < 0) {
                    // mark for deletion in the next request
                    //标记之，下次就可以删除。何为下次删除？
                    //就是下次的http请求中，初始化session组件时，会调用init方法里，
                    //在init方法里，会调用updateFlashCounters()来处理。
                    $counters[$key] = 1;
                }
            } else {
                //闪存信息的下标，在会话数据都不存在了，那就在闪存下标里删除呗
                unset($counters[$key]);
            }
        }
        //标记好后，重新放回session，下次init时就会被删除。
        $_SESSION[$this->flashParam] = $counters;
        //返回所有的闪存信息
        return $flashes;
    }

    /**
     * 设置闪存信息
     * Sets a flash message.
     * 闪存信息会在下次请求处理中，被访问过后再清理。
     * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen
     * in the next request.
     * 新的闪存变量会覆盖旧的同名的闪存变量。
     * If there is already an existing flash message with the same key, it will be overwritten by the new one.
     * 
     * 注意，闪存信息的下标，其实也是会话数据，之所以叫他闪存，是为了方便下次使用过删除之，它是一个临时变量。
     * @param string $key the key identifying the flash message. Note that flash messages
     * 即使是临时变量，它也存在的session中，也会覆盖同名的会话数据。明白了吧？
     * and normal session variables share the same name space. If you have a normal
     * session variable using the same name, its value will be overwritten by this method.
     * @param mixed $value flash message
     * $removeAfterAccess  这个参数可以控制，当访问之后是否删除。
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * 如果是false，则在下次请求中一定会删除，无论它是否被访问过；
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * 如果是true（默认），这个信息将会保留好多次请求，直到它被后续的某个请求访问过了再删除。
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     * @see getFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function setFlash($key, $value = true, $removeAfterAccess = true)
    {
        $counters = $this->get($this->flashParam, []);
        //如果设置为0的话，无论第二次请求是否访问这个闪存信息，都会在第三次请求中删除
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$key] = $value;
        $_SESSION[$this->flashParam] = $counters;
    }

    /**
     * 添加一个闪存信息，给指定下标。这样闪存信息，就变成数组了。
     * Adds a flash message.
     * If there are existing flash messages with the same key, the new one will be appended to the existing message array.
     * @param string $key the key identifying the flash message.
     * @param mixed $value flash message
     * 第三个参数的逻辑，同setFlash()方法。
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     * @see getFlash()
     * @see setFlash()
     * @see removeFlash()
     */
    public function addFlash($key, $value = true, $removeAfterAccess = true)
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$this->flashParam] = $counters;
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = [$value];
        } else {
            if (is_array($_SESSION[$key])) {
                $_SESSION[$key][] = $value;
            } else {
                $_SESSION[$key] = [$_SESSION[$key], $value];
            }
        }
    }

    /**
     * 删除指定下标的闪存信息
     * 并返回这个信息
     * Removes a flash message.
     * 注意，有危险，可能删除同名的，但不是闪存信息的数据，因为他们都是会话数据嘛。
     * @param string $key the key identifying the flash message. Note that flash messages
     * and normal session variables share the same name space.  If you have a normal
     * session variable using the same name, it will be removed by this method.
     * @return mixed the removed flash message. Null if the flash message does not exist.
     * @see getFlash()
     * @see setFlash()
     * @see addFlash()
     * @see removeAllFlashes()
     */
    public function removeFlash($key)
    {
        $counters = $this->get($this->flashParam, []);
        $value = isset($_SESSION[$key], $counters[$key]) ? $_SESSION[$key] : null;
        unset($counters[$key], $_SESSION[$key]);
        //其他闪存信息继续放回去
        $_SESSION[$this->flashParam] = $counters;

        return $value;
    }

    /**
     * 清除所有的闪存信息，连闪存下标也干掉。
     * Removes all flash messages.
     * Note that flash messages and normal session variables share the same name space.
     * If you have a normal session variable using the same name, it will be removed
     * by this method.
     * @see getFlash()
     * @see setFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function removeAllFlashes()
    {
        $counters = $this->get($this->flashParam, []);
        foreach (array_keys($counters) as $key) {
            unset($_SESSION[$key]);
        }
        unset($_SESSION[$this->flashParam]);
    }

    /**
     * 是否有某个闪存下标的信息存在
     * Returns a value indicating whether there are flash messages associated with the specified key.
     * @param string $key key identifying the flash message type
     * @return bool whether any flash messages exist under specified key
     */
    public function hasFlash($key)
    {
        return $this->getFlash($key) !== null;
    }

    /**
     * 数组访问接口ArrayAccess的实现，必须实现的几个方法之一。
     * 四个方法，exists,get,set,unset
     * 使得yii\web\Session组件的属性，也可以同步到$_SESSION全局数组里。
     * This method is required by the interface [[\ArrayAccess]].
     * @param mixed $offset the offset to check on
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->open();

        return isset($_SESSION[$offset]);
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param int $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        $this->open();

        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param int $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->open();
        $_SESSION[$offset] = $item;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        $this->open();
        unset($_SESSION[$offset]);
    }
}
