<?php
/**
 * Yii bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

require(__DIR__ . '/BaseYii.php');

/**
* Yii是个助手类，提供常用的框架功能
 * Yii is a helper class serving common framework functionalities.
 *它继承yii\BaseYii来提供实际的实现
 * It extends from [[\yii\BaseYii]] which provides the actual implementation.
 通过写自己的Yii类，可以自定义yii\BaseYii的功能。
 * By writing your own Yii class, you can customize some functionalities of [[\yii\BaseYii]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Yii extends \yii\BaseYii
{
}

spl_autoload_register(['Yii', 'autoload'], true, true);
Yii::$classMap = require(__DIR__ . '/classes.php');
//这一句不但把Container引入，而且还把其父类Component,Object,Configurable都引入了进来到内存中
Yii::$container = new yii\di\Container();
