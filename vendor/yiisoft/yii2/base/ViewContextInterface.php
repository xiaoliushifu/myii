<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/** 
* 该接口就一个方法，就是应该返回视图的绝对路径吧？
 * ViewContextInterface is the interface that should implemented by classes who want to support relative view names.
 * 视图名的前缀路径？是这个意思吗？ 
 * The method [[getViewPath()]] should be implemented to return the view path that may be prefixed to a relative view name.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
interface ViewContextInterface
{
    /**
     * @return string the view path that may be prefixed to a relative view name.
     */
    public function getViewPath();
}
