<?php

namespace Wkii\Yii2Rbac;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Rbac
 * @package Wkii\Yii2Rbac
 * @property array except
 * RBAC进行扫描时,将会忽略的数据,改属性是一个二位数组,
 * 样例:
 * [
 *  'modules' => ['gii', 'debug', ...],
 *  'controllers' => [
 *      'sys\controllers\TestController', // 忽略TestController
 *      'sys\controllers\dir\TestController',
 *      'sys\controllers\TestController' => [*], // 同上
 *      'sys\controllers\TestControlelr' => ['actionIndex', 'actionTest'], // 忽略TestController中的actionIndex, actionTest
 *  ],
 */
class Rbac extends \yii\base\Module
{
    public $controllerNamespace = 'Wkii\Yii2Rbac\controllers';

    /**
     * 平台ID,用于区分多平台数据
     * @var string|int
     */
    public $platformId = null;

    /**
     * 用于查询角色与用户之间的关系, 该参数需要配置用户所用到的model类
     * @var string
     */
    public $userClass;

    private $userInstance = null;


    private $_exceptModules = [];

    private $_exceptControllers = [];

    private $_exceptActions = [];

    /**
     * 配置选项,是否是调试状态,默认不是调试状态
     * @var bool
     */
    public $debug = false;

    public function init()
    {
        parent::init();
        // custom initialization code goes here
    }

    /**
     * RBAC进行扫描时,将会忽略的数据,改属性是一个二位数组,
     * 样例:
     * [
     *  'modules' => ['gii', 'debug', ...],
     *  'controllers' => [
     *      'sys\controllers\TestController', // 忽略TestController
     *      'sys\controllers\dir\TestController',
     *      'sys\controllers\TestController' => [*], // 同上
     *      'sys\controllers\TestControlelr' => ['actionIndex', 'actionTest'], // 忽略TestController中的actionIndex, actionTest
     *  ],
     * ]
     * @var array
     */
    public function setExcept($value)
    {
        // 获取忽略的modules数组
        $this->_exceptModules = isset($value['modules']) ? $value['modules'] : [];

        if (isset($value['controllers']) && is_array($value['controllers'])) {
            foreach ($value['controllers'] as $key => $value) {
                if (is_string($key) && is_array($value)) {
                    if (in_array('*', $value)) {
                        array_push($this->_exceptControllers, $key);
                    } else {
                        foreach ($value as $action) {
                            array_push($this->_exceptActions, $key . "\\" . $action);
                        }
                    }
                } else {
                    array_push($this->_exceptControllers, $value);
                }
            }
        }
    }

    /**
     * 被排除的module
     * @return array
     */
    public function exceptModules()
    {
        return $this->_exceptModules;
    }

    /**
     * 被排除的控制器
     * @return mixed
     */
    public function exceptControllers()
    {
        return $this->_exceptControllers;
    }

    /**
     * 被排除的控制器中的行为action
     * @return mixed
     */
    public function exceptActions()
    {
        return $this->_exceptActions;
    }

    /**
     * 获取用户model实例
     * @return ActiveRecord
     */
    public function getUserModel()
    {
        if ($this->userInstance == null) {
            $this->userInstance = new $this->userClass;
        }
        return new $this->userInstance;
    }

    /**
     * 获取usermodel的主键
     * @return string
     */
    public function getUserPrimaryKey()
    {
        $primaryKey = $this->getUserModel()->primaryKey();

        return $primaryKey[0];
    }
}
