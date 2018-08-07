<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * Web Response代表了http的响应
 * The web Response class represents an HTTP response
 * 包括发送给客户端的 【headers】【cookies】【content】三项
 * It holds the [[headers]], [[cookies]] and [[content]] that is to be sent to the client.
 * 还可以控制响应码
 * It also controls the HTTP [[statusCode|status code]].
 * 在[[\yii\web\Application]]中，Response默认配置为应用的组件
 * Response is configured as an application component in [[\yii\web\Application]] by default.
 * 可以通过 Yii::$app->response来访问
 * You can access that instance via `Yii::$app->response`.
 * 默认是核心组件，可以更新response的配置，在`conponents`组件，看下面的例子
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 * 网页应用开发的最终目的本质上就是根据不同的请求构建这些响应对象
 * ```php
 * 'response' => [
		//响应data的编码格式
 *     'format' => yii\web\Response::FORMAT_JSON,
		//响应header中的字符集
 *     'charset' => 'UTF-8',
 *     // ...
 * ]
 * ```
 * 详情和如何使用Response,先到官网查看(guide:runtime-responses).
 * For more details and usage information on Response, see the [guide article on responses](guide:runtime-responses).
 *  CookieCollection  集合类，cookie，只读
 * @property CookieCollection $cookies The cookie collection. This property is read-only.
 *  字符串，$downloadHeaders  下载文件时的attachment部分的文件名，该属性只写
 * @property string $downloadHeaders The attachment file name. This property is write-only.
 *  headerCollection $headers  是header集合,该属性是只读的
 * @property HeaderCollection $headers The header collection. This property is read-only.
 * $isClientError  是否这response是要报告客户端的错误，该属性只读。
 * @property bool $isClientError Whether this response indicates a client error. This property is read-only.
 * $isEmpty 是否这个response是空的，该属性只读
 * @property bool $isEmpty Whether this response is empty. This property is read-only.
 * $isForbidden 表名当前的response是报告客户端403的  ，该属性只读
 * @property bool $isForbidden Whether this response indicates the current request is forbidden. This property
 * is read-only.
 * $isInformational 表名当前的response是informational，该属性只读。不明白？什么“response is informational"
 * @property bool $isInformational Whether this response is informational. This property is read-only.
 *  $isInvalid 当前response是否有有效的状态码，该属性只读
 * @property bool $isInvalid Whether this response has a valid [[statusCode]]. This property is read-only.
 * $isNotFound 是否表名当前请求的资源未找到，（就是404）
 * @property bool $isNotFound Whether this response indicates the currently requested resource is not found.
 * This property is read-only.
 * @property bool $isOk Whether this response is OK. This property is read-only.
 *  是否当前的response是一个重定向，该属性只读（就是302,301）这样的
 * @property bool $isRedirection Whether this response is a redirection. This property is read-only.
 * 是否服务端错误
 * @property bool $isServerError Whether this response indicates a server error. This property is read-only.
 * 是否是成功的
 * @property bool $isSuccessful Whether this response is successful. This property is read-only.
 * 响应状态码
 * @property int $statusCode The HTTP status code to send with the response.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Response extends \yii\base\Response
{
    /**
     * 定义了一个事件，在send()方法的开始触发
     * @event ResponseEvent an event that is triggered at the beginning of [[send()]].
     */
    const EVENT_BEFORE_SEND = 'beforeSend';
    /**还是事件，在send()方法的最后触发
     * @event ResponseEvent an event that is triggered at the end of [[send()]].
     */
    const EVENT_AFTER_SEND = 'afterSend';
    /**
     * 在prepare()之后触发这个事件，prepare()在send()中调用
     * @event ResponseEvent an event that is triggered right after [[prepare()]] is called in [[send()]].
     * 我们可以响应这个事件，在http响应发送到客户端的最终关头来过滤下http响应的内容
     * You may respond to this event to filter the response content before it is sent to the client.
     */
    const EVENT_AFTER_PREPARE = 'afterPrepare';
    const FORMAT_RAW = 'raw';
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_JSONP = 'jsonp';
    const FORMAT_XML = 'xml';

    /**
     *  $format 响应的格式，定义如何转化data为content
     * @var string the response format. This determines how to convert [[data]] into [[content]]
     * 该值后期如果没有设置，那么它必须是[[formatters]]数组里的keys之一
     * when the latter is not set. The value of this property must be one of the keys declared in the [[formatters]] array.
     * By default, the following formats are supported:
     * 
     *      表名无需转换content,也无需添加额外的HTTP的header
     * - [[FORMAT_RAW]]: the data will be treated as the response content without any conversion.
     *   No extra HTTP header will be added.
     *   无需转换content,只添加Content-Type头信息"text/html"
     * - [[FORMAT_HTML]]: the data will be treated as the response content without any conversion.
     *   The "Content-Type" header will set as "text/html".
     *   转换content为JSON格式的，添加"Content-Type"头信息的值是“application/json"
     * - [[FORMAT_JSON]]: the data will be converted into JSON format, and the "Content-Type"
     *   header will be set as "application/json".
     *   转换content为JSONP格式，"Content-Type"头信息的值是“text/javascript"
     * - [[FORMAT_JSONP]]: the data will be converted into JSONP format, and the "Content-Type"
     *   这种格式$data必须是一个数组，有data和callback两个元素
     *   header will be set as "text/javascript". Note that in this case `$data` must be an array
     *   data元素代表实际要发送的内容，后者callback则是JavaScript的回调函数名
     *   with "data" and "callback" elements. The former refers to the actual data to be sent,
     *   while the latter refers to the name of the JavaScript callback.
     *    转换content为XML格式，请去[[XmlResponseFormatter]]查看详情
     * - [[FORMAT_XML]]: the data will be converted into XML format. Please refer to [[XmlResponseFormatter]]
     *   for more details.
     *  最后，你还可以自定义格式化的过程，或者通过配置[[formatters]]数组添加额外的格式
     * You may customize the formatting process or support additional formats by configuring [[formatters]].
     * @see formatters
     */
    public $format = self::FORMAT_HTML;
    /**
     * MIME类型  ，来源于http请求的ACCEPT头信息，是表名客户端浏览器支持的类型，Yii框架据此来发送http响应
     * 可见响应内容的类型可不是瞎设置的呀。
     * @var string the MIME type (e.g. `application/json`) from the request ACCEPT header chosen for this response.
     * 该属性由[[\yii\filters\ContentNegotiator]].内容协商过滤器维护
     * This property is mainly set by [[\yii\filters\ContentNegotiator]].
     */
    public $acceptMimeType;
    /**
     * ['q' => 1, 'version' => '1.0']这样的数组，有关上一个属性$acceptMimeType的一列表键值对
     * @var array the parameters (e.g. `['q' => 1, 'version' => '1.0']`) associated with the [[acceptMimeType|chosen MIME type]].
     * 。
     * This is a list of name-value pairs associated with [[acceptMimeType]] from the ACCEPT HTTP header.
     * 该属性由[[\yii\filters\ContentNegotiator]].内容协商过滤器维护
     * This property is mainly set by [[\yii\filters\ContentNegotiator]].
     */
    public $acceptParams = [];
    /**
     * 数组，由[format]参数指定的，把content转换格式的配置数组
     * @var array the formatters for converting data into the response content of the specified [[format]].
     * 数组是key就是格式名，数组的值则是对应负责的格式化对象
     * The array keys are the format names, and the array values are the corresponding configurations
     * for creating the formatter objects.
     * @see format
     * @see defaultFormatters
     */
    public $formatters = [];
    /**
     * 该$data就是原始的response数据，该属性不空时则根据[[format]]属性，在响应处理时把$data转换为[[content]]
     * @var mixed the original response data. When this is not null, it will be converted into [[content]]
     * according to [[format]] when the response is being sent out.
     * @see content
     */
    public $data;
    /**
     * @var string the response content. When [[data]] is not null, it will be converted into [[content]]
     * according to [[format]] when the response is being sent out.
     * @see data
     */
    public $content;
    /** 
     * 资源和数组类型，表示要发送的流。可以是流处理器或者可配置的流处理器对象数组
     * @var resource|array the stream to be sent. This can be a stream handle or an array of stream handle,
     * 当这个属性被设置后，[[data]]和[[content]]属性将会被忽略
     * the begin position and the end position. Note that when this property is set, the [[data]] and [[content]]
     * properties will be ignored by [[send()]].
     */
    public $stream;
    /**
     * http响应的字符集
     * @var string the charset of the text response. If not set, it will use
     * the value of [[Application::charset]].
     */
    public $charset;
    /**
     * 状态短语
     * @var string the HTTP status description that comes together with the status code.
     * @see httpStatuses
     */
    public $statusText = 'OK';
    /**
     * http协议的版本。默认是1.1。也可以通过$_SERVER['SERVER_PROTOCOL']来指定
     * @var string the version of the HTTP protocol to use. If not set, it will be determined via `$_SERVER['SERVER_PROTOCOL']`,
     * or '1.1' if that is not available.
     */
    public $version;
    /**
     * 布尔值，表名http响应是否真的发送过了，如果是true，则调用send()方法啥也不做
     * （因为http响应处理的过程非常复杂且漫长）
     * @var bool whether the response has been sent. If this is true, calling [[send()]] will do nothing.
     */
    public $isSent = false;
    /**
     * 数组列表，http响应的状态码和短语的对应关系
     * 复习一下，很好，数了一下 66个
     * @var array list of HTTP status codes and the corresponding texts
     */
    public static $httpStatuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * http状态码
     * @var int the HTTP status code to send with the response.
     */
    private $_statusCode = 200;
    /**
     * 存储处理response过程中涉及的http响应头信息，待最终向客户端发送响应时才交由header()函数处理
     * @var HeaderCollection
     */
    private $_headers;


    /**
     * Initializes this component.
     */
    public function init()
    {
		//基本上 http版本都是1.1了，所以，为了些许的性能考虑，觉得初始化配置response组件时设置为"HTTP/1.1"合适
        if ($this->version === null) {
            if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
                $this->version = '1.0';
            } else {
                $this->version = '1.1';
            }
        }
		//如果response组件没有设置的话，可以使用它上一级web应用的charset。即$app,通过查看yii\base\Application知道，它的charset=UTF-8
        if ($this->charset === null) {
            $this->charset = Yii::$app->charset;
        }
		//初始化四个响应格式，这四种格式是写死的，我们目前无需扩展。
        $this->formatters = array_merge($this->defaultFormatters(), $this->formatters);
    }

    /**
	 * 返回http状态码，可以把它组装到响应中
     * @return int the HTTP status code to send with the response.
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
	 * 设置http响应码及短语
     * Sets the response status code.
	 * 如果$text是null的话，该方法将会根据$httpStatuses静态成员数组设置对应的短语(statusText)
     * This method will set the corresponding status text if `$text` is null.
     * @param int $value the status code
     * @param string $text the status text. If not set, it will be set automatically based on the status code.
     * @throws InvalidParamException if the status code is invalid.
     */
    public function setStatusCode($value, $text = null)
    {
		//状态码为null，则直接认为是200
        if ($value === null) {
            $value = 200;
        }
		//先设置状态码
        $this->_statusCode = (int) $value;
		//然后判断状态码是否有效（是否在[100,600]区间）
        if ($this->getIsInvalid()) {
            throw new InvalidParamException("The HTTP status code is invalid: $value");
        }
		//短语为null,则根据状态码来设置短语
        if ($text === null) {
            $this->statusText = isset(static::$httpStatuses[$this->_statusCode]) ? static::$httpStatuses[$this->_statusCode] : '';
        } else {
            $this->statusText = $text;
        }
    }

    /**
	 * 返回http响应的头部集合
     * Returns the header collection.
	 * 头部集合是一个类，包含了当前已注册（添加）的http头部
     * The header collection contains the currently registered HTTP headers.
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
			//头部集合单独使用一个类来管理，该类维护了一个二维数组来完成头部集合的管理。
            $this->_headers = new HeaderCollection;
        }
        return $this->_headers;
    }

    /**
	 * 把http响应发送给客户端
	 * 关键就是那两个方法
			sendHeaders()
			sendContent()
     * Sends the response to the client.
     */
    public function send()
    {
		//不会重复发送
        if ($this->isSent) {
            return;
        }
		//send方法的开始，先触发before_send事件
        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
		//触发after_prepare事件
        $this->trigger(self::EVENT_AFTER_PREPARE);
		//根据http协议的规范，肯定是先发送响应头部，再发送响应实体内容
        $this->sendHeaders();
        $this->sendContent();
		//send方法的结尾，触发after_send事件
        $this->trigger(self::EVENT_AFTER_SEND);
		//置为已发送，这是后续其他处理判断的依据
        $this->isSent = true;
    }

    /**
	 * 清空 响应头，响应cookie,响应实体,状态码。
	 * 问：什么情况下需要调用这个方法呢？
     * Clears the headers, cookies, content, status code of the response.
     */
    public function clear()
    {
        $this->_headers = null;
        $this->_cookies = null;
        $this->_statusCode = 200;
        $this->statusText = 'OK';
        $this->data = null;
        $this->stream = null;
        $this->content = null;
        $this->isSent = false;
    }

    /**
	 *  单独发送响应头部，如何发送？其实就是php的原生函数header了。
	 * 内部还调用了sendCookies()方法，因为响应cookie在http响应头中比较特殊，故单独写个方法，可见其重视cookie。
     * Sends the response headers to the client
     */
    protected function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }
		//当有要发送的头部时
        if ($this->_headers) {
			//获得响应头集合对象
            $headers = $this->getHeaders();
			//遍历之，注意$headers是二维数组
            foreach ($headers as $name => $values) {
				//替换成空格，然后首字母都大写，再替换回连字符。仅仅为了两个单词的首字母大写
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                $replace = true;
				//既然是二维数组，这里当然还能再次遍历。一般第二维的数组，都是只有一个元素。
                foreach ($values as $value) {
					//复习一下php的header函数，最多可以传递三个参数呢，第二个参数默认是true,表示覆盖之前重复发送的头信息。
                    header("$name: $value", $replace);
					//这里发送一个后，就置为false,使得同一个头部字段里的值（某二维数组有多个元素），都以第一个元素值为准。
                    $replace = false;
                }
            }
        }
		//再最后设置http响应的状态行。（有时所谓的响应头部，也包含状态行）
		//格式为“HTTP/1.1 200  OK这样的。
        $statusCode = $this->getStatusCode();
        header("HTTP/{$this->version} {$statusCode} {$this->statusText}");
		//还要发送cookie,这个重要性也非常高，当然得单独写个方法了。
        $this->sendCookies();
		//好了，至此http响应的头部部分就做完了。
    }

    /**
     * response组件发送cookie给客户端（使用php原生setcookie()函数）
     * 发送之前肯定早已准备好要种植给客户端的cookie了，都存在response组件的_cookies属性中
     * 为了安全考虑，防止cookie在客户端被篡改，还使用了secure组件给cookie值进行hash散射
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        //没有要种植的cookie，那就算了
        if ($this->_cookies === null) {
            return;
        }
		//种植cookie和服务端接收cookie来处理是一致的，不能种植cookie时不加密，但是服务端接收处理时解密，这就不一致了。
		//所以，需要request组件的enableCookieValidation属性判断，进而判断服务端种植cookie时是否需要加密。
        $request = Yii::$app->getRequest();
		//Request组件的这个属性默认是true,即客户端会解密cookie,那服务端种植是得配合，加密cookie（Validation应理解为加解密）。
        if ($request->enableCookieValidation) {
			//加密时需要一个随机固定的字符串（盐），没有就报异常，说明这个盐非常重要！
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        //cookie集合对象可以被遍历，是因为这个对象实现了PHP原生接口IteratorAggregate
		//复习一下，我们知道，遍历出的$cookie其实是一个web\cookie对象。
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            //不过期(服务端如果想让客户端的cookie过期，不可能直接去客户端删除，而是更新它的过期值为1,当然其他历史的时间值都行，客户端会认为该cookie过期而删除。1是Yii框架特意取的而已）。
			//且配置了认证key，则对发送到客户端的cookie进行加密
            if ($cookie->expire != 1  && isset($validationKey)) {
                //使用Security组件为每个cookie值进行加密，加密参数$validationKey由$request组件自行提供
                //Security组件在哪配置的呢？在Application的核心组件里就有
                //cookie的hash算法其实是防篡改，意味着你可以截获看到，但你不能更改。
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            //最终再调用php的函数setcookie来设置cookie
            setcookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
	 * 发送响应内容给客户端
     * Sends the response content to the client
     */
    protected function sendContent()
    {
		//流数据为空，则直接使用echo 输出content就行，简单干脆！
        if ($this->stream === null) {
            echo $this->content;
            return;
        }
		//使用流数据时，就无需data和content了，直接从流数据资源里读取
		//涉及了一些指针的操作（指针移动，剩余流数据大小，循环分批下载）
		//重新归零脚本运行的超时时间，与php.ini里的max_execution_time对应。
		//这里是动态地使用php函数设置，0表示不限制超时时间。
        set_time_limit(0); // Reset time limit for big files
		//定义流数据的分块大小
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list ($handle, $begin, $end) = $this->stream;
			//在$handle关联的资源中，定位指针位置为$begin
            fseek($handle, $begin);
			//尚未到达文件流数据的末尾，并且，此时指针的位置还未到$end。
			//（ftell是读取当前资源的文件指针位置，fseek是设置(移动）当前指针的位置）
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
				//如果剩余的流数据不够分块大小
                if ($pos + $chunkSize > $end) {
					//直接读取剩余部分就是。
                    $chunkSize = $end - $pos + 1;
                }
				//按照此时$chunkSize指定的数据量，读取流数据，并输出。也就是说，本次输出不是我们自己用content，而是从某个文件中读取内容
				//作为http响应内容发送给客户端
				//默认的8M已经不小了，故web服务器应该不会缓存，而是直接转给浏览器客户端的
                echo fread($handle, $chunkSize);
				//立即释放PHP脚本所有的输出缓存给Web服务器,注意，是释放php级别的缓存，据说可以释放php中【输出缓存机制】占据的一部分内存。
				//但理解尚不深。
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
			//最终关闭资源
            fclose($handle);
		//如果不是数组的话
        } else {
			//则steam属性存储的，直接就是资源了，不用那么多的判断了，只要不到流数据的结尾，就按照$chunkSize大小的读取量，
			//循环读取出来，并立即echo之。
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
            }
			//最终也关闭流资源，好习惯！
            fclose($this->stream);
        }
    }

    /**
	 * 给浏览器发送一个文件（就是所谓的浏览器端的下载？）
     * Sends a file to the browser.
     *
	 * 这个方法只是在为发送文件做准备，直到暗中或明面地调用send()方法的时候才真正发送文件
     * Note that this method only prepares the response for file sending. The file is not sent
	 * 后者（暗中地）一般是从控制器的action中返回后，yii框架最终都会调用send。（当然也可以明确地直接调用send()）
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
	 * 来，下面给个例子，看看如何从一个action中发送文件。该例子是根据文件名，从指定文件系统目录(web不可直接访问）里读取文件内容
     * The following is an example implementation of a controller action that allows requesting files from a directory
     * that is not accessible from web:
     *
     * ```php
     * public function actionFile($filename)
     * {
     *     $storagePath = Yii::getAlias('@app/files');
     *
     *     // check filename for allowed chars (do not allow ../ to avoid security issue: downloading arbitrary files)
     *     if (!preg_match('/^[a-z0-9]+\.[a-z0-9]+$/i', $filename) || !is_file("$storagePath/$filename")) {
     *         throw new \yii\web\NotFoundHttpException('The file does not exists.');
     *     }
	 *										//第一个参数是服务端文件系统的路径，第二个参数是展示给浏览器端的文件名。两者有可能相同
     *     return Yii::$app->response->sendFile("$storagePath/$filename", $filename);
     * }
     * ```
     * $filePath  是文件系统的绝对路径，从该路径下寻找要发送的文件
     * @param string $filePath the path of the file to be sent.
	 * $attachmentName   展示给浏览器的文件名（也许在服务端文件系统里存在的文件名与此不同，也许相同），如果不给出这个名字，则由$filePath决定。
     * @param string $attachmentName the file name shown to the user. If null, it will be determined from `$filePath`.
	 * $options  发送文件时额外的选项，支持的选项有：
     * @param array $options additional options for sending the file. The following options are supported:
     * mimeType http响应的MIME类型，如果没有指明，则根据$filePath猜测
     *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
	 * inline  布尔值，浏览器是否应该在浏览器窗口打开这个文件，默认是false，
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
	 *	也就是说，浏览器应该会弹出一个下载文件的对话框。
     *    meaning a download dialog will pop up.
     *
     * @return $this the response object itself
     * @see sendContentAsFile()  参考这些方法
     * @see sendStreamAsFile()
     * @see xSendFile()
     */
    public function sendFile($filePath, $attachmentName = null, $options = [])
    {
		//mimeType信息的判断
        if (!isset($options['mimeType'])) {
			//根据文件的扩展名（pathinfo()函数获得），Yii框架本身维护了一个Content-Type的数据字典在
			//yii/helpers/mimeTypes.php文件里。这是一个文件扩展名和MIME的映射大数组（大约1000个元素）
            $options['mimeType'] = FileHelper::getMimeTypeByExtension($filePath);
        }
		//展示给浏览器端的文件名的判断
        if ($attachmentName === null) {
            $attachmentName = basename($filePath);
        }
		//只读方式打开一个文件，第二个字符b是以二进制模式读取文件的内容，不改变原文件里的行结束符。根据php手册的解释，
		//该字符b仅仅影响文件的行结束符（windows的\r\n,Unix的\n,还是Mac的\r这三种）。我们有疑问了，读取文件时还会破坏文件吗？
		//是的，因为每个程序读取文件的方式不同，有的就有可能造成对原始二进制流的破坏，这里open文件时，加上b参数，就是说php的这些函数可以
		//安全无破坏地读取$filePath文件流。这也是php手册的强烈建议。
        $handle = fopen($filePath, 'rb');
		//发送给这个方法去处理（涉及文件大小，分批下载，有关下载的几个http响应头设置）
        $this->sendStreamAsFile($handle, $attachmentName, $options);

        return $this;
    }

    /**
	 * 把指定的内容作为文件发送给客户端
     * Sends the specified content as a file to the browser.
     * 这个方法只是在为发送文件做准备。后续的send()方法才是真正发送文件给客户端
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     * $content是要发送的内容，而存在的成员属性[[content]]将会丢弃
     * @param string $content the content to be sent. The existing [[content]] will be discarded.
     * @param string $attachmentName the file name shown to the user.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *
     * @return $this the response object itself
     * @throws RangeNotSatisfiableHttpException if the requested range is not satisfiable
     * @see sendFile() for an example implementation.
     */
    public function sendContentAsFile($content, $attachmentName, $options = [])
    {
        $headers = $this->getHeaders();
		//使用工具类StringHelper来查询$content这个字符串的字节长度，其实就是mb_strlen($content, '8bit');而已。
        $contentLength = StringHelper::byteLength($content);
		//确定本次要发送给客户端的内容范围
        $range = $this->getHttpRange($contentLength);

        if ($range === false) {
            $headers->set('Content-Range', "bytes */$contentLength");
            throw new RangeNotSatisfiableHttpException();
        }

        list($begin, $end) = $range;
        if ($begin != 0 || $end != $contentLength - 1) {
            $this->setStatusCode(206);
            $headers->set('Content-Range', "bytes $begin-$end/$contentLength");
            $this->content = StringHelper::byteSubstr($content, $begin, $end - $begin + 1);
        } else {
            $this->setStatusCode(200);
			//注意，这里与sendStreamAsFile()方法的不同是，直接使用$content覆盖了原始的content成员。
            $this->content = $content;
        }
		//同样需要设置下载相关的几个http头字段
        $mimeType = isset($options['mimeType']) ? $options['mimeType'] : 'application/octet-stream';
        $this->setDownloadHeaders($attachmentName, $mimeType, !empty($options['inline']), $end - $begin + 1);
		//为啥使用这个格式呢？
        $this->format = self::FORMAT_RAW;

        return $this;
    }

    /**
	 *  把指定的流数据作为文件内容发送给浏览器
     * Sends the specified stream as a file to the browser.
     * 注意，这仍然是在准备发送文件，直到调用send()方法才真正发送给浏览器。如果我们不明确地调用，则稍后Yii框架会在程序的结尾处调用send()方法
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *  $handle  这流数据的资源，一般是打开的文件资源，网络连接等
     * @param resource $handle the handle of the stream to be sent.
	 * $attachmentName  展示给终端用户的，下载资源的名字（也许和服务端原始文件名不一样）
     * @param string $attachmentName the file name shown to the user.
	 * $options  是额外的选项（请看sendFile()方法，有点重复了）
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
	 *   fileSize 文件大小，当内容大小知道，但是内容是不可寻址的。默认使用ftell()函数确定内容大小。
     *  - `fileSize`: the size of the content to stream this is useful when size of the content is known
     *    and the content is not seekable. Defaults to content size using `ftell()`.
	 * 这个选项在2.0.4中才有
     *    This option is available since version 2.0.4.
     *
     * @return $this the response object itself
     * @throws RangeNotSatisfiableHttpException if the requested range is not satisfiable
     * @see sendFile() for an example implementation. 请参考sendFile()方法里的一个例子
     */
    public function sendStreamAsFile($handle, $attachmentName, $options = [])
    {

        $headers = $this->getHeaders();
        if (isset($options['fileSize'])) {
            $fileSize = $options['fileSize'];
        } else {
			//首先移动到文件尾，注意查看手册。第三个参数SEEK_END，是说从文件末尾参考偏移量。第二个参数就是偏移量。这里是0。
			//所以，就是说距离文件末尾偏移量为0（就是不偏移呗）的地方，岂不是移动到文件末尾吗？
            fseek($handle, 0, SEEK_END);
			//然后读取此时指针的位置，就是流资源的数据大小了。这样解释明白吗？
            $fileSize = ftell($handle);
        }
		//确定要发送给客户端的数据范围（也许是整个文件流，也许是文件流的一部分）
        $range = $this->getHttpRange($fileSize);
        if ($range === false) {
			//范围不确定的话，就得报异常。可见文件流的范围必须确定。
            $headers->set('Content-Range', "bytes */$fileSize");
            throw new RangeNotSatisfiableHttpException();
        }
		//下面这段代码涉及了分批次下载的概念，或者叫断点续传的功能。206这个状态码，估计在迅雷，电驴等下载应用里比较常见。
        list($begin, $end) = $range;
        if ($begin != 0 || $end != $fileSize - 1) {
            $this->setStatusCode(206);
            $headers->set('Content-Range', "bytes $begin-$end/$fileSize");
        } else {
		//否则就是整个的文件下载，200即可。
            $this->setStatusCode(200);
        }
		//去设置有关下载的一些http响应头
        $mimeType = isset($options['mimeType']) ? $options['mimeType'] : 'application/octet-stream';
        $this->setDownloadHeaders($attachmentName, $mimeType, !empty($options['inline']), $end - $begin + 1);
		//也是设置这样的格式。有什么用意？response组件的响应格式默认是HTML，只在下载时使用FORMAT_RAW
        $this->format = self::FORMAT_RAW;
		//存储到stream属性中，在send方法里会用得到
        $this->stream = [$handle, $begin, $end];

        return $this;
    }

    /**
	 * 设置http头部字段，这些头部字段是有关文件下载的，并不完全清楚，后续慢慢了解吧。
     * Sets a default set of HTTP headers for file downloading purpose.
     * @param string $attachmentName the attachment file name
     * @param string $mimeType the MIME type for the response. If null, `Content-Type` header will NOT be set.
     * @param bool $inline whether the browser should open the file within the browser window. Defaults to false,
     * meaning a download dialog will pop up.
     * @param int $contentLength the byte length of the file being downloaded. If null, `Content-Length` header will NOT be set.
     * @return $this the response object itself
     */
    public function setDownloadHeaders($attachmentName, $mimeType = null, $inline = false, $contentLength = null)
    {
        $headers = $this->getHeaders();

        $disposition = $inline ? 'inline' : 'attachment';
		//链式设置多个头部字段
        $headers->setDefault('Pragma', 'public')
            ->setDefault('Accept-Ranges', 'bytes')//这个头部，非常重要，有些客户端据此判断服务端某些资源是否支持断点续传。
            ->setDefault('Expires', '0')
            ->setDefault('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->setDefault('Content-Disposition', $this->getDispositionHeaderValue($disposition, $attachmentName));

        if ($mimeType !== null) {
            $headers->setDefault('Content-Type', $mimeType);
        }

        if ($contentLength !== null) {
            $headers->setDefault('Content-Length', $contentLength);
        }

        return $this;
    }

    /**
	 * 查看$_SERVER变量HTTP_RANGE。初次认识这个变量。
	 * 大概看一下，这个好像跟断点续传，分批次下载文件有关吧？
	 * 这样，先略过吧，总之我们知道不是每次都一股脑地把文件全部给客户端，而是会给出整个流资源的数据范围。
	 * 该方法就是根据HTTP_RANGE确定要发送给客户端的数据范围吧。
	 * 根据代码$_SERVER可知，HTTP_RANGE这个应该是从http请求头部而来。像这样：range bytes=1-50 的。
	 * 检测http请求中的http range字段。
     * Determines the HTTP range given in the request.
     * @param int $fileSize the size of the file that will be used to validate the requested HTTP range.
     * @return array|bool the range (begin, end), or false if the range request is invalid.
     */
    protected function getHttpRange($fileSize)
    {
		//没有这个变量，就返回一个闭区间
        if (!isset($_SERVER['HTTP_RANGE']) || $_SERVER['HTTP_RANGE'] === '-') {
            return [0, $fileSize - 1];
        }
		//没有匹配到符合规范的字符时，return false。
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $matches)) {
            return false;
        }
		//为了理解下述的代码逻辑，我得知道分批下载的http请求和http响应头部都应该是哪些内容。所以，暂时，先略了吧。
        if ($matches[1] === '') {
            $start = $fileSize - $matches[2];
            $end = $fileSize - 1;
        } elseif ($matches[2] !== '') {
            $start = $matches[1];
            $end = $matches[2];
            if ($end >= $fileSize) {
                $end = $fileSize - 1;
            }
        } else {
            $start = $matches[1];
            $end = $fileSize - 1;
        }
        if ($start < 0 || $start > $end) {
            return false;
        } else {
            return [$start, $end];
        }
    }

    /**略过
     * Sends existing file to a browser as a download using x-sendfile.
     *
     * X-Sendfile is a feature allowing a web application to redirect the request for a file to the webserver
     * that in turn processes the request, this way eliminating the need to perform tasks like reading the file
     * and sending it to the user. When dealing with a lot of files (or very big files) this can lead to a great
     * increase in performance as the web application is allowed to terminate earlier while the webserver is
     * handling the request.
     *
     * The request is sent to the server through a special non-standard HTTP-header.
     * When the web server encounters the presence of such header it will discard all output and send the file
     * specified by that header using web server internals including all optimizations like caching-headers.
     *
     * As this header directive is non-standard different directives exists for different web servers applications:
     *
     * - Apache: [X-Sendfile](http://tn123.org/mod_xsendfile)
     * - Lighttpd v1.4: [X-LIGHTTPD-send-file](http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file)
     * - Lighttpd v1.5: [X-Sendfile](http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file)
     * - Nginx: [X-Accel-Redirect](http://wiki.nginx.org/XSendfile)
     * - Cherokee: [X-Sendfile and X-Accel-Redirect](http://www.cherokee-project.com/doc/other_goodies.html#x-sendfile)
     *
     * So for this method to work the X-SENDFILE option/module should be enabled by the web server and
     * a proper xHeader should be sent.
     *
     * **Note**
     *
     * This option allows to download files that are not under web folders, and even files that are otherwise protected
     * (deny from all) like `.htaccess`.
     *
     * **Side effects**
     *
     * If this option is disabled by the web server, when this method is called a download configuration dialog
     * will open but the downloaded file will have 0 bytes.
     *
     * **Known issues**
     *
     * There is a Bug with Internet Explorer 6, 7 and 8 when X-SENDFILE is used over an SSL connection, it will show
     * an error message like this: "Internet Explorer was not able to open this Internet site. The requested site
     * is either unavailable or cannot be found.". You can work around this problem by removing the `Pragma`-header.
     *
     * **Example**
     *
     * ```php
     * Yii::$app->response->xSendFile('/home/user/Pictures/picture1.jpg');
     * ```
     *
     * @param string $filePath file name with full path
     * @param string $attachmentName file name shown to the user. If null, it will be determined from `$filePath`.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *  - xHeader: string, the name of the x-sendfile header. Defaults to "X-Sendfile".
     *
     * @return $this the response object itself
     * @see sendFile()
     */
    public function xSendFile($filePath, $attachmentName = null, $options = [])
    {
        if ($attachmentName === null) {
            $attachmentName = basename($filePath);
        }
        if (isset($options['mimeType'])) {
            $mimeType = $options['mimeType'];
        } elseif (($mimeType = FileHelper::getMimeTypeByExtension($filePath)) === null) {
            $mimeType = 'application/octet-stream';
        }
        if (isset($options['xHeader'])) {
            $xHeader = $options['xHeader'];
        } else {
            $xHeader = 'X-Sendfile';
        }

        $disposition = empty($options['inline']) ? 'attachment' : 'inline';
        $this->getHeaders()
            ->setDefault($xHeader, $filePath)
            ->setDefault('Content-Type', $mimeType)
            ->setDefault('Content-Disposition', $this->getDispositionHeaderValue($disposition, $attachmentName));

        $this->format = self::FORMAT_RAW;

        return $this;
    }

    /**
	 * 返回Content-Disposition这个头部字段的值，考虑了新旧浏览器的兼容性。
     * Returns Content-Disposition header value that is safe to use with both old and new browsers
     *
     * Fallback name:
     *
     * - Causes issues if contains non-ASCII characters with codes less than 32 or more than 126.
     * - Causes issues if contains urlencoded characters (starting with `%`) or `%` character. Some browsers interpret
     *   `filename="X"` as urlencoded name, some don't.
     * - Causes issues if contains path separator characters such as `\` or `/`.
     * - Since value is wrapped with `"`, it should be escaped as `\"`.
     * - Since input could contain non-ASCII characters, fallback is obtained by transliteration.
     *
     * UTF name:
     *
     * - Causes issues if contains path separator characters such as `\` or `/`.
     * - Should be urlencoded since headers are ASCII-only.
     * - Could be omitted if it exactly matches fallback name.
     *
     * @param string $disposition
     * @param string $attachmentName
     * @return string
     *
     * @since 2.0.10
     */
    protected function getDispositionHeaderValue($disposition, $attachmentName)
    {
        $fallbackName = str_replace('"', '\\"', str_replace(['%', '/', '\\'], '_', Inflector::transliterate($attachmentName, Inflector::TRANSLITERATE_LOOSE)));
        $utfName = rawurlencode(str_replace(['%', '/', '\\'], '', $attachmentName));

        $dispositionHeader = "{$disposition}; filename=\"{$fallbackName}\"";
        if ($utfName !== $fallbackName) {
            $dispositionHeader .= "; filename*=utf-8''{$utfName}";
        }
        return $dispositionHeader;
    }

    /**
	 * 重定向浏览器到指定的URL
     * Redirects the browser to the specified URL.
     *
	 * 该方法其实就是给当前的response增加一个Location头部字段。注意，只是增加一个头部字段，到send()方法时
     * This method adds a "Location" header to the current response. Note that it does not send out
	 * 才真正发送http响应。下面给出了使用重定向的几个例子：
     * the header until [[send()]] is called. In a controller action you may use this method as follows:
     *
     * ```php
     * return Yii::$app->getResponse()->redirect($url);
     * ```
     * 如果在其他情况下，你想立即重定向，那就直接调用send()方法就是了。
     * In other places, if you want to send out the "Location" header immediately, you should use
     * the following code:
     *
     * ```php
     * Yii::$app->getResponse()->redirect($url)->send();
     * return;
     * ```
     * 如果是AJAX模式，正常情况下Location对ajax是无效的，需要客户端的一些JS代码的协助才行。
     * In AJAX mode, this normally will not work as expected unless there are some
	 * 此种情况下，服务端就应该发送X-Redirect头字段给客户端，而不是网页的Location字段。
     * client-side JavaScript code handling the redirection. To help achieve this goal,
     * this method will send out a "X-Redirect" header instead of "Location".
     * Yii框架提供的yii.js里有段js代码可以处理服务端返回X-Redirect的情况。
     * If you use the "yii" JavaScript module, it will handle the AJAX redirection as
	 * 当然，如果你自己不用yii.js的话，也简单，自己写点js代码就行了。
     * described above. Otherwise, you should write the following JavaScript code to
     * handle the redirection:
     * ajax完成重定向的js代码，就是这两行了，简单不？
     * ```javascript
     * $document.ajaxComplete(function (event, xhr, settings) {
     *     var url = xhr && xhr.getResponseHeader('X-Redirect');
     *     if (url) {
     *         window.location = url;
     *     }
     * });
     * ```
     * $url  第一个参数。可以是字符串或者数组，表示重定向的地址。有下面几种情况：
     * @param string|array $url the URL to be redirected to. This can be in one of the following formats:
     *
	 *  1 全部域名的字符串
     * - a string representing a URL (e.g. "http://example.com")
	 *  2 URL别名
     * - a string representing a URL alias (e.g. "@example.com")
	 *  3 一个数组，元素1是路由，后续元素是键值对，比如['site/index', 'ref' => 1]
     * - an array in the format of `[$route, ...name-value pairs...]` (e.g. `['site/index', 'ref' => 1]`).
	 * 注意，路由是关于整个应用的（with respect to),而不是相对于一个控制器或模块
     *   Note that the route is with respect to the whole application, instead of relative to a controller or module.
	 *   Url::to快捷方式，将会把数组格式转换为最终的URL
     *   [[Url::to()]] will be used to convert the array into a URL.
     *
	 * 任何相对的URL都会转换为一个绝对的URL地址，这是通过在前面增加当前请求的host信息实现的
     * Any relative URL will be converted into an absolute one by prepending it with the host info
     * of the current request.
     * $statusCode http响应的状态码，默认是302
     * @param int $statusCode the HTTP status code. Defaults to 302.
     * See <http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html>  w3上有关http响应码的解释
     * for details about HTTP status code
	 * $checkAjax   是否处理AJAX (PJAX）请求，默认是true.
     * @param bool $checkAjax whether to specially handle AJAX (and PJAX) requests. Defaults to true,
	 * 意味着 如果当前请求是AJAX或着PJAX，那么仍然可以实现跳转。
     * meaning if the current request is an AJAX or PJAX request, then calling this method will cause the browser
	 * 如果是false的话，将肯定返回Location相关的响应头。如果是ajax请求的话，此时跳转就无效了。
     * to redirect to the given URL. If this is false, a `Location` header will be sent, which when received as
     * an AJAX/PJAX response, may NOT cause browser redirection.
	 * 客户端浏览器的请求里没有IE浏览器特有的X-Ie-Redirect-Compatibility时，跳转才有效！
     * Takes effect only when request header `X-Ie-Redirect-Compatibility` is absent.
     * @return $this the response object itself
     */
    public function redirect($url, $statusCode = 302, $checkAjax = true)
    {
        if (is_array($url) && isset($url[0])) {
            // ensure the route is absolute，确保左边有一个斜杠"/"，左斜杠就是绝对路径，因为左斜杠代表根目录。
            $url[0] = '/' . ltrim($url[0], '/');
        }
		//用Url一个快捷方法去解析$url参数，可以解析数组或者字符串。最终返回一个字符串。
        $url = Url::to($url);
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = Yii::$app->getRequest()->getHostInfo() . $url;
        }
		//是否检测ajax请求，据此返回ajax可以处理的跳转头部
        if ($checkAjax) {
            if (Yii::$app->getRequest()->getIsAjax()) {
                if (Yii::$app->getRequest()->getHeaders()->get('X-Ie-Redirect-Compatibility') !== null && $statusCode === 302) {
                    // Ajax 302 redirect in IE does not work. Change status code to 200. See https://github.com/yiisoft/yii2/issues/9670
                    $statusCode = 200;
                }
                if (Yii::$app->getRequest()->getIsPjax()) {
                    $this->getHeaders()->set('X-Pjax-Url', $url);
                } else {
                    $this->getHeaders()->set('X-Redirect', $url);
                }
            } else {
                $this->getHeaders()->set('Location', $url);
            }
        } else {
            $this->getHeaders()->set('Location', $url);
        }
		//设置响应状态码。
        $this->setStatusCode($statusCode);

        return $this;
    }

    /**
	 * 刷新当前页面
     * Refreshes the current page.
	 * 该方法的作用，跟用户在客户端点击刷新按钮是一个功能。（不是提交数据的post，一般是get方式）
     * The effect of this method call is the same as the user pressing the refresh button of his browser
     * (without re-posting data).
     * 在控制器的action里，看下面使用刷新功能的例子：
     * In a controller action you may use this method like this:
     *
     * ```php
     * return Yii::$app->getResponse()->refresh();
     * ```
     * 有一个参数$anchor，是一个添加到刷新URL的锚。
     * @param string $anchor the anchor that should be appended to the redirection URL.
	 * 默认是空，请注意锚的语法，以'#开头'
     * Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
     * @return Response the response object itself
     */
    public function refresh($anchor = '')
    {
		//所谓刷新，其实还是一个重定向而已，只不过重定向的URL还是跟当前请求的URL是一样的。
        return $this->redirect(Yii::$app->getRequest()->getUrl() . $anchor);
    }

    //真怪啊，$_cookies属性不是在类开头，而是穿插在方法里声明。
    //但也有道理，在需要了解的时候声明出来，都是有关cookie的成员放一块。
    private $_cookies;

    /**
	 * 返回cookie集合
     * Returns the cookie collection.
     *  response组件中的cookie集合对象，代表了http响应中,在header部分set-cookie字段的内容
	 * 通过返回的cookie集合，开发人员可以添加或删除cookie,如下：
     * Through the returned cookie collection, you add or remove cookies as follows,
     *
	 * 添加cookie的例子
     * ```php
     * // add a cookie
     * $response->cookies->add(new Cookie([
     *     'name' => $name,
     *     'value' => $value,
     * ]);
     * 删除cookie的例子
     * // remove a cookie
     * $response->cookies->remove('name');
     * // alternatively，或者使用这个办法
     * unset($response->cookies['name']);
     * ```
     * CookieCollection类并没有在当前文件里引入（use进来），但是却可以通过自动加载机制加载进来，
	 * 因为CookieCollection类和response类在同一个目录下
     * @return CookieCollection the cookie collection.
     */
    public function getCookies()
    {
        if ($this->_cookies === null) {
            //看到没有，实例化cookie集合类时，不必设置readonly为true，
            //这就是与request组件使用cookie集合对象的区别
            $this->_cookies = new CookieCollection;
        }
        return $this->_cookies;
    }

    /**
	* 检测设置的响应状态码是否合法
     * @return bool whether this response has a valid [[statusCode]].
     */
    public function getIsInvalid()
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
	 * 是否是informational类型的响应。（响应码在100到200之间的）
	 * 了解什么是informational的响应，需要去https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html看RFC
     * @return bool whether this response is informational
     */
    public function getIsInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
	 * 检测状态码是否在  [200,300)区间里
     * @return bool whether this response is successful
     */
    public function getIsSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**是否是跳转类型的响应，跳转类型的响应状态码在[300,400)区间。
     * @return bool whether this response is a redirection
     */
    public function getIsRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
	 * 是否是报告客户端错误的响应，响应码区间在[400,500)
     * @return bool whether this response indicates a client error
     */
    public function getIsClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
	* 是否是报告服务端错误的响应，状态码在[500,600)区间。
     * @return bool whether this response indicates a server error
     */
    public function getIsServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
	* 状态码是否是 200
     * @return bool whether this response is OK
     */
    public function getIsOk()
    {
        return $this->getStatusCode() == 200;
    }

    /**
	 * 状态码是否是  403
     * @return bool whether this response indicates the current request is forbidden
     */
    public function getIsForbidden()
    {
        return $this->getStatusCode() == 403;
    }

    /**
	 * 状态码是否是  404
     * @return bool whether this response indicates the currently requested resource is not found
     */
    public function getIsNotFound()
    {
        return $this->getStatusCode() == 404;
    }

    /**
	 * 201,204,304是什么意思呢？曾经在进销存里，针对ajax请求返回202,（服务端接收了请求，但还未处理）
	 * 201 请求成功，服务端成功创建了资源。
	 * 204服务端成功处理了请求，但不返回任何内容。
	 * 304 Not Modified,说明上次客户端请求得到的响应还没修改过，客户端可以继续使用，服务端就不再返回重复的内容了。
     * @return bool whether this response is empty
     */
    public function getIsEmpty()
    {
        return in_array($this->getStatusCode(), [201, 204, 304]);
    }

    /**
	 * 返回四种web响应里内容的格式
	 * HTML,XML,JSON,JSONP
	 * 这四种响应都继承了同一个接口yii\web\ResponseFormatterInterface
     * @return array the formatters that are supported by default
     */
    protected function defaultFormatters()
    {
        return [
            self::FORMAT_HTML => 'yii\web\HtmlResponseFormatter',
            self::FORMAT_XML => 'yii\web\XmlResponseFormatter',
            self::FORMAT_JSON => 'yii\web\JsonResponseFormatter',
			//其实是三种类，JSON和JSONP在同一个类里用两个方法分别实现了。
            self::FORMAT_JSONP => [
                'class' => 'yii\web\JsonResponseFormatter',
                'useJsonp' => true,
            ],
        ];
    }

    /**
	 * 准备发送请求。准备？。涉及这么几个处理  1format格式化，没了
     * Prepares for sending the response.
	 * 默认的实现，就是把data成员转换给content成员，然后设置头字段
     * The default implementation will convert [[data]] into [[content]] and set headers accordingly.
     * @throws InvalidConfigException if the formatter for the specified format is invalid or [[format]] is not supported
     */
    protected function prepare()
    {
		//不处理流数据
        if ($this->stream !== null) {
            return;
        }
		//格式工具类是否在列表里（列表里默认是那四种类型，且response模式是HTML的）
        if (isset($this->formatters[$this->format])) {
            $formatter = $this->formatters[$this->format];
			//依赖注入方式实例化这个格式工具类
            if (!is_object($formatter)) {
                $this->formatters[$this->format] = $formatter = Yii::createObject($formatter);
            }
			//得是这个接口的实现类才行，否则报异常！
            if ($formatter instanceof ResponseFormatterInterface) {
                $formatter->format($this);
            } else {
                throw new InvalidConfigException("The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
            }
		//是否是FORMAT_RAW格式的（比如验证码二进制流）
        } elseif ($this->format === self::FORMAT_RAW) {
            if ($this->data !== null) {
				//这种格式，无需做格式处理，直接赋值即可。
                $this->content = $this->data;
            }
        } else {
            throw new InvalidConfigException("Unsupported response format: {$this->format}");
        }
	
		//是数组的话，那不行，必须是字符串。
        if (is_array($this->content)) {
            throw new InvalidParamException('Response content must not be an array.');
		//如果是对象的话，是否存在__toString魔术方法。不行的话也报异常。
        } elseif (is_object($this->content)) {
            if (method_exists($this->content, '__toString')) {
                $this->content = $this->content->__toString();
            } else {
                throw new InvalidParamException('Response content must be a string or an object implementing __toString().');
            }
        }
		//		总之,content得是字符串才行。
    }
}
