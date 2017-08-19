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
use yii\base\InvalidValueException;
use yii\rbac\CheckAccessInterface;

/**User类是应用组件之一，用来管理用户认证的状态（登录？退出？）
 * User is the class for the `user` application component that manages the user authentication status.
 *可以使用isGuest来判断当前用户是否是登录状态或者是登出状态（访客状态）
 * You may use [[isGuest]] to determine whether the current user is a guest or not.
 * 如果当前用户是访客，identity属性返回null，否则返回identity实例
 * If the user is a guest, the [[identity]] property would return `null`. Otherwise, it would
 * be an instance of [[IdentityInterface]].
 *
 * 可以通过下述的几个方法来改变用户的认证状态
 * You may call various methods to change the user authentication status:
 * login方法，设置指定的认证实例identity并存储认证状态在session和cookie中
 * - [[login()]]: sets the specified identity and remembers the authentication status in session and cookie;
 * logout方法，标记一个用户为访客（退出登录状态）并清除相关的session和cookie信息
 * - [[logout()]]: marks the user as a guest and clears the relevant information from session and cookie;
 * setIdentity方法，修改认证用户实例而无需触及session和cookie（在无状态的RESTful API方式中，setIdentity是最好用的认证方式）
 * - [[setIdentity()]]: changes the user identity without touching session or cookie
 *   (this is best used in stateless RESTful API implementation).
 *注意，User组件仅仅维护用户的认证状态，并不管如何认证用户
 * Note that User only maintains the user authentication status. It does NOT handle how to authenticate
 * 如何认证一个用户的逻辑，应该在认证类中实现，故identityClass是必须要有的
 * a user. The logic of how to authenticate a user should be done in the class implementing [[IdentityInterface]].
 * You are also required to set [[identityClass]] with the name of this class.
 *User默认配置为Web应用的组件（非console应用），我们可以通过Yii::$app->user来访问这个user组件
 * User is configured as an application component in [[\yii\web\Application]] by default.
 * You can access that instance via `Yii::$app->user`.
 *开发人员可以通过添加数组元素来更新User组件的配置信息，比如下面的例子：
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 *
 * ```php
 * 'user' => [
 *     'identityClass' => 'app\models\User', // User must implement the IdentityInterface
 *     'enableAutoLogin' => true,
 *     // 'loginUrl' => ['user/login'],
 *     // ...
 * ]
 * ```
 * $id,用户唯一标识符，如果是null，表名当前用户是访客，该属性只读
 * @property string|int $id The unique identifier for the user. If `null`, it means the user is a guest. This
 * property is read-only.
 * $identity是有关当前登录用户的认证对象，当用户未登录时（未认证）返回null
 * @property IdentityInterface|null $identity The identity object associated with the currently logged-in
 * user. `null` is returned if the user is not logged in (not authenticated).
 * $isGuest,是否当前用户是访客，只读属性
 * @property bool $isGuest Whether the current user is a guest. This property is read-only.
 * $returnUrl 当用户从未认证（未登录）到认证登录后，用户应该跳转到的url
 * @property string $returnUrl The URL that the user should be redirected to after login. Note that the type
 * 这个属性有set和get方法，但绝不是getter和setter（只是名字重复了而已）
 * 看看setReturnUrl()和getReturnUrl()方法可以了解具体情况
 * of this property differs in getter and setter. See [[getReturnUrl()]] and [[setReturnUrl()]] for details.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class User extends Component
{
	//初始化了四个事件
    const EVENT_BEFORE_LOGIN = 'beforeLogin';
    const EVENT_AFTER_LOGIN = 'afterLogin';
    const EVENT_BEFORE_LOGOUT = 'beforeLogout';
    const EVENT_AFTER_LOGOUT = 'afterLogout';

    /** 
	 * 字符串，认证对象（认证实例）的类名（一般在User组件的配置数组里配置，必填项）
     * @var string the class name of the [[identity]] object.
     */
    public $identityClass;
    /**
	* bool 是否开启基于cookie的登录，默认false不开启
     * @var bool whether to enable cookie-based login. Defaults to `false`.
	 * 当enableSession设置为false时，这个属性将被忽略
	 * 这里所谓基于cookie登录，是说使用cookie保存登录信息（认证信息），
     * Note that this property will be ignored if [[enableSession]] is `false`.
     */
    public $enableAutoLogin = false;
    /**
	 * bool 是否使用Session来持久化认证状态
     * @var bool whether to use session to persist authentication status across multiple requests.
	 * 如果你的应用是无状态的，那么可以设置这个属性是false（想想哪些应用是无状态的？思来想去，估计就是RESTful API时了吧？）
	 * 其实没有哪个应用是无状态的，只是实现状态控制的方式不同而已。比如我们平常所说的Session就是一种会话机制
	 * 在服务端保存（文件，redis，memcache都行），还有一种是用于手机端请求的API应用，这种也有会话机制，那就是通过令牌方式，并不是Session机制
	 * 所以，这里所说的无状态应用，应该就是指手机端访问的API应用了。（令牌方式认证）
     * You set this property to be `false` if your application is stateless, which is often the case
     * for RESTful APIs.
     * 所以只要是用电脑来访问的还是得启用Session。
	 * 默认启用session。但session的存储引擎是什么，请看yii\web\session。默认是File存储，保存sessionID的cookie名没有特别配置，就是
	 * 使用php的PHPSESSID，session文件的存储位置也由php.ini来配置的。
     */
    public $enableSession = true;
    /**
	 * 字符串或数组，当loginRequired()调用时，用于给终端用户生成跳转的url
     * @var string|array the URL for login when [[loginRequired()]] is called.
	 * 如果是个数组，使用[[UrlManager::createUrl()]]处理之；
     * If an array is given, [[UrlManager::createUrl()]] will be called to create the corresponding URL.
	 * 数组的第一个元素必须是登录动作的路由，其他的元素则以键值对的形式给出组装登录动作URL的get参数
     * The first element of the array should be the route to the login action, and the rest of
     * the name-value pairs are GET parameters used to construct the login URL. For example,
     *
     * ```php
     * ['site/login', 'ref' => 1]
     * ```
     * 如果这个属性是null,那么将返回403 http异常
     * If this property is `null`, a 403 HTTP exception will be raised when [[loginRequired()]] is called.
     */
    public $loginUrl = ['site/login'];
    /**
	 * 数组，认证cookie的配置，仅在enableAutoLogin设置为true时才有用
	 * 认证cookie也是一个cookie而已
     * @var array the configuration of the identity cookie. This property is used only when [[enableAutoLogin]] is `true`.
     * @see Cookie
     */
    public $identityCookie = ['name' => '_identity', 'httpOnly' => true];
    /**认证超时的秒数，在用户保持未激活状态持续这些秒数（发呆时间）后，将会自动的登出
     * @var int the number of seconds in which the user will be logged out automatically if he
	 * 如果这个属性没有设置，当session过期时就会登出
     * remains inactive. If this property is not set, the user will be logged out after
     * the current session expires (c.f. [[Session::timeout]]).
	 * 注意，当enableAutologin为true时，这个属性将不起作用
     * Note that this will not work if [[enableAutoLogin]] is `true`.
     * 如何记录用户未激活状态持续的秒数？这个简单，只需在session中保存这个用户上次访问某个页面的时间戳，两次访问的时间差超过
     * authTimeout的值时，就是认证超时了，此时服务端就强制登出这个用户。
     */
    public $authTimeout;
    /**
	 * 访问检测实例（实现CheckAccessInterfase接口）用来检测访问权限的
     * @var CheckAccessInterface The access checker to use for checking access.
	 * 如果没有设置这个属性，则使用auth manager。
     * If not set the application auth manager will be used.
     * @since 2.0.9
     */
    public $accessChecker;
    /**
	 * 绝对登出超时秒数，无论用户的活跃性，只要达到这个秒数用户就一定自动登出
     * @var int the number of seconds in which the user will be logged out automatically
     * regardless of activity.
	 * 注意，当enableAutologin为true时，这个属性将不起作用
     * Note that this will not work if [[enableAutoLogin]] is `true`.
     * 仍然在session中保存用户初次登录的时间戳，每次用户访问页面时都检测当前时间戳和初次登录的时间戳
     * 这两者的时间差大于absoluteAuthTimeout就强制退出当前用户（删除认证信息呗）
     */
    public $absoluteAuthTimeout;
    /**
	 * 布尔类型，当每次请求一个页面时，是否自动刷新认证cookie
     * @var bool whether to automatically renew the identity cookie each time a page is requested.
	 * 当enableAutologin为true时，这个属性才有用。
     * This property is effective only when [[enableAutoLogin]] is `true`.
	 * 当设置为false时，认证cookie将会按照用户登录之初设置的cookie过期时间来决定是否过期。
     * When this is `false`, the identity cookie will expire after the specified duration since the user
	 * 当设置为true时，当用户距离最近的一次请求的持续时间超时后，认证cookie将会过期。（没明白）
     * is initially logged in. When this is `true`, the identity cookie will expire after the specified duration
     * since the user visits the site the last time.
     * @see enableAutoLogin
     */
    public $autoRenewCookie = true;
    /**
	 * 字符串，会话变量名，用来指明会话id
     * @var string the session variable name used to store the value of [[id]].
     */
    public $idParam = '__id';
    /**
	 * 字符串，会话变量名，用来指明认证状态的过期时间戳
     * @var string the session variable name used to store the value of expiration timestamp of the authenticated state.
	 * 当authTimeout属性设置时有用
     * This is used when [[authTimeout]] is set.
     */
    public $authTimeoutParam = '__expire';
    /**
	 * 字符串，会话变量名，用来指明认证状态绝对超时时间戳
     * @var string the session variable name used to store the value of absolute expiration timestamp of the authenticated state.
	 * 当absoluteAuthTimeout属性设置时才有用
     * This is used when [[absoluteAuthTimeout]] is set.
     */
    public $absoluteAuthTimeoutParam = '__absoluteExpire';
    /**
	 * 字符串，会话变量名，用来指明returnUrl的值
     * @var string the session variable name used to store the value of [[returnUrl]].
     */
    public $returnUrlParam = '__returnUrl';
    /**
	 * 数组，在跳转到登录的url时，应该给出的MIME
     * @var array MIME types for which this component should redirect to the [[loginUrl]].
     * @since 2.0.8
     */
    public $acceptableRedirectTypes = ['text/html', 'application/xhtml+xml'];

	/**
	* 私有，暂不知
	*/
    private $_access = [];


    /**
	 * 初始化应用组件（User组件），所谓初始化，就是设置User组件类的属性。
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();
		//没有identityClass属性就抛异常，可见这个属性是必须的哟，必须指明认证类
        if ($this->identityClass === null) {
            throw new InvalidConfigException('User::identityClass must be set.');
        }
        if ($this->enableAutoLogin && !isset($this->identityCookie['name'])) {
            throw new InvalidConfigException('User::identityCookie must contain the "name" element.');
        }
    }
    //默认是false,不是null，这个设置非常重要，使得getIdentity方法里发挥作用
    private $_identity = false;

    /**
     * 返回关联当前登录用户的认证对象(能取回说明是登录状态，返回null则是访客，即非登录状态）
     * Returns the identity object associated with the currently logged-in user.
     * 如果最初认证对象为空时，那么当enableSession是true的话，该方法会尝试从session中读取用户的认证信息并重组相应的认证对象
     * When [[enableSession]] is true, this method may attempt to read the user's authentication data
     * stored in session and reconstruct the corresponding identity object, if it has not done so before.
     * 参数$autoRenew默认是true,也就是说，当尚无认证对象时，会自动取回认证对象，从哪里取回？从session,或者cookie
     * 如果session或cookie中也没有，则可以肯定当前用户是访客，尚未登录。
     * @param bool $autoRenew whether to automatically renew authentication status if it has not been done so before.
     * This is only useful when [[enableSession]] is true.
     * @return IdentityInterface|null the identity object associated with the currently logged-in user.
     * `null` is returned if the user is not logged in (not authenticated).如果用户尚未登录，则返回null。同一个请求里也绝不会再次尝试从session或cookie中获取认证信息了
     * @see login()
     * @see logout()
     */
    public function getIdentity($autoRenew = true)
    {
        /**
         * 一般情况下，由Yii::$app->user->identity或者Yii::$app->user->isGuest这两行开始触发，这两行代码都是需要判断是否是登录状态。
        * 从需求出发，如果你访问的页面不需要用户登录信息，那永远也不会触发是否登录的逻辑。但是，偏偏我们的项目大多数会，
        * 在页面头部给出当前登录者姓名的信息，我们知道只有用户登录了，才能知道当前用户是谁。所以，在这个basic项目里，初次打开浏览器都会
        * 遇到Yii::$app->user->isGuest这个代码逻辑。也就从getIsGuest()--->getIdentity()开始了获取认证信息（identity)的代码逻辑
        */
        //注意，nulll和false在全等符（===）比较时是不等的
        if ($this->_identity === false) {
            if ($this->enableSession && $autoRenew) {
                //注意，这里把_identity设置为null非常巧妙：这样再次遇到这方法时，会因为null === false 不等，而不会再去renewAuthStatus()了。
                $this->_identity = null;
                $this->renewAuthStatus();
            } else {
                return null;
            }
        }

        return $this->_identity;
    }

    /**
     * 设置认证实例
     * Sets the user identity object.
     *注意这个方法不涉及session或者cookie信息的处理
     *,如果涉及session或cookie处理，请使用switchIdentity方法
     *因为我们知道，一般情况下identity和session或cookie是对应的，比如一般identity是id为100的用户实例
     *那么session中就会有__id|s:3:"100",而__identity有的话，这个cookie中也有_id:"100"这样的信息
     *但是，我们通过setIdentity这个方法仅仅修改了identity这个服务端的实例，session和__identity中还是存储旧的信息，
     *这下明白了吧？
     * Note that this method does not deal with session or cookie. You should usually use [[switchIdentity()]]
     * to change the identity of the current user.
     *
     * @param IdentityInterface|null $identity the identity object associated with the currently logged user.
     * If null, it means the current user will be a guest without any associated identity.
     * @throws InvalidValueException if `$identity` object does not implement [[IdentityInterface]].
     */
    public function setIdentity($identity)
    {
        if ($identity instanceof IdentityInterface) {
            $this->_identity = $identity;
            $this->_access = [];
        } elseif ($identity === null) {
            $this->_identity = null;
        } else {
            throw new InvalidValueException('The identity object must implement IdentityInterface.');
        }
    }

    /**登录一个用户，注意认证逻辑已经在validate()中完成
     * Logs in a user.
     *
     * After logging in a user:
     * - the user's identity information is obtainable from the [[identity]] property
     *
     * If [[enableSession]] is `true`:
     * - the identity information will be stored in session and be available in the next requests
     * - in case of `$duration == 0`: as long as the session remains active or till the user closes the browser
     * - in case of `$duration > 0`: as long as the session remains active or as long as the cookie
     *   remains valid by it's `$duration` in seconds when [[enableAutoLogin]] is set `true`.
     *
     * If [[enableSession]] is `false`:
     * - the `$duration` parameter will be ignored
     *
     * @param IdentityInterface $identity the user identity (which should already be authenticated)
     * @param int $duration number of seconds that the user can remain in logged-in status, defaults to `0`
     * @return bool whether the user is logged in
     */
    public function login(IdentityInterface $identity, $duration = 0)
    {
        //先去执行beforeLogin方法，看到没，Yii框架自带的事件，不是明显的$this->trigger(),而是写到一个方法里，在beforeLogin里trigger
        if ($this->beforeLogin($identity, false, $duration)) {
            $this->switchIdentity($identity, $duration);
            $id = $identity->getId();
            $ip = Yii::$app->getRequest()->getUserIP();
            if ($this->enableSession) {
                $log = "User '$id' logged in from $ip with duration $duration.";
            } else {
                $log = "User '$id' logged in from $ip. Session not enabled.";
            }
            Yii::info($log, __METHOD__);
            //在之类trigger另一个事件，而且事件名和方法名是一致的，里面肯定是trigger(EVENT_AFTER_LOGIN,xxxx)
            $this->afterLogin($identity, false, $duration);
        }

        return !$this->getIsGuest();
    }

    /** 通过令牌认证并登录（注意，认证和登录是两回事呀）
     * Logs in a user by the given access token.
     * This method will first authenticate the user by calling [[IdentityInterface::findIdentityByAccessToken()]]
	 * 成功之后，就用login方法登录为认证用户
     * with the provided access token. If successful, it will call [[login()]] to log in the authenticated user.
	 * 认证失败，或登录失败，就返回null
     * If authentication fails or [[login()]] is unsuccessful, it will return null.
     * @param string $token the access token
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface|null the identity associated with the given access token. Null is returned if
     * the access token is invalid or [[login()]] is unsuccessful.
     */
    public function loginByAccessToken($token, $type = null)
    {
        /* @var $class IdentityInterface */
        $class = $this->identityClass;
        $identity = $class::findIdentityByAccessToken($token, $type);
        if ($identity && $this->login($identity)) {
            return $identity;
        } else {
            return null;
        }
    }

    /**
     * 通过cookie认证并登录，
     * Logs in a user by cookie.
     *一般是第一次打开浏览器，使用__identity这个cookie里所含的认证信息，
     * 来实例化服务端的认证实例，进而完成cookie登录
     * This method attempts to log in a user using the ID and authKey information
     * provided by the [[identityCookie|identity cookie]].
     */
    protected function loginByCookie()
    {
        $data = $this->getIdentityAndDurationFromCookie();
        if (isset($data['identity'], $data['duration'])) {
            $identity = $data['identity'];
            $duration = $data['duration'];
            //cookie登录，也是登录，故也得有beforelogin事件
            if ($this->beforeLogin($identity, true, $duration)) {
                //刷新__identity时，注意$duration是来自cookie的，而cookie里的duration是
                //最初用账号密码登录时设置的，后期只要这个__identity存在，$duration就一直是不变的
                //
                $this->switchIdentity($identity, $this->autoRenewCookie ? $duration : 0);
                $id = $identity->getId();
                $ip = Yii::$app->getRequest()->getUserIP();
                Yii::info("User '$id' logged in from $ip via cookie.", __METHOD__);
                $this->afterLogin($identity, true, $duration);
            }
        }
    }

    /**
     * Logs out the current user.
     * This will remove authentication-related session data.
     * If `$destroySession` is true, all session data will be removed.
     * @param bool $destroySession whether to destroy the whole session. Defaults to true.
     * This parameter is ignored if [[enableSession]] is false.
     * @return bool whether the user is logged out
     */
    public function logout($destroySession = true)
    {
        $identity = $this->getIdentity();
        if ($identity !== null && $this->beforeLogout($identity)) {
            $this->switchIdentity(null);
            $id = $identity->getId();
            $ip = Yii::$app->getRequest()->getUserIP();
            Yii::info("User '$id' logged out from $ip.", __METHOD__);
            if ($destroySession && $this->enableSession) {
                Yii::$app->getSession()->destroy();
            }
            $this->afterLogout($identity);
        }

        return $this->getIsGuest();
    }

    /**检测应用当前是否有人是登录状态，本质上是看有没有Identity这个实例来判断的
     * Returns a value indicating whether the user is a guest (not authenticated).
     * @return bool whether the current user is a guest.
     * @see getIdentity()
     */
    public function getIsGuest()
    {
        return $this->getIdentity() === null;
    }

    /**
	 * getId就是获得认证用户类的getId()方法 
     * Returns a value that uniquely represents the user.
     * @return string|int the unique identifier for the user. If `null`, it means the user is a guest.
     * @see getIdentity()
     */
    public function getId()
    {
        $identity = $this->getIdentity();

        return $identity !== null ? $identity->getId() : null;
    }

    /**
     * Returns the URL that the browser should be redirected to after successful login.
     *
     * This method reads the return URL from the session. It is usually used by the login action which
     * may call this method to redirect the browser to where it goes after successful authentication.
     *
     * @param string|array $defaultUrl the default return URL in case it was not set previously.
     * If this is null and the return URL was not set previously, [[Application::homeUrl]] will be redirected to.
     * Please refer to [[setReturnUrl()]] on accepted format of the URL.
     * @return string the URL that the user should be redirected to after login.
     * @see loginRequired()
     */
    public function getReturnUrl($defaultUrl = null)
    {
        $url = Yii::$app->getSession()->get($this->returnUrlParam, $defaultUrl);
        if (is_array($url)) {
            if (isset($url[0])) {
                return Yii::$app->getUrlManager()->createUrl($url);
            } else {
                $url = null;
            }
        }
        //没有returnUrl就返回到主页
        return $url === null ? Yii::$app->getHomeUrl() : $url;
    }

    /**
     * Remembers the URL in the session so that it can be retrieved back later by [[getReturnUrl()]].
     * @param string|array $url the URL that the user should be redirected to after login.
     * If an array is given, [[UrlManager::createUrl()]] will be called to create the corresponding URL.
     * The first element of the array should be the route, and the rest of
     * the name-value pairs are GET parameters used to construct the URL. For example,
     *
     * ```php
     * ['admin/index', 'ref' => 1]
     * ```
     */
    public function setReturnUrl($url)
    {//使用session来保存登录后要跳转到的url地址。一般在重定向之前执行该方法来设置
        Yii::$app->getSession()->set($this->returnUrlParam, $url);
    }

    /**
     * Redirects the user browser to the login page.
     *
     * Before the redirection, the current URL (if it's not an AJAX url) will be kept as [[returnUrl]] so that
     * the user browser may be redirected back to the current page after successful login.
     *
     * Make sure you set [[loginUrl]] so that the user browser can be redirected to the specified login URL after
     * calling this method.
     *
     * Note that when [[loginUrl]] is set, calling this method will NOT terminate the application execution.
     *
     * @param bool $checkAjax whether to check if the request is an AJAX request. When this is true and the request
     * is an AJAX request, the current URL (for AJAX request) will NOT be set as the return URL.
     * @param bool $checkAcceptHeader whether to check if the request accepts HTML responses. Defaults to `true`. When this is true and
     * the request does not accept HTML responses the current URL will not be SET as the return URL. Also instead of
     * redirecting the user an ForbiddenHttpException is thrown. This parameter is available since version 2.0.8.
     * @return Response the redirection response if [[loginUrl]] is set
     * @throws ForbiddenHttpException the "Access Denied" HTTP exception if [[loginUrl]] is not set or a redirect is
     * not applicable.
     */
    public function loginRequired($checkAjax = true, $checkAcceptHeader = true)
    {
        $request = Yii::$app->getRequest();
        $canRedirect = !$checkAcceptHeader || $this->checkRedirectAcceptable();
        if ($this->enableSession
            && $request->getIsGet()
            && (!$checkAjax || !$request->getIsAjax())
            && $canRedirect
        ) {
            $this->setReturnUrl($request->getUrl());
        }
        if ($this->loginUrl !== null && $canRedirect) {
            $loginUrl = (array) $this->loginUrl;
            if ($loginUrl[0] !== Yii::$app->requestedRoute) {
                return Yii::$app->getResponse()->redirect($this->loginUrl);
            }
        }
        throw new ForbiddenHttpException(Yii::t('yii', 'Login Required'));
    }

    /**在登录前发生的事件，EVENT_BEFORE_LOGIN,
     * 有基于用户名密码的，也有基于持久化免密码的cookie方式登录
     * This method is called before logging in a user.
     * The default implementation will trigger the [[EVENT_BEFORE_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     * @param bool $cookieBased whether the login is cookie-based
     * 第三个参数，用户能维持登录状态的秒数
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * 如果是0，则代表会话cookie,关闭浏览器就删除cookie;或者手动删除session文件
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     * @return bool whether the user should continue to be logged in
     */
    protected function beforeLogin($identity, $cookieBased, $duration)
    {
        $event = new UserEvent([
            'identity' => $identity,
            'cookieBased' => $cookieBased,
            'duration' => $duration,
        ]);
        $this->trigger(self::EVENT_BEFORE_LOGIN, $event);

        return $event->isValid;
    }

    /**
     * This method is called after the user is successfully logged in.
     * The default implementation will trigger the [[EVENT_AFTER_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     * @param bool $cookieBased whether the login is cookie-based
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     */
    protected function afterLogin($identity, $cookieBased, $duration)
    {
        $this->trigger(self::EVENT_AFTER_LOGIN, new UserEvent([
            'identity' => $identity,
            'cookieBased' => $cookieBased,
            'duration' => $duration,
        ]));
    }

    /**
     * This method is invoked when calling [[logout()]] to log out a user.
     * The default implementation will trigger the [[EVENT_BEFORE_LOGOUT]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     * @return bool whether the user should continue to be logged out
     */
    protected function beforeLogout($identity)
    {
        $event = new UserEvent([
            'identity' => $identity,
        ]);
        $this->trigger(self::EVENT_BEFORE_LOGOUT, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked right after a user is logged out via [[logout()]].
     * The default implementation will trigger the [[EVENT_AFTER_LOGOUT]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @param IdentityInterface $identity the user identity information
     */
    protected function afterLogout($identity)
    {
        $this->trigger(self::EVENT_AFTER_LOGOUT, new UserEvent([
            'identity' => $identity,
        ]));
    }

    /**
     * 刷新认证cookie,也就是_identity这个cookie,
     * Renews the identity cookie. 
     * 也不是整个cookie,而是仅仅更新了这个cookie的expire字段而已，当前时间加上duration
     * This method will set the expiration time of the identity cookie to be the current time
     * 奇怪的是，每次访问页面时，__identity的expire字段却都不变，为啥呢？
     * plus the originally specified cookie duration.
     */
    protected function renewIdentityCookie()
    {
        $name = $this->identityCookie['name'];
        $value = Yii::$app->getRequest()->getCookies()->getValue($name);//获得的cookie是解密过的
        if ($value !== null) {
            $data = json_decode($value, true);
            if (is_array($data) && isset($data[2])) {
                $cookie = new Cookie($this->identityCookie);
                $cookie->value = $value;
                $cookie->expire = time() + (int) $data[2];//其实仅仅更新了cookie的expire字段而已
                Yii::$app->getResponse()->getCookies()->add($cookie);
            }
        }
    }

    /**
     * 发送一个实例cookie,
     * Sends an identity cookie.
     * This method is used when [[enableAutoLogin]] is true.
     * It saves [[id]], [[IdentityInterface::getAuthKey()|auth key]], and the duration of cookie-based login
     * information in the cookie.
     * @param IdentityInterface $identity
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * @see loginByCookie()
     * 所谓发送实例Cookie，实际并不是立即发送，我们知道cookie是在请求结束后通过http响应里set-cookie字段把cookie信息回传给浏览器，
     * 所以这里其实是把cookie暂时存起来，存储到response组件的cookie集合里，待最终程序结束时，一并由setcookie()来处理
     */
    protected function sendIdentityCookie($identity, $duration)
    {
        //实例化cookie对象(一个cookie对象包括：name，value，domain,path,expire,size,httponly,secure八个字段）
        $cookie = new Cookie($this->identityCookie);
        //cookie值是怎么回事，请看看
        $cookie->value = json_encode([
            $identity->getId(),
            $identity->getAuthKey(),
            $duration,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        //刷新这个cookie的expire字段为$duration，这个意味着什么呢？
       //比如我们设置默认是30天免输入用户密码登录：那么在第一天登录后，后续的30天内任何一天都可以直接打开浏览器就是登录的状态
       //而且，还会刷新这30天有效期。比如在还剩最后一天（第29天）有效期时打开浏览器访问网站，那么此时的有效期就又刷新为30天了，神奇吧？
        $cookie->expire = time() + $duration;
        //把当前的cookie对象添加到Response对象的cookie集合中待定
        Yii::$app->getResponse()->getCookies()->add($cookie);
    }

    /** 检测认证cookie是否是有效的格式，且包含有有效的认证key
     * Determines if an identity cookie has a valid format and contains a valid auth key.
     * 当enableAutoLogin为true时才会用这个方法来判断（一般优先是session)
     * This method is used when [[enableAutoLogin]] is true.
     * 该方法尝试从cookie中读取认证信息
     * This method attempts to authenticate a user using the information in the identity cookie.
     * @return array|null Returns an array of 'identity' and 'duration' if valid, otherwise null.
     * @see loginByCookie()
     * @since 2.0.9
     */
    protected function getIdentityAndDurationFromCookie()
    {
        //由Request组件获得http请求中带来的cookie
        $value = Yii::$app->getRequest()->getCookies()->getValue($this->identityCookie['name']);
        if ($value === null) {
            return null;
        }
        $data = json_decode($value, true);
        if (count($data) == 3) {
            list ($id, $authKey, $duration) = $data;
            /* @var $class IdentityInterface */
            $class = $this->identityClass;
            $identity = $class::findIdentity($id);
            if ($identity !== null) {
                if (!$identity instanceof IdentityInterface) {
                    throw new InvalidValueException("$class::findIdentity() must return an object implementing IdentityInterface.");
                } elseif (!$identity->validateAuthKey($authKey)) {
                    Yii::warning("Invalid auth key attempted for user '$id': $authKey", __METHOD__);
                } else {
                    return ['identity' => $identity, 'duration' => $duration];
                }
            }
        }
        $this->removeIdentityCookie();
        return null;
    }

    /**
	 * 清除__identity指明的cookie信息
     * Removes the identity cookie.
	 * 该方法在enableAutoLogin为true时才使用
	 * cookie我们知道，在request中有，在response中也有
     * This method is used when [[enableAutoLogin]] is true.
     * @since 2.0.9
     */
    protected function removeIdentityCookie()
    {
        Yii::$app->getResponse()->getCookies()->remove(new Cookie($this->identityCookie));
    }

    /**
	 * 用新的认证实例，替换掉已经认证的旧实例
     * Switches to a new identity for the current user.
     * 当enableSession为true时，该方法将根据$duration的情况使用session或cookie来存储用户认证信息
     * When [[enableSession]] is true, this method may use session and/or cookie to store the user identity information,
     * according to the value of `$duration`. Please refer to [[login()]] for more details.
     * 当当前用户需要关联相关的认证信息时，会使用该方法，但该方法一般不会直接调用，而是在login(),logout()和loginByCookie()内调用，
     * This method is mainly called by [[login()]], [[logout()]] and [[loginByCookie()]]
     * when the current user needs to be associated with the corresponding identity information.
     * $IdentityInterfase是有关当前用户的认证实例，如果是null，则代表把当前用户登出（置为访客）
     * @param IdentityInterface|null $identity the identity information to be associated with the current user.
     * If null, it means switching the current user to be a guest.
	 * 整数，$duration登录用户能够保持登录状态的持续时间（秒），当$identity不是null时$duration才有用
     * @param int $duration number of seconds that the user can remain in logged-in status.
     * This parameter is used only when `$identity` is not null.
     */
    public function switchIdentity($identity, $duration = 0)
    {
        $this->setIdentity($identity);
		/**
		 * 不启用session时，直接退出，
		 * 一般restFul API的项目时才会不使用基于session的认证，所以对应的session和cookie信息不用处理
		 */
        if (!$this->enableSession) {
            return;
        }

        /* Ensure any existing identity cookies are removed.
         *  当允许cookie存储认证信息时，那得把旧的__identity干掉(也可能根本没有__identity这个cookie)
         */
        if ($this->enableAutoLogin) {
            $this->removeIdentityCookie();
        }

        //获得session对象
        $session = Yii::$app->getSession();
        if (!YII_ENV_TEST) {
            /**
             *不是TEST环境，就清除旧的session文件，生成新的session文件。
            *注意这里虽然新生成的session文件里的内容和旧session文件是一样的
            *但实际并不是刷新session文件，因为session ID肯定不一样了,故最终其实是修改了session文件的名字而已
            */
            $session->regenerateID(true);
        }
        //删除session中的变量__id，因为session文件里的内容还是旧的。
        //注意删除session变量和删除cookie的方式不同，删除cookie一般是设置一个过期的expire，让客户端浏览器去删除cookie，
        //而删除session的话就直接unset($_SESSION['xxx'])即可
        $session->remove($this->idParam);
        //删除session变量__expire
        $session->remove($this->authTimeoutParam);
        //下面开始设置全新的会话session数据
        if ($identity) {
            //设置一个新的session变量__id
            $session->set($this->idParam, $identity->getId());
            //当authTimeout有值时，就设置session变量__expire，用来控制两次http请求的间隔超时
            if ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
            //当absoluteAuthTimeout有值时，就设置session变量__absoluteExpire,用来控制session变量的绝对会话周期
            if ($this->absoluteAuthTimeout !== null) {
                $session->set($this->absoluteAuthTimeoutParam, time() + $this->absoluteAuthTimeout);
            }
            /*
             *当$duration大于0时，且enableAutoLogin为true,那么就把认证信息存储到Cookie里
            *可见，在enableAutoLogin为false的情况下（只使用session），$duration是没有用的
             * （rememberMe表单项是无用的，它是配合cookie方式的）
            *当只开启enableAutoLogin但又不设置$duration时，还是和enableAutoLogin为false没有什么区别
            *因为这两种情况下，都会在session文件中存储登录用户的id。最终还是通过这个来判断是否登录，获得认证信息的
            *只要用户退出或者关闭浏览器，本次会话就结束了。
            *
            *只有enableAutoLogin且$duration大于0，才会多给客户端发送一个_identity的cookie，这样用户及时关闭了浏览器
            *在一定时间内（默认一个月）还是可以免登录的。（有些浏览器设置是无论如何，只要关闭浏览器就清除所有的cookie；其他浏览器大部分支持）
             */
            if ($duration > 0 && $this->enableAutoLogin) {
                $this->sendIdentityCookie($identity, $duration);
            }
        }
    }

    /**
     * 通过session和cookie来更新认证状态（一般由用户访问页面时从session文件中读取，一般用__id参数）
     * Updates the authentication status using the information from session and cookie.
     *该方法尝试通过__id会话变量来决定用户实例（确切的来说，判断用户是否是登录状态）
     *只要我们在session文件中把“__id|s:3:"100";”删除掉，则刷新页面就能看到当前用户并未登录；而只要我们把“__id|s:3:"100";“这个session变量
     *加入到session文件时，则服务端就认为当前用户是登录着的状态(手动修改session文件有难度,但也可尝试）。
     *一句话，不使用cookie而使用session保存会话信息时，本质上就是通过session文件里的__id|s:3:"100"来决定当前100用户是否是登录着的
     *但我们知道这仅仅是个ID而已，所以每次还得实例化，才能在本次请求中使用。
     * This method will try to determine the user identity using the [[idParam]] session variable.
     *如果authTimeout设置的话，还要刷新这个时间戳（表示最新的访问时间，为后续的超时判断做新的起始点）
     * If [[authTimeout]] is set, this method will refresh the timer.
     *如果用户是否登录不能通过session变量判断的话，将会使用loginByCookie()来登录
     * If the user identity cannot be determined by session, this method will try to [[loginByCookie()|login by cookie]]
     * if [[enableAutoLogin]] is true.
     * 这个方法很重要，我们看到它是protected，可见应该有其他方法来内部调用而不是直接在外部调用。在什么时候调用呢？
     * 目前来看，啥时候需要认证对象时，才会判断是否已经登录，也就是getIdentity()方法里，调用的renewAuthStatus()
     */
    protected function renewAuthStatus()
    {
        $session = Yii::$app->getSession();
        //优先从Session文件中读取认证信息
        //本次请求是否有Session ID?session是否已开启？是否能获得SessionID？
        //（初次打开浏览器访问网站时一般没有PHPSESSID）
        $id = $session->getHasSessionId() || $session->getIsActive() ? $session->get($this->idParam) : null;
        if ($id === null) {
            $identity = null;
        } else {
            /* @var $class IdentityInterface */
            $class = $this->identityClass;
            $identity = $class::findIdentity($id);
        }

        $this->setIdentity($identity);

        if ($identity !== null && ($this->authTimeout !== null || $this->absoluteAuthTimeout !== null)) {
            $expire = $this->authTimeout !== null ? $session->get($this->authTimeoutParam) : null;
            $expireAbsolute = $this->absoluteAuthTimeout !== null ? $session->get($this->absoluteAuthTimeoutParam) : null;
            if ($expire !== null && $expire < time() || $expireAbsolute !== null && $expireAbsolute < time()) {
                $this->logout(false);
            } elseif ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
        }
        //若开启enableAutoLogin，则分两种情况：
        //1 前面session已经登录过了，那就不再登录，则根据autoRenewCookie来判断是否更新_identity这个cookie
        /*2 前面的session没有登录，则还会从cookie中尝试登录（因为cookie中有可能有_identity这个认证信息。这种场景一般发生在很多天以后打开浏览器
        再次访问网站，服务端对应的essionID已经不存在了，但浏览器里__identity这个cookie尚未过期（默认一个月），故仍然可以免输入密码登录)
        */
        if ($this->enableAutoLogin) {
            if ($this->getIsGuest()) {//单从页面头部显示登录者姓名来说，这里又调用getIsGuest()判断是否登录了，但第一次执行时$this->_identity已经是null
                $this->loginByCookie();
            } elseif ($this->autoRenewCookie) {
                $this->renewIdentityCookie();
            }
        }
    }

    /**
     * Checks if the user can perform the operation as specified by the given permission.
     *
     * Note that you must configure "authManager" application component in order to use this method.
     * Otherwise it will always return false.
     *
     * @param string $permissionName the name of the permission (e.g. "edit post") that needs access check.
     * @param array $params name-value pairs that would be passed to the rules associated
     * with the roles and permissions assigned to the user.
     * @param bool $allowCaching whether to allow caching the result of access check.
     * When this parameter is true (default), if the access check of an operation was performed
     * before, its result will be directly returned when calling this method to check the same
     * operation. If this parameter is false, this method will always call
     * [[\yii\rbac\CheckAccessInterface::checkAccess()]] to obtain the up-to-date access result. Note that this
     * caching is effective only within the same request and only works when `$params = []`.
     * @return bool whether the user can perform the operation as specified by the given permission.
     */
    public function can($permissionName, $params = [], $allowCaching = true)
    {
        if ($allowCaching && empty($params) && isset($this->_access[$permissionName])) {
            return $this->_access[$permissionName];
        }
        if (($accessChecker = $this->getAccessChecker()) === null) {
            return false;
        }
        $access = $accessChecker->checkAccess($this->getId(), $permissionName, $params);
        if ($allowCaching && empty($params)) {
            $this->_access[$permissionName] = $access;
        }

        return $access;
    }

    /**
     * Checks if the `Accept` header contains a content type that allows redirection to the login page.
     * The login page is assumed to serve `text/html` or `application/xhtml+xml` by default. You can change acceptable
     * content types by modifying [[acceptableRedirectTypes]] property.
     * @return bool whether this request may be redirected to the login page.
     * @see acceptableRedirectTypes
     * @since 2.0.8
     */
    protected function checkRedirectAcceptable()
    {
        $acceptableTypes = Yii::$app->getRequest()->getAcceptableContentTypes();
        if (empty($acceptableTypes) || count($acceptableTypes) === 1 && array_keys($acceptableTypes)[0] === '*/*') {
            return true;
        }

        foreach ($acceptableTypes as $type => $params) {
            if (in_array($type, $this->acceptableRedirectTypes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns auth manager associated with the user component.
     *
     * By default this is the `authManager` application component.
     * You may override this method to return a different auth manager instance if needed.
     * @return \yii\rbac\ManagerInterface
     * @since 2.0.6
     * @deprecated since version 2.0.9, to be removed in 2.1. Use [[getAccessChecker()]] instead.
     */
    protected function getAuthManager()
    {
        return Yii::$app->getAuthManager();
    }

    /**
     * Returns the access checker used for checking access.
     * @return CheckAccessInterface
     * @since 2.0.9
     */
    protected function getAccessChecker()
    {
        return $this->accessChecker !== null ? $this->accessChecker : $this->getAuthManager();
    }
}
