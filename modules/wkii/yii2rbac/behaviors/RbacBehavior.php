<?php

namespace Wkii\Yii2Rbac\behaviors;

use Wkii\Yii2Rbac\DbManager;
use Wkii\Yii2Rbac\models\AuthItem;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\base\Controller;
use yii\helpers\Inflector;

/**
 * 验证权限的行为类
 * 在控制器中启用改行为,进行判断用户是否有权限
 * User: chaitao
 * Date: 16/3/4
 * Time: 14:49
 */
class RbacBehavior extends ActionFilter
{
    /**
     * @var Controller
     */
    public $owner;

    /**
     * 发生问题时,展示的view视图, 优先权高于errorContent
     * @var string
     */
    public $errorView;

    /**
     * @var [] 忽略的项目
     */
    private $_ignoreItems = [];

    /**
     * 发生问题时,展示的view视图, 如果没有配置errorView展示改字段数据
     * @var string
     */
    public $errorContent = "Error 403,<br><br>授权失败,您未被授权访问此页面";

    /**
     * 行为验证beforeAction, 填充权限的验证逻辑
     * 1. 匿名用户,跳转到登陆界面,
     * 2. 如果是调试程序,不进行权限判断,全部返回true
     * 3. 判断登陆的用户是否存在权限
     * @param Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        $itemName = (new AuthItem())->format($this->owner->className(), "action" . Inflector::id2camel($action->id));

        //匿名用户可以被访问的页面授权
        if ($this->isIgnoreItem($itemName)) {
            return true;
        }

        // 如果是匿名用户,则跳转到登陆界面
        if (\Yii::$app->user->isGuest) {
            \Yii::$app->user->loginRequired();
        } elseif ($this->getAuthManager()->checkAccess($itemName, \Yii::$app->user->id)) {
            return true;
        } else {
            // 权限验证,如果失败,展示信息,终止行为的继续判断
            echo $this->showMessage();
            exit;
        }

        return false;
    }

    /**
     * @return DbManager
     */
    public function getAuthManager()
    {
        return \Yii::$app->authManager;
    }

    /**
     * 判断是否属于本行为要排除的项目
     * @param $itemName
     * @return bool
     */
    private function isIgnoreItem($itemName)
    {
        //是否属于rbac排除项目
        if (method_exists($this->getAuthManager(), 'isIgnoreItem') && $this->getAuthManager()->isIgnoreItem($itemName)) {
            return true;
        }
        //是否属于本行为忽略的项目
        if (in_array($itemName, $this->getIgnoreItems())) {
            return true;
        }

        return false;
    }

    /**
     * 设置ignoreItems 属性
     * @param $value
     */
    public function setIgnoreItems($value)
    {
        if (!empty($value)) {
            $this->_ignoreItems = (array)$value;
        } else {
            $this->_ignoreItems = [];
        }
    }

    /**
     * 获取ignoreItems 属性
     * @return array
     */
    public function getIgnoreItems()
    {
        return $this->_ignoreItems;
    }

    /**
     * 当权限不满足时,返回的错误信息
     * @return string
     */
    private function showMessage()
    {
        if (!empty($this->errorView)) {
            return $this->owner->render($this->errorView);
        }

        return $this->owner->renderContent($this->errorContent);
    }

}