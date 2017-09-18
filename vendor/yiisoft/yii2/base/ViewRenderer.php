<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ViewRenderer is the base class for view renderer classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class ViewRenderer extends Component
{
    /**
	* 渲染一个视图文件
     * Renders a view file.
     *该方法在View组件渲染一个视图文件时调用。
     * This method is invoked by [[View]] whenever it tries to render a view.
	 * 子类必须实现该方法
     * Child classes must implement this method to render the given view file.
     * *$view View视图对象
     * @param View $view the view object used for rendering the file.
	 * $file 视图文件名
     * @param string $file the view file.
	 * $params 传递到视图的变量
     * @param array $params the parameters to be passed to the view file.
     * @return string the rendering result
     */
    abstract public function render($view, $file, $params);
}
