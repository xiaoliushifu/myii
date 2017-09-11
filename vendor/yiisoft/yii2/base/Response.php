<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Response represents the response of an [[Application]] to a [[Request]].
 *
 * For more details and usage information on Response, see the [guide article on responses](guide:runtime-responses).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0

 * 看到 response也是组件
 * 它是web | console两个response的父类
 */
class Response extends Component
{
    /**
	 * 退出码，范围是0-254。0代表没有问题，成功退出。
     * @var int the exit status. Exit statuses should be in the range 0 to 254.
     * The status 0 means the program terminates successfully.
     */
    public $exitStatus = 0;


    /**
	 * 发送响应给客户端（浏览器，命令行终端） 
     * Sends the response to client.
     */
    public function send()
    {
    }

    /**
	 * 清除所有输出缓存区的内容
     * Removes all existing output buffers.
     */
    public function clearOutputBuffers()
    {
        // the following manual level counting is to deal with zlib.output_compression set to On
		//有可能调用多次ob_start()来开启多级别的输出缓冲（嵌套），ob_get_level可以返回嵌套的层数
		//每一层里的输出缓冲都可以调用ob_end_clean，或者ob_clean来关闭当前的输出缓冲区。
		//按照栈的方式开启和关闭，后开启的先关闭。
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }
}
