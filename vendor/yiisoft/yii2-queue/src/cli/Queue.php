<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\cli;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApp;
use yii\helpers\Inflector;
use zhuravljov\yii\queue\Queue as BaseQueue;

/**
 * Queue with CLI
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends BaseQueue implements BootstrapInterface
{
    /**
     * @var string command class name
     */
    public $commandClass = Command::class;
    /**
     * @var array of additional options of command
     */
    public $commandOptions = [];
    /**
     * @var callable|null
     * @internal only for command
     */
    public $messageHandler;

    /**
     * @return string command id
     * @throws
     */
    protected function getCommandId()
    {
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        throw new InvalidConfigException('Queue must be an application component.');
    }

    /**
     * 队列作为yii2扩展,必须有一个bootstrap方法初始化扩展，在控制台添加命令（控制器）command控制器
     * 别名是queue,但是真正的class是Command
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getCommandId()] = [
                'class' => $this->commandClass,
                'queue' => $this,
            ] + $this->commandOptions;
        }
    }

    /**
     * 处理任务
     * @inheritdoc
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        if ($this->messageHandler) {
            return call_user_func($this->messageHandler, $id, $message, $ttr, $attempt);
        } else {
            return parent::handleMessage($id, $message, $ttr, $attempt);
        }
    }

    /**
     * @param string|null $id of a message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @return bool
     * @internal only for command
     */
    public function execute($id, $message, $ttr, $attempt)
    {
        return parent::handleMessage($id, $message, $ttr, $attempt);
    }
}