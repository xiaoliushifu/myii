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
use yii\caching\Cache;
use yii\helpers\Url;

/**
* UrlManager组件，处理http请求，通过一系列的规则来解析和创造URL
 * UrlManager handles HTTP request parsing and creation of URLs based on a set of rules.
 * UrlManager默认由应用主体配置为应用组件
 * UrlManager is configured as an application component in [[\yii\base\Application]] by default.
 可以通过`Yii::$app->urlManager`来访问
 * You can access that instance via `Yii::$app->urlManager`.
 *如果想修改默认的组件配置，可以向下面的例子那样配置：
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 *
 * ```php
 * 'urlManager' => [
 *     'enablePrettyUrl' => true,
 *     'rules' => [
 *         // your rules go here
 *     ],
 *     // ...
 * ]
 * ```
 * Rules是实现了[[UrlRuleInterface]]接口的解析规则类，默认是UrlRule。
 * Rules are classes implementing the [[UrlRuleInterface]], by default that is [[UrlRule]].
 如果包裹规则的话，也可以用GroupUrlRule类
 * For nesting rules, there is also a [[GroupUrlRule]] class.
 *
 * For more details and usage information on UrlManager, see the [guide article on routing](guide:runtime-routing).
 *
 * @property string $baseUrl The base URL that is used by [[createUrl()]] to prepend to created URLs.
 * @property string $hostInfo The host info (e.g. `http://www.example.com`) that is used by
 * [[createAbsoluteUrl()]] to prepend to created URLs.
 * @property string $scriptUrl The entry script URL that is used by [[createUrl()]] to prepend to created
 * URLs.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UrlManager extends Component
{
    /**
	 * 是否开启美化SQL。默认不开启，也就是把所有参数放到URL的querystring部分
     * @var bool whether to enable pretty URLs. Instead of putting all parameters in the query
	 而开启美化后，则允许使用path info来表示路由参数，从而使用更友好的url。比如"/news/Yii-is-released"
     * string part of a URL, pretty URLs allow using path info to represent some of the parameters
	 而不是原始默认的"/index.php?r=news%2Fview&id=100"
     * and can thus produce more user-friendly URLs, such as "/news/Yii-is-released", instead of
     * "/index.php?r=news%2Fview&id=100".
     */
    public $enablePrettyUrl = false;
    /**
	  是否开启严格解析，所谓严格模式，是外来的请求URL必须至少匹配规则[rules]之一,才算是有效的请求
     * @var bool whether to enable strict parsing. If strict parsing is enabled, the incoming
     * requested URL must match at least one of the [[rules]] in order to be treated as a valid request.
	 如果非严格模式，则在rules未匹配的情况下，会使用path info作为请求的路由信息。
     * Otherwise, the path info part of the request will be treated as the requested route.
	 需要[[enablePrettyUrl]]开启，该属性才有效
     * This property is used only when [[enablePrettyUrl]] is `true`.
     */
    public $enableStrictParsing = false;
    /**
	数组，一系列规则。创建和解析URL（当美化URL开启时）
     * @var array the rules for creating and parsing URLs when [[enablePrettyUrl]] is `true`.
	 当[[enablePrettyUrl]]开启时可用。数组里的每个元素都是一个单独URL规则类的配置信息
     * This property is used only if [[enablePrettyUrl]] is `true`. Each element in the array
	 在创建Rule对象之前，还会合并ruleConfig数组的信息
     * is the configuration array for creating a single URL rule. The configuration will
     * be merged with [[ruleConfig]] first before it is used for creating the rule object.
     *
	 如果快捷使用的话，那就只配置pattern和route即可。也就是说，无需配置复杂数组信息，用key来表示模式
     * A special shortcut format can be used if a rule only specifies [[UrlRule::pattern|pattern]]
	 用value表示匹配模式后的路由。比如  'post/<id:\d+>' => 'post/view'
     * and [[UrlRule::route|route]]: `'pattern' => 'route'`. That is, instead of using a configuration
     * array, one can use the key to represent the pattern and the value the corresponding route.
     * For example, `'post/<id:\d+>' => 'post/view'`.
     *对于RESTful路由，除了上述提及的快捷格式外，还能增加verb参数，来指定http的请求方法（GET,PUT,POST）
     * For RESTful routing the mentioned shortcut format also allows you to specify the
     * [[UrlRule::verb|HTTP verb]] that the rule should apply for.
	 添加verb时，用空格分开
     * You can do that  by prepending it to the pattern, separated by space.
	 比如：'PUT post/<id:\d+>' => 'post/update'
     * For example, `'PUT post/<id:\d+>' => 'post/update'`.
	 还可以添加多个verb，verb之间用逗号分隔
     * You may specify multiple verbs by separating them with comma
	 比如：'POST,PUT post/index' => 'post/create'
     * like this: `'POST,PUT post/index' => 'post/create'`.
	 支持的verb有：GET, HEAD, POST, PUT, PATCH and DELETE.
     * The supported verbs in the shortcut format are: GET, HEAD, POST, PUT, PATCH and DELETE.
     * Note that [[UrlRule::mode|mode]] will be set to PARSING_ONLY when specifying verb in this way
     * so you normally would not specify a verb for normal GET request.
     *
	 下面是RESTful使用时的几个例子：
     * Here is an example configuration for RESTful CRUD controller:
     *
     * ```php
     * [
     *     'dashboard' => 'site/index',
     *
     *     'POST <controller:[\w-]+>s' => '<controller>/create',
     *     '<controller:[\w-]+>s' => '<controller>/index',
     *
     *     'PUT <controller:[\w-]+>/<id:\d+>'    => '<controller>/update',
     *     'DELETE <controller:[\w-]+>/<id:\d+>' => '<controller>/delete',
     *     '<controller:[\w-]+>/<id:\d+>'        => '<controller>/view',
     * ];
     * ```
     *
     * Note that if you modify this property after the UrlManager object is created, make sure
     * you populate the array with rule objects instead of rule configurations.
     */
    public $rules = [];
    /**
	* 字符串，URL的后缀，比如.html。仅在开启美化URL功能时使用
     * @var string the URL suffix used when [[enablePrettyUrl]] is `true`.
     * For example, ".html" can be used so that the URL looks like pointing to a static HTML page.
     * This property is used only if [[enablePrettyUrl]] is `true`.
     */
    public $suffix;
    /** 
	是否在url中显示出脚本名（一般是index.php）默认是true，（注，需服务端web配合）
     * @var bool whether to show entry script name in the constructed URL. Defaults to `true`.
     * This property is used only if [[enablePrettyUrl]] is `true`.
     */
    public $showScriptName = true;
    /**
	* 路由的参数名，当未开启美化，也就是默认情况可用
     * @var string the GET parameter name for route. This property is used only if [[enablePrettyUrl]] is `false`.
     */
    public $routeParam = 'r';
    /**
	* 缓存对象或字符串，缓存对象或应用主体的缓存组件ID
     * @var Cache|string the cache object or the application component ID of the cache object.
     * Compiled URL rules will be cached through this cache object, if it is available.
     *
     * After the UrlManager object is created, if you want to change this property,
     * you should only assign it with a cache object.
     * Set this property to `false` if you do not want to cache the URL rules.
     */
    public $cache = 'cache';
    /**
	URL规则的默认配置（class下标是必须的）
     * @var array the default configuration of URL rules. Individual rule configurations
     * specified via [[rules]] will take precedence when the same property of the rule is configured.
     */
    public $ruleConfig = ['class' => 'yii\web\UrlRule'];
    /**
	对象，数组，字符串，布尔皆可
     * @var UrlNormalizer|array|string|false the configuration for [[UrlNormalizer]] used by this UrlManager.
	 默认是false,意味着不启用normallization
     * The default value is `false`, which means normalization will be skipped.
	 想启用该功能的话，应该如下的配置：
     * If you wish to enable URL normalization, you should configure this property manually.
     * For example:
     *
     * ```php
     * [
     *     'class' => 'yii\web\UrlNormalizer',
     *     'collapseSlashes' => true,
     *     'normalizeTrailingSlash' => true,
     * ]
     * ```
     *
     * @since 2.0.10
     */
    public $normalizer = false;

    /**
	缓存规则时，在缓存组件里的key
     * @var string the cache key for cached rules
     * @since 2.0.8
     */
    protected $cacheKey = __CLASS__;

	//这四个私有变量很重要
    private $_baseUrl;
    private $_scriptUrl;
    private $_hostInfo;
    private $_ruleCache;


    /**
	urlManager也是组件，也有自己的init方法
     * Initializes UrlManager.
     */
    public function init()
    {
        parent::init();
		
		//实例化这个normalizer,暂不知其作用
        if ($this->normalizer !== false) {
            $this->normalizer = Yii::createObject($this->normalizer);
            if (!$this->normalizer instanceof UrlNormalizer) {
                throw new InvalidConfigException('`' . get_class($this) . '::normalizer` should be an instance of `' . UrlNormalizer::className() . '` or its DI compatible configuration.');
            }
        }
		//未开启美化，或者规则数组为空。都无需往下走了（一般情况下，没有特殊url，则规则数组为空居多）
        if (!$this->enablePrettyUrl || empty($this->rules)) {
            return;
        }
		//实例化缓存组件
        if (is_string($this->cache)) {
            $this->cache = Yii::$app->get($this->cache, false);
        }
		//刚开始，优先从缓存组件里读取规则rules
        if ($this->cache instanceof Cache) {
            $cacheKey = $this->cacheKey;
            $hash = md5(json_encode($this->rules));
            if (($data = $this->cache->get($cacheKey)) !== false && isset($data[1]) && $data[1] === $hash) {
                $this->rules = $data[0];
            } else {
                $this->rules = $this->buildRules($this->rules);
                $this->cache->set($cacheKey, [$this->rules, $hash]);
            }
		//最后才从配置数组里的rules里读取
        } else {
		//这是重点，在init方法里，就根据配置信息实例化了各个rule
            $this->rules = $this->buildRules($this->rules);
        }
    }

    /**
		添加额外的URL规则
     * Adds additional URL rules.
     *该方法内部调用[[buildRules()]]来解析规则表达式，然后填充到[[rules]]属性里。
     * This method will call [[buildRules()]] to parse the given rule declarations and then append or insert
     * them to the existing [[rules]].
     *注意，[[enablePrettyUrl]]不启用时，啥也不做
     * Note that if [[enablePrettyUrl]] is `false`, this method will do nothing.
     *
     * @param array $rules the new rules to be added. Each array element represents a single rule declaration.
     * Please refer to [[rules]] for the acceptable rule format.
     * @param bool $append whether to add the new rules by appending them to the end of the existing rules.
     */
    public function addRules($rules, $append = true)
    {
		//美化开启才有用
        if (!$this->enablePrettyUrl) {
            return;
        }
        $rules = $this->buildRules($rules);
        if ($append) {
            $this->rules = array_merge($this->rules, $rules);
        } else {
            $this->rules = array_merge($rules, $this->rules);
        }
    }

    /**
	  根据配置信息创建URL对象
	  注意是protected,在子类中调用，外部不许调用
     * Builds URL rule objects from the given rule declarations.
     * @param array $rules the rule declarations. Each array element represents a single rule declaration.
     * Please refer to [[rules]] for the acceptable rule formats.
     * @return UrlRuleInterface[] the rule objects built from the given rule declarations
     * @throws InvalidConfigException if a rule declaration is invalid
     */
    protected function buildRules($rules)
    {
        $compiledRules = [];
        $verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';
		//遍历最初的rules
        foreach ($rules as $key => $rule) {
			//字符串
            if (is_string($rule)) {
                $rule = ['route' => $rule];
				//不常见的?:出现了
				//这是要匹配多个逗号分隔的verb或者仅仅有一个verb+空白
                if (preg_match("/^((?:($verbs),)*($verbs))\\s+(.*)$/", $key, $matches)) {
                    $rule['verb'] = explode(',', $matches[1]);
                    // rules that do not apply for GET requests should not be use to create urls
                    if (!in_array('GET', $rule['verb'])) {
                        $rule['mode'] = UrlRule::PARSING_ONLY;
                    }
                    $key = $matches[4];
                }
                $rule['pattern'] = $key;
            }
			//数组形式,合并ruleConfig后再去实例化
            if (is_array($rule)) {
                $rule = Yii::createObject(array_merge($this->ruleConfig, $rule));
            }
			//非指定的接口（接口即规范）抛出异常
            if (!$rule instanceof UrlRuleInterface) {
                throw new InvalidConfigException('URL rule class must implement UrlRuleInterface.');
            }
            $compiledRules[] = $rule;
        }
        return $compiledRules;
    }

    /**
     * 解析用户（客户端）的请求，实则是转移为每个rule对象去解析当前的Request组件，这也是request组件转移到UrlManager的入口
     * Parses the user request.
     * @param Request $request the request component  $request 是组件
     * 返回，数组或布尔。
     * 数组里包含着路由和关联的参数。在enablePrettyUrl为false时，关联的参数总是空。
     * @return array|bool the route and the associated parameters. The latter is always empty
     * 如果不能成功解析，则返回false。
     * if [[enablePrettyUrl]] is `false`. `false` is returned if the current request cannot be successfully parsed.
     */
    public function parseRequest($request)
    {
        //开启时才能美化
        if ($this->enablePrettyUrl) {
            /* @var $rule UrlRule */
			//而开启美化URL的关键，就是遍历rules数组，按顺序每个rule对象去解析当前请求
            foreach ($this->rules as $rule) {
                $result = $rule->parseRequest($this, $request);
				//打个日志
                if (YII_DEBUG) {
                    Yii::trace([
                        'rule' => method_exists($rule, '__toString') ? $rule->__toString() : get_class($rule),
                        'match' => $result !== false,
                        'parent' => null
                    ], __METHOD__);
                }
				//返回解析结果（估计就是路由了）
                if ($result !== false) {
                    return $result;
                }
            }
			//如果开启严格解析。则rule必须解析成功。
			//能够走到这里，说明前面遍历rule，都没有解析成功,故在这里return false。
			//如果未开启严格解析，则会继续往下走，path info将发挥作用
            if ($this->enableStrictParsing) {
                return false;
            }

            Yii::trace('No matching URL rules. Using default URL parsing logic.', __METHOD__);

            $suffix = (string) $this->suffix;
			//Request组件获得path info
            $pathInfo = $request->getPathInfo();
            $normalized = false;
            if ($this->normalizer !== false) {
                $pathInfo = $this->normalizer->normalizePathInfo($pathInfo, $suffix, $normalized);
            }
			//url带有后缀的情况
            if ($suffix !== '' && $pathInfo !== '') {
                $n = strlen($this->suffix);
                if (substr_compare($pathInfo, $this->suffix, -$n, $n) === 0) {
                    $pathInfo = substr($pathInfo, 0, -$n);
                    if ($pathInfo === '') {
                        // suffix alone is not allowed
                        return false;
                    }
                } else {
                    // suffix doesn't match
                    return false;
                }
            }
			//正规化
            if ($normalized) {
                // pathInfo was changed by normalizer - we need also normalize route
                return $this->normalizer->normalizeRoute([$pathInfo, []]);
			//非正规化，直接返回，同样的。数组的第二个元素，也是空的子数组。
            } else {
                return [$pathInfo, []];
            }
        } else {
            //当未开启美化URL时（enablePrettyUrl为false)主要解析queryString中的r参数
            Yii::trace('Pretty URL not enabled. Using default URL parsing logic.', __METHOD__);
            //路由参数名，是由组件的routeParam成员决定的，默认是"r"。
            //从Request组件的getQueryParam方法里获取r参数
            $route = $request->getQueryParam($this->routeParam, '');
            if (is_array($route)) {
                $route = '';
            }
            //第二个参数，永远是空数组
            return [(string) $route, []];
        }
    }

    /**
	* 根据给出的路由和query parameters来创建一个URL（这也是助手方法helpers\Url::to的底层
     * Creates a URL using the given route and query parameters.
     *创建URL，首先需要指定一个字符串形式的路由，比如site/index。需要带query参数时，则使用数组形式
     * You may specify the route as a string, e.g., `site/index`. You may also use an array
     * if you want to specify additional query parameters for the URL being created. The
	 格式如下，例子非常重要
     * array format must be:
     *
     * ```php
     * // generates: /index.php?r=site%2Findex&param1=value1&param2=value2
     * ['site/index', 'param1' => 'value1', 'param2' => 'value2']
     * ```
     *
	 创建带锚的URL
     * If you want to create a URL with an anchor, you can use the array format with a `#` parameter.
     * For example,
     *
     * ```php
     * // generates: /index.php?r=site%2Findex&param1=value1#name
     * ['site/index', 'param1' => 'value1', '#' => 'name']
     * ```
     * 该方法创建URL是创建相对URL，想创建绝对url(带有http主机信息的）则使用[[createAbsoluteUrl()]]方法
     * The URL created is a relative one. Use [[createAbsoluteUrl()]] to create an absolute URL.
     *
     * Note that unlike [[\yii\helpers\Url::toRoute()]], this method always treats the given route
     * as an absolute route.
     *
     * @param string|array $params use a string to represent a route (e.g. `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @return string the created URL
     */
    public function createUrl($params)
    {
        $params = (array) $params;
        $anchor = isset($params['#']) ? '#' . $params['#'] : '';
        unset($params['#'], $params[$this->routeParam]);//删除第二个参数是什么意思？

        $route = trim($params[0], '/'); //去掉开始的斜杠
        unset($params[0]);//删除它吧

        $baseUrl = $this->showScriptName || !$this->enablePrettyUrl ? $this->getScriptUrl() : $this->getBaseUrl();
        //下面开始拼接QueryString
        if ($this->enablePrettyUrl) {
            $cacheKey = $route . '?';
            foreach ($params as $key => $value) {
                if ($value !== null) {
                    $cacheKey .= $key . '&';
                }
            }

            $url = $this->getUrlFromCache($cacheKey, $route, $params);

            if ($url === false) {
                $cacheable = true;
                foreach ($this->rules as $rule) {
                    /* @var $rule UrlRule */
                    if (!empty($rule->defaults) && $rule->mode !== UrlRule::PARSING_ONLY) {
                        // if there is a rule with default values involved, the matching result may not be cached
                        $cacheable = false;
                    }
                    if (($url = $rule->createUrl($this, $route, $params)) !== false) {
                        if ($cacheable) {
                            $this->setRuleToCache($cacheKey, $rule);
                        }
                        break;
                    }
                }
            }

            if ($url !== false) {
                if (strpos($url, '://') !== false) {
                    if ($baseUrl !== '' && ($pos = strpos($url, '/', 8)) !== false) {
                        return substr($url, 0, $pos) . $baseUrl . substr($url, $pos) . $anchor;
                    } else {
                        return $url . $baseUrl . $anchor;
                    }
                } elseif (strpos($url, '//') === 0) {
                    if ($baseUrl !== '' && ($pos = strpos($url, '/', 2)) !== false) {
                        return substr($url, 0, $pos) . $baseUrl . substr($url, $pos) . $anchor;
                    } else {
                        return $url . $baseUrl . $anchor;
                    }
                } else {
                    $url = ltrim($url, '/');
                    return "$baseUrl/{$url}{$anchor}";
                }
            }

            if ($this->suffix !== null) {
                $route .= $this->suffix;
            }
            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                $route .= '?' . $query;
            }

            $route = ltrim($route, '/');
            return "$baseUrl/{$route}{$anchor}";
        } else {
            $url = "$baseUrl?{$this->routeParam}=" . urlencode($route);
            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                $url .= '&' . $query;
            }

            return $url . $anchor;
        }
    }

    /**
     * Get URL from internal cache if exists
     * @param string $cacheKey generated cache key to store data.
     * @param string $route the route (e.g. `site/index`).
     * @param array $params rule params.
     * @return bool|string the created URL
     * @see createUrl()
     * @since 2.0.8
     */
    protected function getUrlFromCache($cacheKey, $route, $params)
    {
        if (!empty($this->_ruleCache[$cacheKey])) {
            foreach ($this->_ruleCache[$cacheKey] as $rule) {
                /* @var $rule UrlRule */
                if (($url = $rule->createUrl($this, $route, $params)) !== false) {
                    return $url;
                }
            }
        } else {
            $this->_ruleCache[$cacheKey] = [];
        }
        return false;
    }

    /**
     * Store rule (e.g. [[UrlRule]]) to internal cache
     * @param $cacheKey
     * @param UrlRuleInterface $rule
     * @since 2.0.8
     */
    protected function setRuleToCache($cacheKey, UrlRuleInterface $rule)
    {
        $this->_ruleCache[$cacheKey][] = $rule;
    }

    /**
     * Creates an absolute URL using the given route and query parameters.
     *
     * This method prepends the URL created by [[createUrl()]] with the [[hostInfo]].
     *
     * Note that unlike [[\yii\helpers\Url::toRoute()]], this method always treats the given route
     * as an absolute route.
     *
     * @param string|array $params use a string to represent a route (e.g. `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @param string|null $scheme the scheme to use for the URL (either `http`, `https` or empty string
     * for protocol-relative URL).
     * If not specified the scheme of the current request will be used.
     * @return string the created URL
     * @see createUrl()
     */
    public function createAbsoluteUrl($params, $scheme = null)
    {
        $params = (array) $params;
        $url = $this->createUrl($params);
        if (strpos($url, '://') === false) {
            $hostInfo = $this->getHostInfo();
            if (strpos($url, '//') === 0) {
                $url = substr($hostInfo, 0, strpos($hostInfo, '://')) . ':' . $url;
            } else {
                $url = $hostInfo . $url;
            }
        }

        return Url::ensureScheme($url, $scheme);
    }

    /**
	* 返回base URL。可以在createUrl()方法中使用
     * Returns the base URL that is used by [[createUrl()]] to prepend to created URLs.
	 默认是调用Request组件的baseurl来实现的
     * It defaults to [[Request::baseUrl]].
	 主要在美化开启，而[[showScriptName]]关闭时使用
     * This is mainly used when [[enablePrettyUrl]] is `true` and [[showScriptName]] is `false`.
     * @return string the base URL that is used by [[createUrl()]] to prepend to created URLs.
     * @throws InvalidConfigException if running in console application and [[baseUrl]] is not configured.
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl === null) {
            $request = Yii::$app->getRequest();
            if ($request instanceof Request) {
				//还是借用Request组件的方法
                $this->_baseUrl = $request->getBaseUrl();
            } else {
                throw new InvalidConfigException('Please configure UrlManager::baseUrl correctly as you are running a console application.');
            }
        }

        return $this->_baseUrl;
    }

    /**
     * Sets the base URL that is used by [[createUrl()]] to prepend to created URLs.
     * This is mainly used when [[enablePrettyUrl]] is `true` and [[showScriptName]] is `false`.
     * @param string $value the base URL that is used by [[createUrl()]] to prepend to created URLs.
     */
    public function setBaseUrl($value)
    {
        $this->_baseUrl = $value === null ? null : rtrim($value, '/');
    }

    /**
     * Returns the entry script URL that is used by [[createUrl()]] to prepend to created URLs.
     * It defaults to [[Request::scriptUrl]].
     * This is mainly used when [[enablePrettyUrl]] is `false` or [[showScriptName]] is `true`.
     * @return string the entry script URL that is used by [[createUrl()]] to prepend to created URLs.
     * @throws InvalidConfigException if running in console application and [[scriptUrl]] is not configured.
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
            $request = Yii::$app->getRequest();
            if ($request instanceof Request) {
				//还是借用Request组件的方法
                $this->_scriptUrl = $request->getScriptUrl();
            } else {
                throw new InvalidConfigException('Please configure UrlManager::scriptUrl correctly as you are running a console application.');
            }
        }

        return $this->_scriptUrl;
    }

    /**
     * Sets the entry script URL that is used by [[createUrl()]] to prepend to created URLs.
     * This is mainly used when [[enablePrettyUrl]] is `false` or [[showScriptName]] is `true`.
     * @param string $value the entry script URL that is used by [[createUrl()]] to prepend to created URLs.
     */
    public function setScriptUrl($value)
    {
        $this->_scriptUrl = $value;
    }

    /**
     * Returns the host info that is used by [[createAbsoluteUrl()]] to prepend to created URLs.
     * @return string the host info (e.g. `http://www.example.com`) that is used by [[createAbsoluteUrl()]] to prepend to created URLs.
     * @throws InvalidConfigException if running in console application and [[hostInfo]] is not configured.
     */
    public function getHostInfo()
    {
        if ($this->_hostInfo === null) {
            $request = Yii::$app->getRequest();
            if ($request instanceof \yii\web\Request) {
				//还是Request组件的方法
                $this->_hostInfo = $request->getHostInfo();
            } else {
                throw new InvalidConfigException('Please configure UrlManager::hostInfo correctly as you are running a console application.');
            }
        }

        return $this->_hostInfo;
    }

    /**
     * Sets the host info that is used by [[createAbsoluteUrl()]] to prepend to created URLs.
     * @param string $value the host info (e.g. "http://www.example.com") that is used by [[createAbsoluteUrl()]] to prepend to created URLs.
     */
    public function setHostInfo($value)
    {
        $this->_hostInfo = $value === null ? null : rtrim($value, '/');
    }
}
