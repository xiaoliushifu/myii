<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use yii\base\Component;

/**
 * HtmlResponseFormatter formats the given data into an HTML response content.
 *
 * It is used by [[Response]] to format response data.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HtmlResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    public $contentType = 'text/html';


    /**格式化指定的响应内容，注意参数$response是响应对象，非响应字符串
     * 通过代码可知，所谓格式化Html,其实是设置了一个http响应对象的头字段Content-Type而已，并没有对
     * 实际的响应实体字符串做什么格式转换
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $response->charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);
        if ($response->data !== null) {
            //data直接赋值给content成员属性，难道不检查字符串吗，万一是数组啥的呢？
            //这里其实不判断格式正确与否，而是交给上层去处理
            $response->content = $response->data;
        }
    }
}
