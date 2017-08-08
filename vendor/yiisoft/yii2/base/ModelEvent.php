<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**ModelEvent事件类，代表Model类中的事件对象
 * ModelEvent represents the parameter needed by [[Model]] events.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ModelEvent extends Event
{
    /** 多了一个isValid属性
     * @var bool whether the model is in valid status. Defaults to true.
     * 一个模型通过了验证或者其他某个检测，则认为是valid,有效的
     * A model is in valid status if it passes validations or certain checks.
     */
    public $isValid = true;
}
