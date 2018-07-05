<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Component;
use yii\helpers\Json;

/**
 * JsonResponseFormatter 格式工具类，用来把data转换成JSON或者JSONP的响应内容,
 * 注意，JSONP和JSON虽然名字相近，但其实是两种相差很远的概念
 * JsonResponseFormatter formats the given data into a JSON or JSONP response content.
 * 需要传递Response参数
 * It is used by [[Response]] to format response data.
 *
 * 这个工具类还可以添加[[encodeOptions]]和[[prettyPrint]]。在response组件的配置数组里，可以向下面那样配置：
 * To configure properties like [[encodeOptions]] or [[prettyPrint]], you can configure the `response`
 * application component like the following:
 *
 * ```php
 * 'response' => [
 *     // ...
 *     'formatters' => [
 *         \yii\web\Response::FORMAT_JSON => [
 *              'class' => 'yii\web\JsonResponseFormatter',
 *              'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
 *              // ...
 *         ],
 *     ],
 * ],
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class JsonResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
	 * 默认不使用JSONP响应格式。当这个属性是true,也就意味着是JSONP格式的响应，那么此时的data成员必须是一个数组
     * @var bool whether to use JSONP response format. When this is true, the [[Response::data|response data]]
	 * 这个数组包含 data元素和callback元素。后者（callback)应该是一个JS的函数名，前者（data）则是这个JS函数的参数。
     * must be an array consisting of `data` and `callback` members. The latter should be a JavaScript
     * function name while the former will be passed to this function as a parameter.
     */
    public $useJsonp = false;
    /**
	 *  使用JSON格式的响应，会使用json_encode()php函数，这个属性可以配置使用这个php函数时的第二个参数
     * @var int the encoding options passed to [[Json::encode()]]. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     * This property has no effect, when [[useJsonp]] is `true`.
     * @since 2.0.7
     */
    public $encodeOptions = 320;
    /**
	 * 是否美化输出，在调试模式时，非常有用。 
     * @var bool whether to format the output in a readable "pretty" format. This can be useful for debugging purpose.
	 * 可以试试。
     * If this is true, `JSON_PRETTY_PRINT` will be added to [[encodeOptions]].
     * Defaults to `false`.
     * This property has no effect, when [[useJsonp]] is `true`.
     * @since 2.0.7
     */
    public $prettyPrint = false;


    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        if ($this->useJsonp) {
            $this->formatJsonp($response);
        } else {
            $this->formatJson($response);
        }
    }

    /**
	* 其实就两个内容，一个是Content-Type :application/json; charset=UTF-8。
	* 再一个就是使用php原生函数json_encode()把data编码后赋值给content。
     * Formats response data in JSON format.
     * @param Response $response
     */
    protected function formatJson($response)
    {
        $response->getHeaders()->set('Content-Type', 'application/json; charset=UTF-8');
        if ($response->data !== null) {
            $options = $this->encodeOptions;
            if ($this->prettyPrint) {
                $options |= JSON_PRETTY_PRINT;
            }
			//在这里也不是直接写的json_encode，而是又使用了助手类yii\helpers\Json。
			//可见php面向对象的封装性，无处不在呀
            $response->content = Json::encode($response->data, $options);
        }
    }

    /**
	* JSONP的Content-Type是application/javascript; charset=UTF-8，与JSON不一样哟！
     * Formats response data in JSONP format.
     * @param Response $response
	 *注意，如果服务端响应jsonp的话，action方法需要返回数组，且数组包含data和callback两个下标的关联数组
	 *做钉钉微应用时，下滑组件使用了jsonp格式的数据交互用到了这个知识点
     */
    protected function formatJsonp($response)
    {
        $response->getHeaders()->set('Content-Type', 'application/javascript; charset=UTF-8');
        if (is_array($response->data) && isset($response->data['data'], $response->data['callback'])) {
			//此时的内容，就是一个js函数的调用。callback是函数名，data则是参数。
            $response->content = sprintf('%s(%s);', $response->data['callback'], Json::htmlEncode($response->data['data']));
        } elseif ($response->data !== null) {
            $response->content = '';
            Yii::warning("The 'jsonp' response requires that the data be an array consisting of both 'data' and 'callback' elements.", __METHOD__);
        }
    }
}
