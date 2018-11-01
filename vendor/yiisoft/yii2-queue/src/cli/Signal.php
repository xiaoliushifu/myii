<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\cli;

/**
 * Process Signal Helper
 * 暂时还不清楚它在队列处理中扮演了啥角色
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Signal
{
    private static $exit = false;

    /**
     * Checks exit signals
     * @return bool
     */
    public static function isExit()
    {
        if (function_exists('pcntl_signal')) {
            // Installs a signal handler
             //声明为静态属性（默认public)
            static $handled = false;
            if (!$handled) {
                //为三个信号（[SIGTERM, SIGINT, SIGHUP]）安装信号处理器，暂略
                foreach ([SIGTERM, SIGINT, SIGHUP] as $signal) {
                    pcntl_signal($signal, function () {
                        static::$exit = true;
                    });
                }
                $handled = true;
            }

            // Checks signal
            if (!static::$exit) {
                //调用等待信号的处理器
                pcntl_signal_dispatch();
            }
        }

        return static::$exit;
    }
}