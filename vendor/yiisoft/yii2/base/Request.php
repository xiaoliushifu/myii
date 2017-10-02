<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Request表示被应用主体[[Application]]处理的http请求
 * Request represents a request that is handled by an [[Application]].
 *详情先参考官网的介绍
 * For more details and usage information on Request, see the [guide article on requests](guide:runtime-requests).
 * $isConsoleRequest 布尔，该值表示是否当前的请求是通过命令行的（console)
 * @property bool $isConsoleRequest The value indicating whether the current request is made via console.
 * $scriptFile  字符串，入口脚本文件路径（被w/，realpath()处理过的），wl是啥意思？
 * @property string $scriptFile Entry script file path (processed w/ realpath()).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 * 抽象类
 */
abstract class Request extends Component
{
    private $_scriptFile;
    private $_isConsoleRequest;


    /**
     * 解析当前http请求为一个路由和相关联的参数
     * Resolves the current request into a route and the associated parameters.
     * 返回一个数组，第一个元素是路由，第二个元素是相关参数
     * @return array the first element is the route, and the second is the associated parameters.
     */
    abstract public function resolve();

    /**
     * 返回布尔，表名当前请求，是通过命令行不（不是命令行，一般就是浏览器了）
     * Returns a value indicating whether the current request is made via command line
     * @return bool the value indicating whether the current request is made via console
     */
    public function getIsConsoleRequest()
    {
        //是不是命令行的关键，php里有个常量PHP_SAPI==='cli'就是
        return $this->_isConsoleRequest !== null ? $this->_isConsoleRequest : PHP_SAPI === 'cli';
    }

    /**
     * 设置，这种估计不是手动操作的，而是Yii框架自动调用吧。或者该方法无需调用
     * Sets the value indicating whether the current request is made via command line
     * @param bool $value the value indicating whether the current request is made via command line
     */
    public function setIsConsoleRequest($value)
    {
        $this->_isConsoleRequest = $value;
    }

    /**
     * 返回完全脚本路径
     * Returns entry script file path.
     * @return string entry script file path (processed w/ realpath())
     * @throws InvalidConfigException if the entry script file path cannot be determined automatically.
     */
    public function getScriptFile()
    {
        if ($this->_scriptFile === null) {
            //还是从$_SERVER中读取最终的数据来源
            if (isset($_SERVER['SCRIPT_FILENAME'])) {
                $this->setScriptFile($_SERVER['SCRIPT_FILENAME']);
            } else {
                throw new InvalidConfigException('Unable to determine the entry script file path.');
            }
        }

        return $this->_scriptFile;
    }

    /**
     * 设置完全脚本路径
     * Sets the entry script file path.
     * 正常情况下，完全脚本路径读取来自$_SERVER的SCRIPT_FILENAME变量
     * The entry script file path can normally be determined based on the `SCRIPT_FILENAME` SERVER variable.
     * 然而，由于服务端的某些配置，也可能是不正确，不可行的
     * However, for some server configurations, this may not be correct or feasible.
     * 所以，setter是用来手动指定完全脚本路径
     * This setter is provided so that the entry script file path can be manually specified.
     * @param string $value the entry script file path. This can be either a file path or a path alias.
     * @throws InvalidConfigException if the provided entry script file path is invalid.
     */
    public function setScriptFile($value)
    {
        //经过realpath处理过后，赋值给_scriptFile成员属性
        $scriptFile = realpath(Yii::getAlias($value));
        //is_file要检测其有效性
        if ($scriptFile !== false && is_file($scriptFile)) {
            $this->_scriptFile = $scriptFile;
        } else {
            throw new InvalidConfigException('Unable to determine the entry script file path.');
        }
    }
}
