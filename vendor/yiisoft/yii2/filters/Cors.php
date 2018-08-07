<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\Request;
use yii\web\Response;

/**
 * Cors filter implements [Cross Origin Resource Sharing](http://en.wikipedia.org/wiki/Cross-origin_resource_sharing).
 *
 * Make sure to read carefully what CORS does and does not. CORS do not secure your API,
 * but allow the developer to grant access to third party code (ajax calls from external domain).
 *
 * 读完Cors过滤器，好像有点糊涂，糊涂的是：并没有做验证和什么，仅仅是用协商好的头部手段
 * 达到协商的目的，比如告诉客户端允许的自定义header,什么请求方法，什么来源是否允许等；仅仅如此；
 * 客户端得到这个成功后，应该就知道如何再次请求服务端了；至于服务端允许不允许还是服务端说了算。
 * 那这个Cors的用处在那儿呢？默认是ajax跨域限制，通过首先发送options请求，且据此的响应，后续可以跨域了，达到跨域的目的？？？
 * You may use CORS filter by attaching it as a behavior to a controller or module, like the following,
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'corsFilter' => [
 *             'class' => \yii\filters\Cors::className(),
 *         ],
 *     ];
 * }
 * ```
 *
 * The CORS filter can be specialized to restrict parameters, like this,
 * [MDN CORS Information](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS)
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'corsFilter' => [
 *             'class' => \yii\filters\Cors::className(),
 *             'cors' => [
 *                 // restrict access to
 *                 'Origin' => ['http://www.myserver.com', 'https://www.myserver.com'],
 *                 'Access-Control-Request-Method' => ['POST', 'PUT'],
 *                 // Allow only POST and PUT methods
 *                 'Access-Control-Request-Headers' => ['X-Wsse'],
 *                 // Allow only headers 'X-Wsse'
 *                 'Access-Control-Allow-Credentials' => true,
 *                 // Allow OPTIONS caching
 *                 'Access-Control-Max-Age' => 3600,
 *                 // Allow the X-Pagination-Current-Page header to be exposed to the browser.
 *                 'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
 *             ],
 *
 *         ],
 *     ];
 * }
 * ```
 *
 * For more information on how to add the CORS filter to a controller, see
 * the [Guide on REST controllers](guide:rest-controllers#cors).
 *
 * @author Philippe Gaultier <pgaultier@gmail.com>
 * @since 2.0
 */
class Cors extends ActionFilter
{
    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request;
    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;
    /**
     * @var array define specific CORS rules for specific actions
     */
    public $actions = [];
    /**
     * @var array Basic headers handled for the CORS requests.
     * 这个数组是配置接收客户端的请求header部分字段，也就是说，服务端从http客户端请求的这几个header字段里作为判断协商的关键；
     * 也可以自定义，但是目前默认的如下五个够用了
     */
    public $cors = [
        'Origin' => ['*'],//Origin字段指明客户端的来源信息，客户端是哪个域名或者ip的，*代表服务端的限制规则是任意域名，ip
        'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        'Access-Control-Request-Headers' => ['*'],
        'Access-Control-Allow-Credentials' => null,
        'Access-Control-Max-Age' => 86400,
        'Access-Control-Expose-Headers' => [],
    ];


    /**
     * @inheritdoc
     * 这是过滤器的入口
     */
    public function beforeAction($action)
    {
        //取得http客户端对象
        $this->request = $this->request ?: Yii::$app->getRequest();
        //取得http响应对象
        $this->response = $this->response ?: Yii::$app->getResponse();

        //这个暂时没有用到
        $this->overrideDefaultSettings($action);
        //从$_SERVER里找到需要的header
        $requestCorsHeaders = $this->extractHeaders();
        //处理这些header，响应给客户端对应的header
        $responseCorsHeaders = $this->prepareHeaders($requestCorsHeaders);
        //把响应header添加到响应对象中
        $this->addCorsHeaders($this->response, $responseCorsHeaders);

        return true;
    }

    /**
     * Override settings for specific action.
     * @param \yii\base\Action $action the action settings to override
     */
    public function overrideDefaultSettings($action)
    {
        if (isset($this->actions[$action->id])) {
            $actionParams = $this->actions[$action->id];
            $actionParamsKeys = array_keys($actionParams);
            foreach ($this->cors as $headerField => $headerValue) {
                if (in_array($headerField, $actionParamsKeys)) {
                    $this->cors[$headerField] = $actionParams[$headerField];
                }
            }
        }
    }

    /**
     * Extract CORS headers from the request.
     * @return array CORS headers to handle
     */
    public function extractHeaders()
    {
        $headers = [];
        $requestHeaders = array_keys($this->cors);
        foreach ($requestHeaders as $headerField) {
            //这一步很重要，需要把本地配置的cors加上HTTP_,因为php的习惯问题
            //$_SERVER收到的http请求header,都会加上HTTP_前缀。
            //这样外部实际的http请求header和php的$_SERVER是不同的，多了HTTP_前缀
            $serverField = $this->headerizeToPhp($headerField);
            $headerData = isset($_SERVER[$serverField]) ? $_SERVER[$serverField] : null;
            if ($headerData !== null) {
                $headers[$headerField] = $headerData;
            }
        }

        return $headers;
    }

