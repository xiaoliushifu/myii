<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**这个异常类，专门为了视图找不到而定义的。
* 看到没，这就是专业呀
 * ViewNotFoundException represents an exception caused by view file not found.
 *
 * @author Alexander Makarov
 * @since 2.0.10
 */
class ViewNotFoundException extends InvalidParamException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'View not Found';
    }
}
