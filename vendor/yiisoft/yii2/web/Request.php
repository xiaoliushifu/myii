<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\StringHelper;

/**
* Request组件，代表了Http的请求实体
 * The web Request class represents an HTTP request
 * 囊括$_SERVER超全局数组，并解析它们，以处理不同Web服务器的不一致性
 * It encapsulates the $_SERVER variable and resolves its inconsistency among different Web servers.
 * 也提供了一个这样的接口：从$_POST,$_GET,$_COOKIES和通过http的put，delete方法发送的REST 参数
 * Also it provides an interface to retrieve request parameters from $_POST, $_GET, $_COOKIES and REST
 * parameters sent via other HTTP methods like PUT or DELETE.
 *Request配置为应用主体组件
 * Request is configured as an application component in [[\yii\web\Application]] by default.
 * 可以通过Yii::$app->request。
 * You can access that instance via `Yii::$app->request`.
 *
 *可以先看看官网的guide
 * For more details and usage information on Request, see the [guide article on requests](guide:runtime-requests).
 *
 * 字符串，$absoluteUrl  比如http:://www.cctv.com/index.php/admin/?r=site/index
 * @property string $absoluteUrl The currently requested absolute URL. This property is read-only.
 * 数组，可接受的内容类型,由优先级排列。
 * @property array $acceptableContentTypes The content types ordered by the quality score. Types with the
 * 数组的键是内容类型，数组的值对应的优先级
 * highest scores will be returned first. The array keys are the content types, while the array values are the
 * corresponding quality score and other parameters as given in the header.
 * 数组，$acceptableLanguages  可接受的语言，优先级排列
 * @property array $acceptableLanguages The languages ordered by the preference level. The first element
 * represents the most preferred language.
 * 字符串，null  $authPassword  通过HTTP authentication发送的，如果没有发送则是null,只读
 * @property string|null $authPassword The password sent via HTTP authentication, null if the password is not
 * given. This property is read-only.
 * 字符串，null;  $authUser   通过HTTP authentication发送的，如果没有发送则是null,只读
 * @property string|null $authUser The username sent via HTTP authentication, null if the username is not
 * given. This property is read-only.
 * 字符串，$baseUrl   相对与当前应用主体的URL   /index.php(不带host）
 * @property string $baseUrl The relative URL for the application.
 * 数组，$bodyParams，请求参数来自于http请求实体（不是queryString)
 * @property array $bodyParams The request parameters given in the request body.
 * 字符串，$contentType  请求内容类型，如果读取不到内容类型（content-TYPE）,则返回null，只读
 * @property string $contentType Request content-type. Null is returned if this information is not available.
 * This property is read-only.
 * cookie集合    $cookie  只读
 * @property CookieCollection $cookies The cookie collection. This property is read-only.
 * 字符串，$csrfToken  用来执行CSRF验证，只读
 * @property string $csrfToken The token used to perform CSRF validation. This property is read-only.
 * 字符串，$csrfTokenFromHeader   见名知意，通过http请求header里的CSRF_HEADER。没有则返回null。只读
 * @property string $csrfTokenFromHeader The CSRF token sent via [[CSRF_HEADER]] by browser. Null is returned
 * if no such header is sent. This property is read-only.
 * 数组，$eTags   实体标记，只读属性
 * @property array $eTags The entity tags. This property is read-only.
 * HeaderCollection  $headers  头部对象实体。只读
 * @property HeaderCollection $headers The header collection. This property is read-only.
 * 字符串，null,$hostInfo模式和主机部分（带端口后如果需要）。比如http://www.yiiframework.com
 * @property string|null $hostInfo Schema and hostname part (with port number if needed) of the request URL
 * (e.g. `http://www.yiiframework.com`), null if can't be obtained from `$_SERVER` and wasn't set. See
 * [[getHostInfo()]] for security related notes on this property.
 * 
 * 字符串，null。$hostName，比如 www.yiiframework.com。只读
 * @property string|null $hostName Hostname part of the request URL (e.g. `www.yiiframework.com`). This
 * property is read-only.
 * @property bool $isAjax Whether this is an AJAX (XMLHttpRequest) request. This property is read-only.
 * @property bool $isDelete Whether this is a DELETE request. This property is read-only.
 * @property bool $isFlash Whether this is an Adobe Flash or Adobe Flex request. This property is read-only.
 * @property bool $isGet Whether this is a GET request. This property is read-only.
 * @property bool $isHead Whether this is a HEAD request. This property is read-only.
 * @property bool $isOptions Whether this is a OPTIONS request. This property is read-only.
 * @property bool $isPatch Whether this is a PATCH request. This property is read-only.
 * @property bool $isPjax Whether this is a PJAX request. This property is read-only.
 * @property bool $isPost Whether this is a POST request. This property is read-only.
 * @property bool $isPut Whether this is a PUT request. This property is read-only.
 * @property bool $isSecureConnection If the request is sent via secure channel (https). This property is
 * read-only.
 * @property string $method Request method, such as GET, POST, HEAD, PUT, PATCH, DELETE. The value returned is
 * turned into upper case. This property is read-only.
 * @property string $pathInfo Part of the request URL that is after the entry script and before the question
 * mark. Note, the returned path info is already URL-decoded.
 * @property int $port Port number for insecure requests.
 * 数组，$queryParams  请求参数值
 * @property array $queryParams The request GET parameter values.
 * 字符串，$queryString  请求URL的问号之后的部分
 * @property string $queryString Part of the request URL that is after the question mark. This property is
 * read-only.
 * 字符串，$rawBody   原始http的请求实体
 * @property string $rawBody The request body.
 * 字符串，$referrer  。URL的referrer,只读
 * @property string|null $referrer URL referrer, null if not available. This property is read-only.
 * 字符串，$scriptFile  入口脚本路径
 * @property string $scriptFile The entry script file path.
 * 字符串，入口脚本的相对路径
 * @property string $scriptUrl The relative URL of the entry script.
 * 整型，$securePort，安全请求（https)的端口号
 * @property int $securePort Port number for secure requests.
 * 字符串，$serverName  服务器名字，只读
 * @property string $serverName Server name, null if not available. This property is read-only.
 * 整型，$serverPort  服务器的端口号，只读
 * @property int|null $serverPort Server port number, null if not available. This property is read-only.
 * 字符串，$url  当前请求的相对URL,注意，返回的信息是URL-encoded的
 * @property string $url The currently requested relative URL. Note that the URI returned is URL-encoded.
 * 字符串，$userAgent  用户代理，只读
 * @property string|null $userAgent User agent, null if not available. This property is read-only.
 * 字符串，$userHost 用户主机名，只读（客户端的主机名？）
 * @property string|null $userHost User host name, null if not available. This property is read-only.
 * 字符串，$userIP  用户ip地址
 * @property string|null $userIP User IP address, null if not available. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Request extends \yii\base\Request
{
    /**
     * 发送CSRF字符串的http头字段的名字（请求还是响应？）
     * The name of the HTTP header for sending CSRF token.
     */
    const CSRF_HEADER = 'X-CSRF-Token';
    /**
     * The length of the CSRF token mask.
     */
    const CSRF_MASK_LENGTH = 8;

    /**
     * 是否启用跨站请求伪造验证
     * @var bool whether to enable CSRF (Cross-Site Request Forgery) validation. Defaults to true.
     * When CSRF validation is enabled, forms submitted to an Yii Web application must be originated
     * from the same application. If not, a 400 HTTP exception will be raised.
     * 这个机制，需要客户端浏览器开启接收cookie的支持
     * Note, this feature requires that the user client accepts cookie. Also, to use this feature,
     * 通过post方法提交的表单里必须包含一个隐藏域，它的名字由csrfParam成员指定
     * forms submitted via POST method must contain a hidden input whose name is specified by [[csrfParam]].
     * 可以使用yii\helpers\Html::beginForm()来生成它的隐藏项
     * You may use [[\yii\helpers\Html::beginForm()]] to generate his hidden input.
     *在客户端的JavaScript里，可以通过yii.getCsrfParam()获得[[csrfParam]]的值，通过yii.getCsrfToken()获得csrfToken的值。
     * In JavaScript, you may get the values of [[csrfParam]] and [[csrfToken]] via `yii.getCsrfParam()` and
     * 但是yiiAsset必须注册才行。还需要在页面里使用Html::csrfMetaTags()来包含meta标签
     * `yii.getCsrfToken()`, respectively. The [[\yii\web\YiiAsset]] asset must be registered.
     * 
     * You also need to include CSRF meta tags in your pages by using [[\yii\helpers\Html::csrfMetaTags()]].
     *
     * @see Controller::enableCsrfValidation  参考这个
     * @see http://en.wikipedia.org/wiki/Cross-site_request_forgery 参考wiki
     */
    //默认开启
    public $enableCsrfValidation = true;
    /**
     * 实现CSRF验证的那个cookie的名字
     * @var string the name of the token used to prevent CSRF. Defaults to '_csrf'.
     * 仅在$enableCsrfValidation为true时才有用
     * This property is used only when [[enableCsrfValidation]] is true.
     */
    public $csrfParam = '_csrf';
    /**
     * 为_csrf这个cookie配置httpOnly字段
     * @var array the configuration for creating the CSRF [[Cookie|cookie]]. This property is used only when
     * 该属性得在enableCsrfValidation和enableCsrfCookie属性都启用时才可生效
     * both [[enableCsrfValidation]] and [[enableCsrfCookie]] are true.
     */
    public $csrfCookie = ['httpOnly' => true];
    /**
     * 使用cookie方式存储csrf字符串
     * 布尔，是否使用cookie方式存储CSRF令牌。如果不允许，那么将会使用session来存储
     * @var bool whether to use cookie to persist CSRF token. If false, CSRF token will be stored
     * 其中session名也是由csrfParam来指定，注意，虽然用session存储增加安全性，但是它需要每个页面都要开启session
     * in session under the name of [[csrfParam]]. Note that while storing CSRF tokens in session increases
     * 这将影响网站性能
     * security, it requires starting a session for every page, which will degrade your site performance.
     */
    public $enableCsrfCookie = true;
    /**
     * 为了确保客户端cookie不被篡改，是否应该加密(验证）那些种植给客户端的所有cookie。默认true
     * @var bool whether cookies should be validated to ensure they are not tampered. Defaults to true.
     */
    public $enableCookieValidation = true;
    /**
     * 配合上一个属性，需要给cookie加密时，加密算法需要一个加密的token，这个属性在初始化request组件时指出
     * @var string a secret key used for cookie validation. This property must be set if [[enableCookieValidation]] is true.
     */
    public $cookieValidationKey;
    /**
     * 字符串，POST参数的名字，表名是否一个http请求是一个通过POST方法来建立的PUT,PATCH,或者DELETE请求通道，
     * @var string the name of the POST parameter that is used to indicate if a request is a PUT, PATCH or DELETE
     * 默认是_method
     * request tunneled through POST. Defaults to '_method'.
     * @see getMethod() 参考
     * @see getBodyParams() 参考
     */
    public $methodParam = '_method';
    /**
     * 数组，把原始http请求转为[bodyParams]]的解析器
     * @var array the parsers for converting the raw HTTP request body into [[bodyParams]].
     * 数组的keys是请求的Content-Types，而数组的values是对应的配置（用Yii::createObject据此配置来创建解析对象）
     * The array keys are the request `Content-Types`, and the array values are the
     * corresponding configurations for [[Yii::createObject|creating the parser objects]].
     * 解析对象必须实现[[RequestParserInterface]].接口
     * A parser must implement the [[RequestParserInterface]].
     *如果需要解析一个JSON请求的话，可以像下面的例子那样使用[[JsonParser]]类：
     * To enable parsing for JSON requests you can use the [[JsonParser]] class like in the following example:
     *
     * ```
     * [
     *     'application/json' => 'yii\web\JsonParser',
     * ]
     * ```
     *如果想用一个解析器解析所有的请求类型，那就用"*"作为数组的key
     * To register a parser for parsing all request types you can use `'*'` as the array key.
     * 这将在所有Content-type不匹配时作为备用
     * This one will be used as a fallback in case no other types match.
     *
     * @see getBodyParams()  参考
     */
    public $parsers = [];

    /**
     * CookieCollection  请求的cookie集合类
     * @var CookieCollection Collection of request cookies.
     */
    private $_cookies;
    /**
     * HeaderCollection http请求头部集合
     * @var HeaderCollection Collection of request headers.
     */
    private $_headers;


    /**
     * 这是一个web请求继承父类base\request必须实现的方法
     * 用来把当前的http请求解析成一个路由和关联的参数
     * Resolves the current request into a route and the associated parameters.
     * 返回值，数组，第一个元素是路由；第二个元素是相关的参数
     * @return array the first element is the route, and the second is the associated parameters.
     * @throws NotFoundHttpException if the request cannot be resolved.
     */
    public function resolve()
    {
        //不是request直接解析，还得靠UrlManager组件来帮忙
        //未开启美化功能，一般返回r参数和空数组
        $result = Yii::$app->getUrlManager()->parseRequest($this);
        
        if ($result !== false) {
            //$route就是请求url里的r参数，$params永远是空数组
            list ($route, $params) = $result;
            //注意，成员属性_queryParams为空时，会修改$_GET
            if ($this->_queryParams === null) {
                //回忆一下，php数组相加的结果是什么呢？
                //下标相同的（索引或关联），前者保留。
                $_GET = $params + $_GET; // preserve numeric keys
            } else {
                $this->_queryParams = $params + $this->_queryParams;
            }
            //路由信息（r后的参数），和整个的$_GET（$_GET已经被调整过了）
            return [$route, $this->getQueryParams()];
        } else {
            //路由必须解析出来，否则抛异常
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }

    /**
     * 返回头集合对象
     * Returns the header collection.
     * 头集合对象包含了http请求的头部信息
     * The header collection contains incoming HTTP headers.
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            //实例化头集合对象
            $this->_headers = new HeaderCollection;
            //apache服务器才有的函数，这也是php跟外界有依赖的体现
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            //PECL pecl_http >= 0.10.0时才有的函数
            } elseif (function_exists('http_get_request_headers')) {
                $headers = http_get_request_headers();
            } else {
                //实在不行，就遍历$_SERVER。这是跨平台的
                foreach ($_SERVER as $name => $value) {
                    //变量里，是"HTTP_"这五个字符开头的，才是http请求里的头字段
                    if (strncmp($name, 'HTTP_', 5) === 0) {
                        //把HTTP_CONTENT_TYPE 修改成  Content-type。然后添加到头集合对象里
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $this->_headers->add($name, $value);
                    }
                }
                //返回头集合对象即可
                return $this->_headers;
            }
            //通过php原生函数（getallHeaders(),或者http_get_request_headers())获得的http请求头信息，单独也添加到头集合对象里
            foreach ($headers as $name => $value) {
                $this->_headers->add($name, $value);
            }
        }
        //还是返回头集合对象
        return $this->_headers;
    }

    /**
     * 返回当前http请求的方法（比如，GET,POST,HEAD,PUT,PATCH,DELETE)
     * Returns the method of the current request (e.g. GET, POST, HEAD, PUT, PATCH, DELETE).
     * 返回字符串，大写的
     * @return string request method, such as GET, POST, HEAD, PUT, PATCH, DELETE.
     * The value returned is turned into upper case.
     */
    public function getMethod()
    {
        //如果POST方式里，带有_method的参数，优先使用这个
        //一般用POST方法来模拟PUT,PATCH这样的请求（在laravel中首次见到）
        if (isset($_POST[$this->methodParam])) {
            return strtoupper($_POST[$this->methodParam]);
        }
        //或者$_SERVER里有这个参数。这个是什么知识点，是自定义的？还是公认的？
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }

        //最后使用原生的超全局数组来确定
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }
        //最后，上述三种情况都不能确定的话，那就用GET吧
        return 'GET';
    }

    /**
     * 布尔，判断是不是GET方法
     * Returns whether this is a GET request.
     * @return bool whether this is a GET request.
     */
    public function getIsGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * 布尔，判断是不是OPTIONS方法
     * Returns whether this is an OPTIONS request.
     * @return bool whether this is a OPTIONS request.
     */
    public function getIsOptions()
    {
        return $this->getMethod() === 'OPTIONS';
    }

    /**
     * 布尔，判断是不是HEAD方法
     * Returns whether this is a HEAD request.
     * @return bool whether this is a HEAD request.
     */
    public function getIsHead()
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * 布尔，判断是不是POST方法
     * Returns whether this is a POST request.
     * @return bool whether this is a POST request.
     */
    public function getIsPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * 布尔，判断是不是DELETE方法
     * Returns whether this is a DELETE request.
     * @return bool whether this is a DELETE request.
     */
    public function getIsDelete()
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * 布尔，判断是不是PUT方法
     * Returns whether this is a PUT request.
     * @return bool whether this is a PUT request.
     */
    public function getIsPut()
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * 布尔，判断是不是PATCH方法
     * Returns whether this is a PATCH request.
     * @return bool whether this is a PATCH request.
     */
    public function getIsPatch()
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * 布尔，判断是不是一个AJAX请求
     * Returns whether this is an AJAX (XMLHttpRequest) request.
     *注意，如果跨域的话，不会设置这个头字段HTTP_X_REQUESTED_WITH。因为跨域根本就已经不是ajax请求了。
     *跨域和ajax根本就不是同一个概念。所以现在市面上所说的解决ajax不能跨域的问题。其实已经不是ajax了。而是使用另一种非ajax的方式。
     *只要用ajax，就一定不能跨域。
     *所以这里说，跨域不会设置头字段HTTP_X_REQUESTED_WITH，是因为跨域已经不是ajax请求，而是修改为普通的http请求。
     * Note that jQuery doesn't set the header in case of cross domain
     * requests: https://stackoverflow.com/questions/8163703/cross-domain-ajax-doesnt-send-x-requested-with-header
     *
     * @return bool whether this is an AJAX (XMLHttpRequest) request.
     */
    public function getIsAjax()
    {
        //单独通过$_SERVER里的这个头字段判断，我觉得有待商榷，因为这个头字段是可以随意更改的。
        //况且，这应该不是什么公认的吧？这只是Jquery框架里的方式，原生js可不是这样弄的。所以，并不是从HTTP协议上强制规定的。
        //因为HTTP协议只规定了，带有X_AAAA的都是自定义的头部。所以，并不是强制规定的。完全可以自定义为X_AAA来表示是否为ajax请求。
        //也是可以的。
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * 是否是PJAX请求，这是什么。是yii框架自定义的吗？
     * 应该是，因为我们又看到了X_PJAX是符合http协议了关于自定义头部的消息。
     * 但是什么是pjax呢？我们后续再说
     * Returns whether this is a PJAX request
     * @return bool whether this is a PJAX request
     */
    public function getIsPjax()
    {
        //首先是ajax，然后。。。。。。。
        return $this->getIsAjax() && !empty($_SERVER['HTTP_X_PJAX']);
    }

    /**
     * 是否是Flash或者flex请求。有点不明白这有什么背景知识。后续再说.
	 * 估计属于flash软件自定义的吧
     * Returns whether this is an Adobe Flash or Flex request.
     * @return bool whether this is an Adobe Flash or Adobe Flex request.
     */
    public function getIsFlash()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) &&
            //我们看到在用户代理里，必须出现Shockwave这个关键字，或者Flash才行
            (stripos($_SERVER['HTTP_USER_AGENT'], 'Shockwave') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') !== false);
    }

    private $_rawBody;

    /**
     * Returns the raw HTTP request body.
     * @return string the request body
     */
    public function getRawBody()
    {
        if ($this->_rawBody === null) {
            //这个之前就已经知道，php可以通过这个方法获得原始的请求实体
            //但是表单请求是form-data的类型时是无效的。
			//Content-Type: multipart/form-data; boundary=${bound}
			//不像form-urlencoded是a=b&c=d&e=f这样。form-data是由分隔符切割开的一个个的a=b
            $this->_rawBody = file_get_contents('php://input');
        }

        return $this->_rawBody;
    }

    /**
     * 设置原始的http请求实体，主要在测试环境中使用
     * Sets the raw HTTP request body, this method is mainly used by test scripts to simulate raw HTTP requests.
     * @param string $rawBody the request body
     */
    public function setRawBody($rawBody)
    {
        $this->_rawBody = $rawBody;
    }

    private $_bodyParams;

    /**
     * 返回请求实体里的参数（一般是POST方式的参数）
     * Returns the request parameters given in the request body.
     *由解析器来决定。
     * Request parameters are determined using the parsers configured in [[parsers]] property.
     * 如果内容类型没有指定解析器，最后有mb_parse_str()原生函数
     * 获得实体参数，就是获得_BodyParams成员的值
     * If no parsers are configured for the current [[contentType]] it uses the PHP function `mb_parse_str()`
     * to parse the [[rawBody|request body]].
     * @return array the request parameters given in the request body.
     * @throws \yii\base\InvalidConfigException if a registered parser does not implement the [[RequestParserInterface]].
     * @see getMethod()
     * @see getBodyParam()
     * @see setBodyParams()
     */
    public function getBodyParams()
    {
        if ($this->_bodyParams === null) {
            
            //是否是post方式模拟的PUT,PATCH请求参数
            if (isset($_POST[$this->methodParam])) {
                //无论模拟PUT,PATCH等，本质还是POST方式，所以参数还是从$_POST来读取
                $this->_bodyParams = $_POST;
                unset($this->_bodyParams[$this->methodParam]);
                return $this->_bodyParams;
            }
            //先看内容类型，稍后再说内容（实体参数）
            $rawContentType = $this->getContentType();
            //什么时候出现";",来看看"Content-type:text/html;charset=utf-8"
            if (($pos = strpos($rawContentType, ';')) !== false) {
                // e.g. application/json; charset=UTF-8
                //只要内容类型，不需要字符集
                $contentType = substr($rawContentType, 0, $pos);
            } else {
                $contentType = $rawContentType;
            }

            //针对该类型的解析器
            if (isset($this->parsers[$contentType])) {
                //实例化解析器的对象
                $parser = Yii::createObject($this->parsers[$contentType]);
                if (!($parser instanceof RequestParserInterface)) {
                    throw new InvalidConfigException("The '$contentType' request parser is invalid. It must implement the yii\\web\\RequestParserInterface.");
                }
                $this->_bodyParams = $parser->parse($this->getRawBody(), $rawContentType);
            //用"*"则表示能够解析以前内容类型的解析器
            } elseif (isset($this->parsers['*'])) {
                $parser = Yii::createObject($this->parsers['*']);
                if (!($parser instanceof RequestParserInterface)) {
                    throw new InvalidConfigException("The fallback request parser is invalid. It must implement the yii\\web\\RequestParserInterface.");
                }
                $this->_bodyParams = $parser->parse($this->getRawBody(), $rawContentType);
             //post方式的http请求，则直接使用现成的$_POST
            } elseif ($this->getMethod() === 'POST') {
                // PHP has already parsed the body so we have all params in $_POST
                $this->_bodyParams = $_POST;
            //平常普通的HTTP的GET请求
            } else {
                $this->_bodyParams = [];
                //用PHP原生函数来解析
                //这个函数的功能，是解析一个urlencode编码过的字符串，到第二个数组参数$this->_bodyParams中
                mb_parse_str($this->getRawBody(), $this->_bodyParams);
            }
        }

        return $this->_bodyParams;
    }

    /**
     * 设置http请求实体的参数（键值对）
     * Sets the request body parameters.
     * @param array $values the request body parameters (name-value pairs)
     * @see getBodyParam()
     * @see getBodyParams()
     */
    public function setBodyParams($values)
    {
        $this->_bodyParams = $values;
    }

    /**
     * 获取指定的http请求实体参数
     * Returns the named request body parameter value.
     * //如果参数不存在，则使用第二个参数返回（有点多次一举吧？）
     * If the parameter does not exist, the second parameter passed to this method will be returned.
     * @param string $name the parameter name
     * @param mixed $defaultValue the default parameter value if the parameter does not exist.
     * @return mixed the parameter value
     * @see getBodyParams()
     * @see setBodyParams()
     */
    public function getBodyParam($name, $defaultValue = null)
    {
        $params = $this->getBodyParams();

        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * 获得POST方式里的参数。
     * 如果没有指定$name,则获取全部，否则获取指定$name的参数值
     * Returns POST parameter with a given name. If name isn't specified, returns an array of all POST parameters.
     *
     * @param string $name the parameter name
     * @param mixed $defaultValue the default parameter value if the parameter does not exist.
     * @return array|mixed
     */
    public function post($name = null, $defaultValue = null)
    {
        if ($name === null) {
			//还是使用getBodyParams方法获取的
            return $this->getBodyParams();
        } else {
            return $this->getBodyParam($name, $defaultValue);
        }
    }

    private $_queryParams;

    /**
     * 返回成员属性_queryParams（也就是问号后面的字符串，这几乎就是$_GET了）
     * Returns the request parameters given in the [[queryString]].
     *
     *如果这个_queryParams成员为null的话，则返回$_GET
     * This method will return the contents of `$_GET` if params where not explicitly set.
     * @return array the request GET parameter values.
     * @see setQueryParams()//参考该方法设置_queryParams成员属性
     */
    public function getQueryParams()
    {
        if ($this->_queryParams === null) {
            return $_GET;
        }

        return $this->_queryParams;
    }

    /**
     * 设置queryString参数
     * Sets the request [[queryString]] parameters.
     * @param array $values the request query parameters (name-value pairs)
     * @see getQueryParam()
     * @see getQueryParams()
     */
    public function setQueryParams($values)
    {
        $this->_queryParams = $values;
    }

    /**
     * 获取GET方式的参数（指定的，或者全部$_GET)
     * Returns GET parameter with a given name. If name isn't specified, returns an array of all GET parameters.
     *
     * @param string $name the parameter name
     * @param mixed $defaultValue the default parameter value if the parameter does not exist.
     * @return array|mixed
     */
    public function get($name = null, $defaultValue = null)
    {
        if ($name === null) {
            //这还不完全是$_GET，因为一旦_queryString有值的话，就不是$_GET了
			//从上一个方法可知，_queryParams是可以手动设置的
            return $this->getQueryParams();
        } else {
            return $this->getQueryParam($name, $defaultValue);
        }
    }

    /**
     * 返回指定的GET参数
     * 这个一般在UrlManager组件里解析路由时调用，urlManager未开启美化的情况下
     * Returns the named GET parameter value.
     * 如果GET参数不存在，则默认返回第二个参数
     * If the GET parameter does not exist, the second parameter passed to this method will be returned.
     * @param string $name the GET parameter name.   $name一般是r。路由参数的名字，是由urlManager组件绝对的
     * @param mixed $defaultValue the default parameter value if the GET parameter does not exist.
     * @return mixed the GET parameter value
     * @see getBodyParam()
     */
    public function getQueryParam($name, $defaultValue = null)
    {
        //这个方法名不过是多了个s而已,getQueryParams，其实就是http请求URL里，问号后面的部分
        //而我们知道，这部分其实就是$_GET(跟http使用的get,post请求方法没有关系）
        $params = $this->getQueryParams();

        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    private $_hostInfo;
    private $_hostName;

    /**
	 * 返回当前http请求里的协议和主机信息。
     * Returns the schema and host part of the current request URL.
     *注意，返回的URL不包含末尾的斜杠。（比如http://www.cctv.com)
     * The returned URL does not have an ending slash.
     *默认情况下，该值基于用户的请求信息。返回`$_SERVER['HTTP_HOST']`。
     * By default this value is based on the user request information. This method will
	 如果它可用，而`$_SERVER['SERVER_NAME']`不可用
     * return the value of `$_SERVER['HTTP_HOST']` if it is available or `$_SERVER['SERVER_NAME']` if not.
	 详情，请查看php官方文档
     * You may want to check out the [PHP documentation](http://php.net/manual/en/reserved.variables.server.php)
     * for more information on these variables.
     *也可以明确地通过[[setHostInfo()|hostInfo]] 属性成员来指明该值
     * You may explicitly specify it by setting the [[setHostInfo()|hostInfo]] property.
     *
	 注意，由于依赖服务端配置，该信息是不可靠的（可能被用户发送的http头信息伪造）
     * > Warning: Dependent on the server configuration this information may not be
     * > reliable and [may be faked by the user sending the HTTP request](https://www.acunetix.com/vulnerabilities/web/host-header-attack).
	 如果服务端不依赖请求header而是配置成统一的名字，则此时该值`$_SERVER['HTTP_HOST']`是不可靠的
     * > If the webserver is configured to serve the same site independent of the value of
	 此时，要么修整服务端配置，要么明确地通过[[setHostInfo()|hostInfo]]来完成该值的主动设置
     * > the `Host` header, this value is not reliable. In such situations you should either
     * > fix your webserver configuration or explicitly set the value by setting the [[setHostInfo()|hostInfo]] property.
	 如果你没有权限修改服务端,还可以通过[[\yii\filters\HostControl]]过滤器，在应用级别上保护类似攻击
     * > If you don't have access to the server configuration, you can setup [[\yii\filters\HostControl]] filter at
     * > application level in order to protect against such kind of attack.
     *
     * @property string|null schema and hostname part (with port number if needed) of the request URL
     * (e.g. `http://www.yiiframework.com`), null if can't be obtained from `$_SERVER` and wasn't set.
     * See [[getHostInfo()]] for security related notes on this property.
     * @return string|null schema and hostname part (with port number if needed) of the request URL
     * (e.g. `http://www.yiiframework.com`), null if can't be obtained from `$_SERVER` and wasn't set.
     * @see setHostInfo()
     */
    public function getHostInfo()
    {
        if ($this->_hostInfo === null) {
            //安全连接，简单来说，就是https
            $secure = $this->getIsSecureConnection();
            $http = $secure ? 'https' : 'http';
            //还是从$_SERVER里读来。客户端的请求主机信息
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
            //或者，从服务端的主机信息读
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
                $port = $secure ? $this->getSecurePort() : $this->getPort();
                if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                    //拼接上端口
                    $this->_hostInfo .= ':' . $port;
                }
            }
        }

        return $this->_hostInfo;
    }

    /**
     * 设置模式和主机部分。这里的模式指的是http://或者https://
     * Sets the schema and host part of the application URL.
     * 设置器setHostInfo()方法，是用来设置的，当http://www.zzz.com在某个web服务器上不能决定的时候
	 * 一般是http请求绝对，特殊情况下才需要手动设置。
     * This setter is provided in case the schema and hostname cannot be determined
     * on certain Web servers.
     * @param string|null $value the schema and host part of the application URL. The trailing slashes will be removed.
     * @see getHostInfo() for security related notes on this property.
     */
    public function setHostInfo($value)
    {
        $this->_hostName = null;
        $this->_hostInfo = $value === null ? null : rtrim($value, '/');
    }

    /**
     * 返回请求URL的主机部分
     * Returns the host part of the current request URL.
     * Value is calculated from current [[getHostInfo()|hostInfo]] property.
     *该值可能是不可靠的（因为getHostInfo()方法）
     * > Warning: The content of this value may not be reliable, dependent on the server
     * > configuration. Please refer to [[getHostInfo()]] for more information.
     *
     * @return string|null hostname part of the request URL (e.g. `www.yiiframework.com`)
     * @see getHostInfo()
     * @since 2.0.10
     */
    public function getHostName()
    {
        if ($this->_hostName === null) {
			//内部使用自定义的方法getHostInfo(),然后再使用php原生函数parse_url来再次解析出HOST信息
            $this->_hostName = parse_url($this->getHostInfo(), PHP_URL_HOST);
        }

        return $this->_hostName;
    }

    private $_baseUrl;

    /**
     * 返回应用主体的相对URL
     * Returns the relative URL for the application.
     * 这个相对URL，不包含脚本文件名，且拖尾的斜杠是去掉的。在这里就是一个空字符串
     * This is similar to [[scriptUrl]] except that it does not include the script file name,
     * and the ending slashes are removed.
     * @return string the relative URL for the application
     * @see setScriptUrl()
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl === null) {
			//取目录，再去掉右边的斜杠"/",反斜杠"\"。基本就是个空字符串了。
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        //估计是""空字符串
        return $this->_baseUrl;
    }

    /**

     * Sets the relative URL for the application.
	 默认情况下，这个URL是基于入口脚本的
     * By default the URL is determined based on the entry script URL.
	 提供该方法的原因是因为，某些情况下开发人员可能想改变默认的行为
     * This setter is provided in case you want to change this behavior.
     * @param string $value the relative URL for the application
     */
    public function setBaseUrl($value)
    {
        $this->_baseUrl = $value;
    }

    private $_scriptUrl;

    /**
	* 返回入口脚本的相对URL。相对的主体是谁呢？是相对于document_root的路径
	* document_root是web服务器配置的，网站根目录。
	*所以入口脚本的相对url。可以是/test/index.php,或者/index.php这样的字符串
     * Returns the relative URL of the entry script.
	 * 该方法的实现，参考了zend框架里的Zend_Controller_Request_Http控制器
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string the relative URL of the entry script.
     * @throws InvalidConfigException if unable to determine the entry script URL
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
			//返回入口脚本的文件系统绝对路径
            $scriptFile = $this->getScriptFile();
			//返回文件名部分（也就是入口脚本文件名）
            $scriptName = basename($scriptFile);
			//是否是
			//相对网站根目录（document_root是网站根目录）的路径,比如/test/index.php
            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
			//与上述$_SERVER['SCRIPT_NAME']的结果相同，但是不清楚含义的区别
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && ($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
            } else {
                throw new InvalidConfigException('Unable to determine the entry script URL.');
            }
        }
		//基本上这个值就是/test/index.php这样或者是/index.php这样
        return $this->_scriptUrl;
    }

    /**
     * Sets the relative URL for the application entry script.
     * This setter is provided in case the entry script URL cannot be determined
     * on certain Web servers.
     * @param string $value the relative URL for the application entry script.
     */
    public function setScriptUrl($value)
    {
        $this->_scriptUrl = $value === null ? null : '/' . trim($value, '/');
    }

    private $_scriptFile;

    /**
	 * 返回入口脚本路径，注意是服务端文件系统的绝对路径
     * Returns the entry script file path.
	 *默认就是根据$_SERVER来实现的。测试时就是   "D:/wamp64/www/basic/web/index.php"
     * The default implementation will simply return `$_SERVER['SCRIPT_FILENAME']`.
     * @return string the entry script file path
     * @throws InvalidConfigException
     */
    public function getScriptFile()
    {
        if (isset($this->_scriptFile)) {
            return $this->_scriptFile;
		//在本地，这个变量的值是，D:/wamp64/www/test/index.php（入口脚本在文件系统里的绝对路径）
        } elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        } else {
            throw new InvalidConfigException('Unable to determine the entry script file path.');
        }
    }

    /**
	* 主动设置入口脚本路径（服务器所在操作系统的文件系统的绝对路径）
     * Sets the entry script file path.
	 * 正常情况下，入口脚本路径通过$_SERVER['SCRIPT_FILENAME']来获得
     * The entry script file path normally can be obtained from `$_SERVER['SCRIPT_FILENAME']`.
	 * 如果因为服务端的配置，导致未能正确返回该值，此时就可以手动配置这个属性
     * If your server configuration does not return the correct value, you may configure
     * this property to make it right.
     * @param string $value the entry script file path.
     */
    public function setScriptFile($value)
    {
        $this->_scriptFile = $value;
    }

    private $_pathInfo;

    /**
	 * 返回当前请求URL的path info 
     * Returns the path info of the currently requested URL.
	 * path info是一个URL里入口脚本之后，问号之前的部分。比如http://www.a.com/index.php/site/index?a=b&c=d里的site/index
     * A path info refers to the part that is after the entry script and before the question mark (query string).
	 * 开始的斜杠，结尾的斜杠都被去除了
     * The starting and ending slashes are both removed.
     * @return string part of the request URL that is after the entry script and before the question mark.
	 * 注意，返回的值，已经被URL-decoded。（确实，只要经过服务器这一关到PHP，都是url-decoded)
     * Note, the returned path info is already URL-decoded.
     * @throws InvalidConfigException if the path info cannot be determined due to unexpected server configuration
     */
    public function getPathInfo()
    {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }

        return $this->_pathInfo;
    }

    /**
	* 设置path info。仅供测试环境使用
     * Sets the path info of the current request.
     * This method is mainly provided for testing purpose.
     * @param string $value the path info of the current request
     */
    public function setPathInfo($value)
    {
        $this->_pathInfo = $value === null ? null : ltrim($value, '/');
    }

    /**
	* 解析当前请求URL的path info
     * Resolves the path info part of the currently requested URL.
     * A path info refers to the part that is after the entry script and before the question mark (query string).
     * The starting slashes are both removed (ending slashes will be kept).
     * @return string part of the request URL that is after the entry script and before the question mark.
     * Note, the returned path info is decoded.
     * @throws InvalidConfigException if the path info cannot be determined due to unexpected server configuration
     */
    protected function resolvePathInfo()
    {
		//获得原始url信息
        $pathInfo = $this->getUrl();
		//是否有问号
        if (($pos = strpos($pathInfo, '?')) !== false) {
		//取得问号之前的部分
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $pathInfo = urldecode($pathInfo);

		//这个处理是基于什么的呢？一下没有看明白。
		//不过倒是看到了php正则表达式的一些应用。比如修正符s是什么？影响点号（.）可以匹配任何字符。
		//修正符x是让#后的字符忽略，以及任何空白空格字符也都忽略。这其实就是所谓的：正则表达式注释。
        // try to encode in UTF8 if not so
        // http://w3.org/International/questions/qa-forms-utf-8.html
        if (!preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs', $pathInfo)
        ) {
		//注意，正则的开头是^,if的外部是!。其实是双重否定。所以，只能说上面每个[]里的都是各个编码里
		//特有的字符，只要匹配到那些字符，就说明是指定编码，而不能是utf-8编码了。这才把
		//不是utf-8编码的字符串统一转换成utf-8，并进行encode。
            $pathInfo = utf8_encode($pathInfo);
        }
		//scriptUrl一般是"/index.php"
        $scriptUrl = $this->getScriptUrl();
        $baseUrl = $this->getBaseUrl();
		//如果$pathInfo是以/index.php开始
        if (strpos($pathInfo, $scriptUrl) === 0) {
			//pathinfo应该是去掉/index.php的部分
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
		//这是什么情况？
        } elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
		//基本上这俩是相等的吧
        } elseif (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
            $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
        } else {
            throw new InvalidConfigException('Unable to determine the path info of the current request.');
        }
		//如果开始带有斜杠"/"，则去掉开始的斜杠
        if (substr($pathInfo, 0, 1) === '/') {
            $pathInfo = substr($pathInfo, 1);
        }

        return (string) $pathInfo;
    }

    /**
     * Returns the currently requested absolute URL.
	 * 快捷方式，hostInfo和url的拼接而已。
     * This is a shortcut to the concatenation of [[hostInfo]] and [[url]].
     * @return string the currently requested absolute URL.
     */
    public function getAbsoluteUrl()
    {
        return $this->getHostInfo() . $this->getUrl();
    }

    private $_url;

    /**
     * Returns the currently requested relative URL.
     * This refers to the portion of the URL that is after the [[hostInfo]] part.
     * It includes the [[queryString]] part if any.
     * @return string the currently requested relative URL. Note that the URI returned is URL-encoded.
     * @throws InvalidConfigException if the URL cannot be determined due to unusual server configuration
     */
    public function getUrl()
    {
        if ($this->_url === null) {
            $this->_url = $this->resolveRequestUri();
        }

        return $this->_url;
    }

    /**
     * Sets the currently requested relative URL.
     * The URI must refer to the portion that is after [[hostInfo]].
     * Note that the URI should be URL-encoded.
     * @param string $value the request URI to be set
     */
    public function setUrl($value)
    {
        $this->_url = $value;
    }

    /**
     * Resolves the request URI portion for the currently requested URL.
     * This refers to the portion that is after the [[hostInfo]] part. It includes the [[queryString]] part if any.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string|bool the request URI portion for the currently requested URL.
     * Note that the URI returned is URL-encoded.
     * @throws InvalidConfigException if the request URI cannot be determined due to unusual server configuration
     */
    protected function resolveRequestUri()
    {
		//IIS服务器？，真是少见
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
		//REQUEST_URI基本上也是相对于主机信息之后的全部部分（pathinfo,问号都包含）
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
				//https://xxxx的部分全部替换为空
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new InvalidConfigException('Unable to determine the request URI.');
        }

        return $requestUri;
    }

    /**
     * 从$_SERVER中读取，QUERY_STRING变量
     * Returns part of the request URL that is after the question mark.
     * @return string part of the request URL that is after the question mark
     */
    public function getQueryString()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * 是否通过安全通道（也就是https)
     * Return if the request is sent via secure channel (https).
     * @return bool if the request is sent via secure channel (https)
     */
    public function getIsSecureConnection()
    {
		//有https里吗？自己没有测试过，学习了。
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
            || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }

    /**
     * 服务端的服务器名，是从$_SERVER中来的SERVER_NAME变量
	 * 一般就是域名了，比如localhost,home.qianyun.me这样的
     * Returns the server name.
     * @return string server name, null if not available
     */
    public function getServerName()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
    }

    /**
     * 服务端的端口号，一般是80，或者443（https)
     * Returns the server port number.
     * @return int|null server port number, null if not available
     */
    public function getServerPort()
    {
        return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
    }

    /**
     * $_SERVER里的HTTP_REFERER，也就是所谓的上一个url
     * Returns the URL referrer.
     * @return string|null URL referrer, null if not available
     */
    public function getReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * $_SERVER里的用户代理，一般是浏览器的信息
     * Returns the user agent.
     * @return string|null user agent, null if not available
     */
    public function getUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    /**
     * 用户IP,还是从$_SERVER的REMOTE_ADDR来的
     * Returns the user IP address.
     * @return string|null user IP address, null if not available
     */
    public function getUserIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * 客户端发送http请求的主机，这个一般为空吧？反正本地测试是$_SERVER里没有看到
	 *不知使用公网访问时是否有值呢
     * Returns the user host name.
     * @return string|null user host name, null if not available
     */
    public function getUserHost()
    {
        return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
    }

    /**
     * 通过http认证信息发送的用户名，少见
     * @return string|null the username sent via HTTP authentication, null if the username is not given
     */
    public function getAuthUser()
    {
        return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
    }

    /**
     * 通过http认证信息发送的用户密码，少见
     * @return string|null the password sent via HTTP authentication, null if the password is not given
     */
    public function getAuthPassword()
    {
        return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
    }

    private $_port;

    /**
     * 获得端口
     * Returns the port to use for insecure requests.
     * Defaults to 80, or the port specified by the server if the current
     * request is insecure.
     * @return int port number for insecure requests.
     * @see setPort()
     */
    public function getPort()
    {
        if ($this->_port === null) {
            //这个代码有点晕。
            //是安全通道（https)的，就是80端口吗？不是吧。还是我的理解有误
			//根据php手册，逻辑运算符&&优先于?:。故下述等价于：
			//  ( !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) )? (int) $_SERVER['SERVER_PORT'] : 80;
			//整体来看是个?:
            $this->_port = !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
        }

        return $this->_port;
    }

    /**
	* 为非安全请求设置端口号
     * Sets the port to use for insecure requests.
	 * 该方法的提供是万一服务端使用必要的自定义端口时
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param int $value port number.
     */
    public function setPort($value)
    {
        if ($value != $this->_port) {
            $this->_port = (int) $value;
			//还把主机信息置空了
            $this->_hostInfo = null;
        }
    }

    private $_securePort;

    /**
     * Returns the port to use for secure requests.
     * https且指定了端口，就优先指定的，否则就是默认的443
     * Defaults to 443, or the port specified by the server if the current
     * request is secure.
     * @return int port number for secure requests.
     * @see setSecurePort()
     */
    public function getSecurePort()
    {
        if ($this->_securePort === null) {
			//注意，逻辑运算符&& 优先级高于三元?:
            $this->_securePort = $this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 443;
        }

        return $this->_securePort;
    }

    /**
	* 调整（设置）安全请求的端口
     * Sets the port to use for secure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param int $value port number.
     */
    public function setSecurePort($value)
    {
        if ($value != $this->_securePort) {
            $this->_securePort = (int) $value;
            $this->_hostInfo = null;
        }
    }

    private $_contentTypes;

    /**
     * 返回终端用户（浏览器）可以接收的内容类型
     * Returns the content types acceptable by the end user.
     * 如何知道终端用户可以接收的？当然是客户端发送的http请求里的ACCEPT字段来的
     * This is determined by the `Accept` HTTP header. For example,
     *
     * ```php
     * $_SERVER['HTTP_ACCEPT'] = 'text/plain; q=0.5, application/json; version=1.0, application/xml; version=2.0;';
     * $types = $request->getAcceptableContentTypes();
     * print_r($types);
     * // displays:
     * // [
     * //     'application/json' => ['q' => 1, 'version' => '1.0'],
     * //      'application/xml' => ['q' => 1, 'version' => '2.0'],
     * //           'text/plain' => ['q' => 0.5],
     * // ]
     * ```
     *
	 返回数组
     * @return array the content types ordered by the quality score. Types with the highest scores
     * will be returned first. The array keys are the content types, while the array values
     * are the corresponding quality score and other parameters as given in the header.
     */
    public function getAcceptableContentTypes()
    {
        if ($this->_contentTypes === null) {
			//HTTP_ACCEPT是以逗号分隔的字符串，表名客户端浏览器可接收的响应内容类型
            if (isset($_SERVER['HTTP_ACCEPT'])) {
                $this->_contentTypes = $this->parseAcceptHeader($_SERVER['HTTP_ACCEPT']);
            } else {
                $this->_contentTypes = [];
            }
        }

        return $this->_contentTypes;
    }

    /**
	* 手动设置（调整）_contentTypes，
     * Sets the acceptable content types.
	 * 看get方法可知它的具体格式
     * Please refer to [[getAcceptableContentTypes()]] on the format of the parameter.
     * @param array $value the content types that are acceptable by the end user. They should
     * be ordered by the preference level.
     * @see getAcceptableContentTypes()
     * @see parseAcceptHeader()
     */
    public function setAcceptableContentTypes($value)
    {
        $this->_contentTypes = $value;
    }

    /**
     * 返回http请求里的内容类型
     * Returns request content-type
	 * CONTENT_TYPE表名了数据的MIME类型（要发送给客户端的http响应体数据类型）
     * The Content-Type header field indicates the MIME type of the data
     * contained in [[getRawBody()]] or, in the case of the HEAD method, the
     * media type that would have been sent had the request been a GET.
     * For the MIME-types the user expects in response, see [[acceptableContentTypes]].
     * @return string request content-type. Null is returned if this information is not available.
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.17
     * HTTP 1.1 header field definitions
     */
    public function getContentType()
    {
		//CONTENT_TYPE一般不会出现在$_SERVER里吧？它一般在http响应的头部字段里
        if (isset($_SERVER['CONTENT_TYPE'])) {
            //优先从$_SERVER里读取，优先是CONTENT_TYPE变量
            return $_SERVER['CONTENT_TYPE'];
		//这个也少见
        } elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            //然后再次是HTTP_CONTENT_TYPE
            //fix bug https://bugs.php.net/bug.php?id=66606
            return $_SERVER['HTTP_CONTENT_TYPE'];
        }

        return null;
    }

    private $_languages;

    /**
     * Returns the languages acceptable by the end user.
     * This is determined by the `Accept-Language` HTTP header.
     * @return array the languages ordered by the preference level. The first element
     * represents the most preferred language.
     */
    public function getAcceptableLanguages()
    {
        if ($this->_languages === null) {
			//一般是逗号分隔的字符串，像"zh-CN,zh;q=0.8"这样的。
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $this->_languages = array_keys($this->parseAcceptHeader($_SERVER['HTTP_ACCEPT_LANGUAGE']));
            } else {
                $this->_languages = [];
            }
        }

        return $this->_languages;
    }

    /**
	* 手动设置客户端（终端用户）的语言。数组格式
     * @param array $value the languages that are acceptable by the end user. They should
     * be ordered by the preference level.
     */
    public function setAcceptableLanguages($value)
    {
        $this->_languages = $value;
    }

    /**
	 * 解析Accept或者Accept-Language头字段信息
     * Parses the given `Accept` (or `Accept-Language`) header.
     * 返回数组，每个元素都是带有q分数的一维数组
     * This method will return the acceptable values with their quality scores and the corresponding parameters
	 *数组的key是内容类型，而value又是一个数组，包含q和其他参数，q高的将优先返回
     * as specified in the given `Accept` header. The array keys of the return value are the acceptable values,
     * while the array values consisting of the corresponding quality scores and parameters. The acceptable
     * values with the highest quality scores will be returned first. For example,
     *
     * ```php
	 * 比如头信息如下：
     * $header = 'text/plain; q=0.5, application/json; version=1.0, application/xml; version=2.0;';
	 * 经过该方法解析后
     * $accepts = $request->parseAcceptHeader($header);
	 *我们打印一下返回的信息格式如下：
     * print_r($accepts);
     * // displays:
     * // [
     * //     'application/json' => ['q' => 1, 'version' => '1.0'],
     * //      'application/xml' => ['q' => 1, 'version' => '2.0'],
     * //           'text/plain' => ['q' => 0.5],
     * // ]
     * ```
     *
     * @param string $header the header to be parsed
     * @return array the acceptable values ordered by their quality score. The values with the highest scores
     * will be returned first.
     */
    public function parseAcceptHeader($header)
    {
        $accepts = [];
		//因为原始信息就是以逗号分隔
        foreach (explode(',', $header) as $i => $part) {
			//每个内容类型里都已分号(;)来给出q,所以再拆
			//不限制分隔后的子串(-1),且返回分隔后的非空部分
            $params = preg_split('/\s*;\s*/', trim($part), -1, PREG_SPLIT_NO_EMPTY);
            if (empty($params)) {
                continue;
            }
            $values = [
                'q' => [$i, array_shift($params), 1],
            ];
            foreach ($params as $param) {
                if (strpos($param, '=') !== false) {
                    list ($key, $value) = explode('=', $param, 2);
                    if ($key === 'q') {
                        $values['q'][2] = (double) $value;
                    } else {
                        $values[$key] = $value;
                    }
                } else {
                    $values[] = $param;
                }
            }
            $accepts[] = $values;
        }

		//使用php原生函数usort()来自定义排序，非常好
        usort($accepts, function ($a, $b) {
            $a = $a['q']; // index, name, q
            $b = $b['q'];
            if ($a[2] > $b[2]) {
                return -1;
            } elseif ($a[2] < $b[2]) {
                return 1;
            } elseif ($a[1] === $b[1]) {
                return $a[0] > $b[0] ? 1 : -1;
            } elseif ($a[1] === '*/*') {
                return 1;
            } elseif ($b[1] === '*/*') {
                return -1;
            } else {
                $wa = $a[1][strlen($a[1]) - 1] === '*';
                $wb = $b[1][strlen($b[1]) - 1] === '*';
                if ($wa xor $wb) {
                    return $wa ? 1 : -1;
                } else {
                    return $a[0] > $b[0] ? 1 : -1;
                }
            }
        });

        $result = [];
        foreach ($accepts as $accept) {
            $name = $accept['q'][1];
            $accept['q'] = $accept['q'][2];
            $result[$name] = $accept;
        }

        return $result;
    }

    /**
	* 返回用户意愿的语言，根据浏览器的头字段来的
     * Returns the user-preferred language that should be used by this application.
     * The language resolution is based on the user preferred languages and the languages
     * supported by the application. The method will try to find the best match.
     * @param array $languages a list of the languages supported by the application. If this is empty, the current
     * application language will be returned without further processing.
     * @return string the language that the application should use.
     */
    public function getPreferredLanguage(array $languages = [])
    {
        if (empty($languages)) {
            return Yii::$app->language;
        }
        foreach ($this->getAcceptableLanguages() as $acceptableLanguage) {
            $acceptableLanguage = str_replace('_', '-', strtolower($acceptableLanguage));
            foreach ($languages as $language) {
                $normalizedLanguage = str_replace('_', '-', strtolower($language));

                if ($normalizedLanguage === $acceptableLanguage || // en-us==en-us
                    strpos($acceptableLanguage, $normalizedLanguage . '-') === 0 || // en==en-us
                    strpos($normalizedLanguage, $acceptableLanguage . '-') === 0) { // en-us==en

                    return $language;
                }
            }
        }

        return reset($languages);
    }

    /**
	* 一种缓存方式，Etags
     * Gets the Etags.
     *
     * @return array The entity tags
     */
    public function getETags()
    {
		//一般在服务端使用缓存时，才有这个头字段吧
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return preg_split('/[\s,]+/', str_replace('-gzip', '', $_SERVER['HTTP_IF_NONE_MATCH']), -1, PREG_SPLIT_NO_EMPTY);
        } else {
            return [];
        }
    }

    /**
     * 返回cookie集合对象
     * Returns the cookie collection.
     * request组件中的cookie集合对象，代表了http请求中,在header部分cookie字段的内容
     * Through the returned cookie collection, you may access a cookie using the following syntax:
     *
     * ```php
     * $cookie = $request->cookies['name']
     * if ($cookie !== null) {
     *     $value = $cookie->value;
     * }
     *
     * // alternatively
     * $value = $request->cookies->getValue('name');
     * ```
     *
     * @return CookieCollection the cookie collection.
     */
    public function getCookies()
    {
        if ($this->_cookies === null) {
            //看到没有，当request实例化cookie集合类时，是只读的。那response组件呢？
            //在loadCookies()方法里，已经解密了cookie的前缀hash
            $this->_cookies = new CookieCollection($this->loadCookies(), [
                'readOnly' => true,
            ]);
        }

        return $this->_cookies;
    }

    /**
     * 客户端传来的cookie，都存在PHP超全局数组$_COOKIE
     * Converts `$_COOKIE` into an array of [[Cookie]].
     * 我们就用$_COOKIE作为cookie集合对象的cookie来源
     * response中如何加密cookie，这里request就如何对应地解密cookie，两者操作cookie是一致的。
     * response 设置cookie时要加密；request 接收cookie时就要解密
     * @return array the cookies obtained from request
     * @throws InvalidConfigException if [[cookieValidationKey]] is not set when [[enableCookieValidation]] is true
     */
    protected function loadCookies()
    {
        $cookies = [];
        if ($this->enableCookieValidation) {
            if ($this->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($this) . '::cookieValidationKey must be configured with a secret key.');
            }
            foreach ($_COOKIE as $name => $value) {
                if (!is_string($value)) {
                    continue;
                }
                //使用security组件验证每个cookie值是否被篡改过，返回去掉hash前缀的原始数据
                //（原理是找到原始信息再用同样的算法,同样的密钥生成一次，比较两次生成的字符串是否一样）
                $data = Yii::$app->getSecurity()->validateData($value, $this->cookieValidationKey);
                if ($data === false) {
                    continue;
                }
                //反序列化原始数据
                $data = @unserialize($data);
                if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $name) {
                    $cookies[$name] = new Cookie([
                        'name' => $name,
                        'value' => $data[1],
                        'expire' => null,
                    ]);
                }
            }
        //没有开启cookie防篡改机制时
        } else {
            foreach ($_COOKIE as $name => $value) {
                $cookies[$name] = new Cookie([
                    'name' => $name,
                    'value' => $value,
                    'expire' => null,
                ]);
            }
        }

        return $cookies;
    }

    private $_csrfToken;

    /**
	* 返回令牌（生成令牌），用于后续的CSRF验证
     * Returns the token used to perform CSRF validation.
     * 该令牌的生成比较安全。
     * This token is generated in a way to prevent [BREACH attacks](http://breachattack.com/). It may be passed
	 令牌从客户端传递而来有两种：通过隐藏表单域，或者http请求的头字段里
     * along via a hidden field of an HTML form or an HTTP header value to support CSRF validation.
     * @param bool $regenerate whether to regenerate CSRF token. When this parameter is true, each time
     * this method is called, a new CSRF token will be generated and persisted (in session or cookie).
     * @return string the token used to perform CSRF validation.
     */
    public function getCsrfToken($regenerate = false)
    {
        if ($this->_csrfToken === null || $regenerate) {
            if ($regenerate || ($token = $this->loadCsrfToken()) === null) {
                $token = $this->generateCsrfToken();
            }
            // the mask doesn't need to be very random
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-.';
            $mask = substr(str_shuffle(str_repeat($chars, 5)), 0, static::CSRF_MASK_LENGTH);
            // The + sign may be decoded as blank space later, which will fail the validation
            $this->_csrfToken = str_replace('+', '.', base64_encode($mask . $this->xorTokens($token, $mask)));
        }

        return $this->_csrfToken;
    }

    /**
	* 从cookie或者session里加载csrf令牌,也就是加载服务端的csrf令牌
     * Loads the CSRF token from cookie or session.
     * @return string the CSRF token loaded from cookie or session. Null is returned if the cookie or session
     * does not have CSRF token.
     */
    protected function loadCsrfToken()
    {
        if ($this->enableCsrfCookie) {
            return $this->getCookies()->getValue($this->csrfParam);
        } else {
            return Yii::$app->getSession()->get($this->csrfParam);
        }
    }

    /**
	* 生成Csrf令牌，一个随机的字符串而已
	* 两种方式（cookie,session)
     * Generates  an unmasked random token used to perform CSRF validation.
     * @return string the random token for CSRF validation.
     */
    protected function generateCsrfToken()
    {
		//使用安全组件了
        $token = Yii::$app->getSecurity()->generateRandomString();
        if ($this->enableCsrfCookie) {
            $cookie = $this->createCsrfCookie($token);
            Yii::$app->getResponse()->getCookies()->add($cookie);
        } else {
            Yii::$app->getSession()->set($this->csrfParam, $token);
        }
        return $token;
    }

    /**
     * 返回两个字符串的亦或结果
     * Returns the XOR result of two strings.
     * 如果两个字符串的长度不等，那么短字符串将填充，填充到和长字符串一样的长度
	 * 有点不明白
     * If the two strings are of different lengths, the shorter one will be padded to the length of the longer one.
     * @param string $token1
     * @param string $token2
     * @return string the XOR result
     */
    private function xorTokens($token1, $token2)
    {
        $n1 = StringHelper::byteLength($token1);
        $n2 = StringHelper::byteLength($token2);
        if ($n1 > $n2) {
            //str_pad我得查看php官网。是如何截取$token2填充到$token1上的。
            $token2 = str_pad($token2, $n1, $token2);
        } elseif ($n1 < $n2) {
            $token1 = str_pad($token1, $n2, $n1 === 0 ? ' ' : $token1);
        }

        return $token1 ^ $token2;
    }

    /**
	* 通过http请求头里获得客户端的csrf令牌
     * @return string the CSRF token sent via [[CSRF_HEADER]] by browser. Null is returned if no such header is sent.
     */
    public function getCsrfTokenFromHeader()
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper(static::CSRF_HEADER));
        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }

    /**
	* cookie方式生成CSRF令牌信息
     * Creates a cookie with a randomly generated CSRF token.
	 由csrfCookie指定的随机初始化值将会使用来生成令牌
     * Initial values specified in [[csrfCookie]] will be applied to the generated cookie.
     * @param string $token the CSRF token
     * @return Cookie the generated cookie
     * @see enableCsrfValidation
     */
    protected function createCsrfCookie($token)
    {
        $options = $this->csrfCookie;
        $options['name'] = $this->csrfParam;
        $options['value'] = $token;
        return new Cookie($options);
    }

    /**
     * Performs the CSRF validation.
     *  验证，验证服务端存储的csrf(cookie或者session里）和客户端给出的csrf是否一致
     * This method will validate the user-provided CSRF token by comparing it with the one stored in cookie or session.
	 * 该方法一般在[[Controller::beforeAction()]]级别的beforeAction里调用
     * This method is mainly called in [[Controller::beforeAction()]].
     * 注意，当未开启，或者请求方法是GET,HEAD,OPTIONS都不会验证csrf
     * Note that the method will NOT perform CSRF validation if [[enableCsrfValidation]] is false or the HTTP method
     * is among GET, HEAD or OPTIONS.
     *
	 * $token提供，或者从POST域，或者http请求头字段里
     * @param string $token the user-provided CSRF token to be validated. If null, the token will be retrieved from
     * the [[csrfParam]] POST field or HTTP header.
	 * 从2.0.4里有这个参数的
     * This parameter is available since version 2.0.4.
	 如果csrf功能是关闭的，将总是返回true
     * @return bool whether CSRF token is valid. If [[enableCsrfValidation]] is false, this method will return true.
     */
    public function validateCsrfToken($token = null)
    {
        $method = $this->getMethod();
        // only validate CSRF token on non-"safe" methods http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.1.1
        if (!$this->enableCsrfValidation || in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $trueToken = $this->loadCsrfToken();

        if ($token !== null) {
            return $this->validateCsrfTokenInternal($token, $trueToken);
        } else {
            return $this->validateCsrfTokenInternal($this->getBodyParam($this->csrfParam), $trueToken)
                || $this->validateCsrfTokenInternal($this->getCsrfTokenFromHeader(), $trueToken);
        }
    }

    /**
	* 执行csrf令牌的验证
     * Validates CSRF token
     *
     * @param string $token
     * @param string $trueToken
     * @return bool
     */
    private function validateCsrfTokenInternal($token, $trueToken)
    {
        if (!is_string($token)) {
            return false;
        }

        $token = base64_decode(str_replace('.', '+', $token));
		//用助手函数计算长度
        $n = StringHelper::byteLength($token);
		//长度对不对
        if ($n <= static::CSRF_MASK_LENGTH) {
            return false;
        }
        $mask = StringHelper::byteSubstr($token, 0, static::CSRF_MASK_LENGTH);
        $token = StringHelper::byteSubstr($token, static::CSRF_MASK_LENGTH, $n - static::CSRF_MASK_LENGTH);
        $token = $this->xorTokens($mask, $token);

        return $token === $trueToken;
    }
}