    /**
     * For each CORS headers create the specific response.
     * @param array $requestHeaders CORS headers we have detected
     * @return array CORS headers ready to be sent
     */
    public function prepareHeaders($requestHeaders)
    {
        $responseHeaders = [];
        // handle Origin
        if (isset($requestHeaders['Origin'], $this->cors['Origin'])) {
            if (in_array('*', $this->cors['Origin']) || in_array($requestHeaders['Origin'], $this->cors['Origin'])) {
				//Access-Control-Allow-Origin这个是W3C的Cors协议指定的服务端必须明确返回的响应header
				//因为客户端的浏览器就是据此来判断是否允许本地脚本向外域发送http请求的，该值必须含有客户端脚本所在的域才行
                $responseHeaders['Access-Control-Allow-Origin'] = $requestHeaders['Origin'];
            }
        }

        //这一步是咋回事，为啥单独对Headers做处理呢？
        $this->prepareAllowHeaders('Headers', $requestHeaders, $responseHeaders);

        if (isset($requestHeaders['Access-Control-Request-Method'])) {
            $responseHeaders['Access-Control-Allow-Methods'] = implode(', ', $this->cors['Access-Control-Request-Method']);
        }

        if (isset($this->cors['Access-Control-Allow-Credentials'])) {
            $responseHeaders['Access-Control-Allow-Credentials'] = $this->cors['Access-Control-Allow-Credentials'] ? 'true' : 'false';
        }

        if (isset($this->cors['Access-Control-Max-Age']) && $this->request->getIsOptions()) {
            $responseHeaders['Access-Control-Max-Age'] = $this->cors['Access-Control-Max-Age'];
        }

        if (isset($this->cors['Access-Control-Expose-Headers'])) {
            $responseHeaders['Access-Control-Expose-Headers'] = implode(', ', $this->cors['Access-Control-Expose-Headers']);
        }

        return $responseHeaders;
    }

    /**
     * Handle classic CORS request to avoid duplicate code.
     * @param string $type the kind of headers we would handle
     * @param array $requestHeaders CORS headers request by client
     * @param array $responseHeaders CORS response headers sent to the client
     */
    protected function prepareAllowHeaders($type, $requestHeaders, &$responseHeaders)
    {
        $requestHeaderField = 'Access-Control-Request-' . $type;
        $responseHeaderField = 'Access-Control-Allow-' . $type;
        if (!isset($requestHeaders[$requestHeaderField], $this->cors[$requestHeaderField])) {
            return;
        }
        if (in_array('*', $this->cors[$requestHeaderField])) {
            $responseHeaders[$responseHeaderField] = $this->headerize($requestHeaders[$requestHeaderField]);
        } else {
            //把这些按照逗号拆分
            $requestedData = preg_split('/[\\s,]+/', $requestHeaders[$requestHeaderField], -1, PREG_SPLIT_NO_EMPTY);
            //计算交集，用strcasecmp做回调函数处理
            $acceptedData = array_uintersect($requestedData, $this->cors[$requestHeaderField], 'strcasecmp');
            if (!empty($acceptedData)) {
                $responseHeaders[$responseHeaderField] = implode(', ', $acceptedData);
            }
        }
    }

    /**
     * Adds the CORS headers to the response.
     * @param Response $response
     * @param array $headers CORS headers which have been computed
     */
    public function addCorsHeaders($response, $headers)
    {
        if (empty($headers) === false) {
            $responseHeaders = $response->getHeaders();
            foreach ($headers as $field => $value) {
                $responseHeaders->set($field, $value);
            }
        }
    }

    /**
     * Convert any string (including php headers with HTTP prefix) to header format.
     *
     * Example:
     *  - X-PINGOTHER -> X-Pingother
     *  - X_PINGOTHER -> X-Pingother
     * @param string $string string to convert
     * @return string the result in "header" format
     */
    protected function headerize($string)
    {
        $headers = preg_split('/[\\s,]+/', $string, -1, PREG_SPLIT_NO_EMPTY);
        $headers = array_map(function ($element) {
            return str_replace(' ', '-', ucwords(strtolower(str_replace(['_', '-'], [' ', ' '], $element))));
        }, $headers);
        return implode(', ', $headers);
    }

    /**
     * Convert any string (including php headers with HTTP prefix) to header format.
     *
     * Example:
     *  - X-Pingother -> HTTP_X_PINGOTHER
     *  - X PINGOTHER -> HTTP_X_PINGOTHER
     * @param string $string string to convert
     * @return string the result in "php $_SERVER header" format
     */
    protected function headerizeToPhp($string)
    {
        return 'HTTP_' . strtoupper(str_replace([' ', '-'], ['_', '_'], $string));
    }
}
