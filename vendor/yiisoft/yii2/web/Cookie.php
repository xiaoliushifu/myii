<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

/**
 * Cookie类表示一个http请求和响应中发送的cookie,包含name,value,domain,httponly等字段
 * Cookie represents information related with a cookie, such as [[name]], [[value]], [[domain]], etc.
 * cookie一般在http请求和响应中使用，在request和response组件中维护一个cookies属性，它是一个cookie集合，其中的每个成员
 * 就是yii\web\cookie对象。
 *先去官网看一遍再说
 * For more details and usage information on Cookie, see the [guide article on handling cookies](guide:runtime-sessions-cookies).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Cookie extends \yii\base\Object
{
    /**
     * @var string name of the cookie
     */
    public $name;
    /**
     * @var string value of the cookie
     */
    public $value = '';
    /**
     * @var string domain of the cookie
     */
    public $domain = '';
    /**
     * 时间戳，cookie有效期，服务端时间戳，默认0表示浏览器关闭后cookie就失效（不是浏览器选项卡关闭）
     * @var int the timestamp at which the cookie expires. This is the server timestamp.
     * Defaults to 0, meaning "until the browser is closed".
     */
    public $expire = 0;
    /**
     * 服务端web路径,在这个路径下涉及的页面，浏览器都要把cookie发送给服务端
     * @var string the path on the server in which the cookie will be available on. The default is '/'.
     */
    public $path = '/';
    /**
     * 浏览器发送时cookie是否使用安全连接（https吧）
     * @var bool whether cookie should be sent via secure connection
     */
    public $secure = false;
    /**是否应该只允许通过http协议访问
     * @var bool whether the cookie should be accessible only through the HTTP protocol.
     * 默认就是true,意味着不允许被脚本语言如（javascript)访问，这能非常有效的减少通过XSS攻击实现的cookie劫持的危险
     * By setting this property to true, the cookie will not be accessible by scripting languages,
     * such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
     */
    public $httpOnly = true;


    /**魔术方法：__toString()何时被调用？当一个php对象被当做字符串输出时，比如echo $obj;
	* 这里就是echo yii\web\Cookie对象了。
     * Magic method to turn a cookie object into a string without having to explicitly access [[value]].
     *
     * ```php
     * if (isset($request->cookies['name'])) {
     *     $value = (string) $request->cookies['name'];
     * }
     * ```
     *
     * @return string The value of the cookie. If the value property is null, an empty string will be returned.
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
