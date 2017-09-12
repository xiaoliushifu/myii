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
 * 
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
 *  CookieCollection集合
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
     *  响应的格式，定义如何转化data为content
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
     * MIME类型  ，来源于http的ACCEPT头信息，是表名客户端浏览器支持的类型，Yii框架据此来发送http响应
     * 类型可不是瞎设置的呀。
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
     * 存储处理response过程中的头信息
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
	 * 头部集合包含了当前已注册（添加）的http头部
     * The header collection contains the currently registered HTTP headers.
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection;
        }
        return $this->_headers;
    }

    /**
     * Sends the response to the client.
     */
    public function send()
    {
        if ($this->isSent) {
            return;
        }
        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendHeaders();
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }

    /**
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
     * Sends the response headers to the client
     */
    protected function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }
        if ($this->_headers) {
            $headers = $this->getHeaders();
            foreach ($headers as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                $replace = true;
                foreach ($values as $value) {
                    header("$name: $value", $replace);
                    $replace = false;
                }
            }
        }
        $statusCode = $this->getStatusCode();
        header("HTTP/{$this->version} {$statusCode} {$this->statusText}");
        $this->sendCookies();
    }

    /**
     * response组件发送cookie给客户端（使用php原生setcookie()函数）
     * 发送之前肯定早已准备好要种植给客户端的cookie了，都存在response组件的_cookies属性中
     * 为了安全考虑，防止cookie在客户端被篡改，还使用了安全组件给cookie值进行hash散射
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        //没有要种植的cookie，那就算了
        if ($this->_cookies === null) {
            return;
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        //cookie集合对象可以被遍历，是因为这个对象实现了PHP原生接口IteratorAggregate
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            //不过期，且配置了认证key，则对发送到客户端的cookie进行加密
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
     * Sends the response content to the client
     */
    protected function sendContent()
    {
        if ($this->stream === null) {
            echo $this->content;

            return;
        }

        set_time_limit(0); // Reset time limit for big files
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list ($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                echo fread($handle, $chunkSize);
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
            }
            fclose($this->stream);
        }
    }

    /**
     * Sends a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
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
     *     return Yii::$app->response->sendFile("$storagePath/$filename", $filename);
     * }
     * ```
     *
     * @param string $filePath the path of the file to be sent.
     * @param string $attachmentName the file name shown to the user. If null, it will be determined from `$filePath`.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *
     * @return $this the response object itself
     * @see sendContentAsFile()
     * @see sendStreamAsFile()
     * @see xSendFile()
     */
    public function sendFile($filePath, $attachmentName = null, $options = [])
    {
        if (!isset($options['mimeType'])) {
            $options['mimeType'] = FileHelper::getMimeTypeByExtension($filePath);
        }
        if ($attachmentName === null) {
            $attachmentName = basename($filePath);
        }
        $handle = fopen($filePath, 'rb');
        $this->sendStreamAsFile($handle, $attachmentName, $options);

        return $this;
    }

    /**
     * Sends the specified content as a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
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

        $contentLength = StringHelper::byteLength($content);
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
            $this->content = $content;
        }

        $mimeType = isset($options['mimeType']) ? $options['mimeType'] : 'application/octet-stream';
        $this->setDownloadHeaders($attachmentName, $mimeType, !empty($options['inline']), $end - $begin + 1);

        $this->format = self::FORMAT_RAW;

        return $this;
    }

    /**
     * Sends the specified stream as a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
     * @param resource $handle the handle of the stream to be sent.
     * @param string $attachmentName the file name shown to the user.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *  - `fileSize`: the size of the content to stream this is useful when size of the content is known
     *    and the content is not seekable. Defaults to content size using `ftell()`.
     *    This option is available since version 2.0.4.
     *
     * @return $this the response object itself
     * @throws RangeNotSatisfiableHttpException if the requested range is not satisfiable
     * @see sendFile() for an example implementation.
     */
    public function sendStreamAsFile($handle, $attachmentName, $options = [])
    {
        $headers = $this->getHeaders();
        if (isset($options['fileSize'])) {
            $fileSize = $options['fileSize'];
        } else {
            fseek($handle, 0, SEEK_END);
            $fileSize = ftell($handle);
        }

        $range = $this->getHttpRange($fileSize);
        if ($range === false) {
            $headers->set('Content-Range', "bytes */$fileSize");
            throw new RangeNotSatisfiableHttpException();
        }

        list($begin, $end) = $range;
        if ($begin != 0 || $end != $fileSize - 1) {
            $this->setStatusCode(206);
            $headers->set('Content-Range', "bytes $begin-$end/$fileSize");
        } else {
            $this->setStatusCode(200);
        }

        $mimeType = isset($options['mimeType']) ? $options['mimeType'] : 'application/octet-stream';
        $this->setDownloadHeaders($attachmentName, $mimeType, !empty($options['inline']), $end - $begin + 1);

        $this->format = self::FORMAT_RAW;
        $this->stream = [$handle, $begin, $end];

        return $this;
    }

    /**
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
        $headers->setDefault('Pragma', 'public')
            ->setDefault('Accept-Ranges', 'bytes')
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
     * Determines the HTTP range given in the request.
     * @param int $fileSize the size of the file that will be used to validate the requested HTTP range.
     * @return array|bool the range (begin, end), or false if the range request is invalid.
     */
    protected function getHttpRange($fileSize)
    {
        if (!isset($_SERVER['HTTP_RANGE']) || $_SERVER['HTTP_RANGE'] === '-') {
            return [0, $fileSize - 1];
        }
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $matches)) {
            return false;
        }
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

    /**
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
     * Redirects the browser to the specified URL.
     *
     * This method adds a "Location" header to the current response. Note that it does not send out
     * the header until [[send()]] is called. In a controller action you may use this method as follows:
     *
     * ```php
     * return Yii::$app->getResponse()->redirect($url);
     * ```
     *
     * In other places, if you want to send out the "Location" header immediately, you should use
     * the following code:
     *
     * ```php
     * Yii::$app->getResponse()->redirect($url)->send();
     * return;
     * ```
     *
     * In AJAX mode, this normally will not work as expected unless there are some
     * client-side JavaScript code handling the redirection. To help achieve this goal,
     * this method will send out a "X-Redirect" header instead of "Location".
     *
     * If you use the "yii" JavaScript module, it will handle the AJAX redirection as
     * described above. Otherwise, you should write the following JavaScript code to
     * handle the redirection:
     *
     * ```javascript
     * $document.ajaxComplete(function (event, xhr, settings) {
     *     var url = xhr && xhr.getResponseHeader('X-Redirect');
     *     if (url) {
     *         window.location = url;
     *     }
     * });
     * ```
     *
     * @param string|array $url the URL to be redirected to. This can be in one of the following formats:
     *
     * - a string representing a URL (e.g. "http://example.com")
     * - a string representing a URL alias (e.g. "@example.com")
     * - an array in the format of `[$route, ...name-value pairs...]` (e.g. `['site/index', 'ref' => 1]`).
     *   Note that the route is with respect to the whole application, instead of relative to a controller or module.
     *   [[Url::to()]] will be used to convert the array into a URL.
     *
     * Any relative URL will be converted into an absolute one by prepending it with the host info
     * of the current request.
     *
     * @param int $statusCode the HTTP status code. Defaults to 302.
     * See <http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html>
     * for details about HTTP status code
     * @param bool $checkAjax whether to specially handle AJAX (and PJAX) requests. Defaults to true,
     * meaning if the current request is an AJAX or PJAX request, then calling this method will cause the browser
     * to redirect to the given URL. If this is false, a `Location` header will be sent, which when received as
     * an AJAX/PJAX response, may NOT cause browser redirection.
     * Takes effect only when request header `X-Ie-Redirect-Compatibility` is absent.
     * @return $this the response object itself
     */
    public function redirect($url, $statusCode = 302, $checkAjax = true)
    {
        if (is_array($url) && isset($url[0])) {
            // ensure the route is absolute
            $url[0] = '/' . ltrim($url[0], '/');
        }
        $url = Url::to($url);
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = Yii::$app->getRequest()->getHostInfo() . $url;
        }

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

        $this->setStatusCode($statusCode);

        return $this;
    }

    /**
     * Refreshes the current page.
     * The effect of this method call is the same as the user pressing the refresh button of his browser
     * (without re-posting data).
     *
     * In a controller action you may use this method like this:
     *
     * ```php
     * return Yii::$app->getResponse()->refresh();
     * ```
     *
     * @param string $anchor the anchor that should be appended to the redirection URL.
     * Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
     * @return Response the response object itself
     */
    public function refresh($anchor = '')
    {
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
     * ```php
     * // add a cookie
     * $response->cookies->add(new Cookie([
     *     'name' => $name,
     *     'value' => $value,
     * ]);
     *
     * // remove a cookie
     * $response->cookies->remove('name');
     * // alternatively
     * unset($response->cookies['name']);
     * ```
     * CookieCollection类并没有在当前文件里引入（use进来），但是却可以通过自动加载机制加载进来
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
     * @return bool whether this response has a valid [[statusCode]].
     */
    public function getIsInvalid()
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
     * @return bool whether this response is informational
     */
    public function getIsInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * @return bool whether this response is successful
     */
    public function getIsSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * @return bool whether this response is a redirection
     */
    public function getIsRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * @return bool whether this response indicates a client error
     */
    public function getIsClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * @return bool whether this response indicates a server error
     */
    public function getIsServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * @return bool whether this response is OK
     */
    public function getIsOk()
    {
        return $this->getStatusCode() == 200;
    }

    /**
     * @return bool whether this response indicates the current request is forbidden
     */
    public function getIsForbidden()
    {
        return $this->getStatusCode() == 403;
    }

    /**
     * @return bool whether this response indicates the currently requested resource is not found
     */
    public function getIsNotFound()
    {
        return $this->getStatusCode() == 404;
    }

    /**
     * @return bool whether this response is empty
     */
    public function getIsEmpty()
    {
        return in_array($this->getStatusCode(), [201, 204, 304]);
    }

    /**
	 * 返回四种web响应里内容的格式
	 * HTML,XML,JSON,JSONP
     * @return array the formatters that are supported by default
     */
    protected function defaultFormatters()
    {
        return [
            self::FORMAT_HTML => 'yii\web\HtmlResponseFormatter',
            self::FORMAT_XML => 'yii\web\XmlResponseFormatter',
            self::FORMAT_JSON => 'yii\web\JsonResponseFormatter',
            self::FORMAT_JSONP => [
                'class' => 'yii\web\JsonResponseFormatter',
                'useJsonp' => true,
            ],
        ];
    }

    /**
     * Prepares for sending the response.
     * The default implementation will convert [[data]] into [[content]] and set headers accordingly.
     * @throws InvalidConfigException if the formatter for the specified format is invalid or [[format]] is not supported
     */
    protected function prepare()
    {
        if ($this->stream !== null) {
            return;
        }

        if (isset($this->formatters[$this->format])) {
            $formatter = $this->formatters[$this->format];
            if (!is_object($formatter)) {
                $this->formatters[$this->format] = $formatter = Yii::createObject($formatter);
            }
            if ($formatter instanceof ResponseFormatterInterface) {
                $formatter->format($this);
            } else {
                throw new InvalidConfigException("The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
            }
        } elseif ($this->format === self::FORMAT_RAW) {
            if ($this->data !== null) {
                $this->content = $this->data;
            }
        } else {
            throw new InvalidConfigException("Unsupported response format: {$this->format}");
        }

        if (is_array($this->content)) {
            throw new InvalidParamException('Response content must not be an array.');
        } elseif (is_object($this->content)) {
            if (method_exists($this->content, '__toString')) {
                $this->content = $this->content->__toString();
            } else {
                throw new InvalidParamException('Response content must be a string or an object implementing __toString().');
            }
        }
    }
}
