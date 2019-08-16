<?php

namespace Wkii\Yii2Rbac;

use yii;
use Wkii\Yii2Rbac\models\Platform;
use yii\bootstrap\Html;
use yii\caching\Cache;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

/**
 * Created by PhpStorm.
 * User: chaitao
 * Date: 16/3/4
 * Time: 17:55
 * @property Rbac $rbacModule
 */
class DbManager extends \yii\rbac\DbManager
{
    /**
     * 在配置文件中 所配置的 模块名称
     * @var string
     */
    public $rbacModuleName = 'rbac';
    /**
     * @var Rbac
     */
    protected $_rbacModule = null;

    /**
     * @var Cache
     */
    public $cache = 'cache';

    /**
     * @var string
     */
    public $cacheKey = 'yii2rbac';

    /**
     * 检查用户是否有该项目的权限
     * 查询用户的角色roles
     * 根据roles,获取roles下的所有任务tasks
     * 得到tasks就可以得到项目条目,从而进行判断是否有该权限
     * @param int|string $itemName     样例:'sys\controllers\TestController\actionIndex'
     * @param int $userId         用户ID
     * @param array $params
     * @return bool
     */
    public function checkAccess($itemName, $userId, $params = [])
    {
        if ($userId == null) {
            return false;
        }
        // 如果是测试状态,那么直接允许
        if ($this->rbacModule->debug) {
            return true;
        }

        // 判断用户权重,是否可以操作rbac
        if (stripos($itemName,'yii2rbac') !== false && Yii::$app->user->roleWeight != 999) {
            return false;
        }

        // 判断总是允许的权限中是否存在该权限项目
        $allowedItems = self::getAllowedItems();
        if (isset($allowedItems[$itemName])) {
            return true;
        }

        // 查看是否给该用户分配该项目权限
        $items = self::getItems($userId);
        if (isset($items[$itemName])) {
            return true;
        }

        return false;
    }

    /**
     * 是否是属于忽略的项目
     * @param $itemName
     * @return bool
     */
    public function isIgnoreItem($itemName)
    {
        // 是否是忽略的controller
        foreach ($this->rbacModule->exceptControllers() as $controller) {
            if (strpos($itemName, $controller) === 0) {
                return true;
            }
        }
        // 是否是忽略的action
        foreach ($this->rbacModule->exceptActions() as $action) {
            if ($itemName == $action) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取当前rbac的module
     * @return Rbac
     * @throws \Exception
     */
    public function getRbacModule()
    {
        $this->_rbacModule = \Yii::$app->getModule($this->rbacModuleName);
        if (!($this->_rbacModule instanceof Rbac)) {
            throw new \Exception(self::class . " 配置的rbacModuleName并非是 " . Rbac::class . " 或继承与它的实例");
        }

        return $this->_rbacModule;
    }

    /**
     * 移除某个用户的所有权限缓存, 获取全部平台, 把每个平台的缓存依次移除
     * @param mixed $userId
     * @return bool
     */
    public function revokeAll($userId)
    {
        $platforms = self::getAllPlatform();

        foreach ($platforms as $platform) {
            $cacheKey = self::getCacheKey($userId, $platform);
            if (!$this->cache->delete($cacheKey)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取用户所有已分配的项目权限,返回值为权限名称做键
     * @param int $user_id
     * @return array|mixed
     * 样例:
     * [
     *      'sys\controllers\TestControlelr\actionIndex' => 0,
     *      'sys\controllers\TestControlelr\actionCreate' => 1
     * ]
     */
    protected function getItems($user_id)
    {
        $result = $this->getAuthItemsByUserId($user_id, $this->rbacModule->platformId);

        $result = array_flip($result);

        return $result;
    }

    /**
     * 获取总是被允许的权限功能
     * @return array
     * 样例:
     * [
     *      'sys\controllers\TestControlelr\actionIndex' => 0,
     *      'sys\controllers\TestControlelr\actionCreate' => 1
     * ]
     */
    protected function getAllowedItems()
    {
        //总是被允许的项目权限应该是一样的只要是登陆用户都可以共享
        $result = $this->getAllowedItemsByPlatformId($this->rbacModule->platformId);
        $result = array_flip($result);
        return $result;
    }

    /**
     * 根据用户ID获取,平台ID下的所有授权项目
     * @param $userId
     * @param $platformId
     * @return array|mixed
     * @throws \Exception
     */
    public function getAuthItemsByUserId($userId, $platformId)
    {
        $cacheKey = $this->getCacheKey($userId, $platformId);

        $platforms = self::getAllPlatform();
        if (!in_array($platformId, $platforms)) {
            throw new \Exception("不存在的平台ID");
        }

        if (($result = $this->cache->get($cacheKey)) === false) {
            $sql = <<<sql
SELECT item.item_name, item.platform_id FROM yii2rbac_auth_item as item
LEFT JOIN yii2rbac_task_item AS taskItem ON item.item_id = taskItem.item_id
LEFT JOIN yii2rbac_auth_task AS task ON task.task_id = taskItem.task_id
LEFT JOIN yii2rbac_role_task AS roleTask ON roleTask.task_id = task.task_id
LEFT JOIN yii2rbac_role AS role ON role.role_id = roleTask.role_id
LEFT JOIN yii2rbac_user_role AS userRole ON userRole.role_id = role.role_id
WHERE userRole.user_id = :user_id;
sql;
            $rows = $this->db->createCommand($sql, ['user_id' => $userId])->queryAll();
            $items = [];
            foreach ($rows as $row) {
                $items[$row['platform_id']][] = $row['item_name'];
            }

            //拿到所有平台数据,依次将授权项目,存入缓存
            foreach ($platforms as $platform) {
                $tempCacheKey = $this->getCacheKey($userId, $platform);
                $this->cache->set($tempCacheKey, isset($items[$platform]) ? $items[$platform] : []);
            }

            //获取,此次调用函数所需要的平台授权项目数据
            $result = isset($items[$platformId]) ? $items[$platformId] : [];
        }

        return $result;

    }

    /**
     * 获取平台ID下的始终允许的授权项目
     * @param $platformId
     * @return array|mixed
     * @throws \Exception
     */
    public function getAllowedItemsByPlatformId($platformId) {
        $cacheKey = $this->getCacheKey(null, $platformId);

        $platforms = self::getAllPlatform();
        if (!in_array($platformId, $platforms)) {
            throw new \Exception("不存在的平台ID");
        }

        if (($result = $this->cache->get($cacheKey)) === false) {
            $sql = <<<sql
SELECT item.item_name, item.platform_id FROM yii2rbac_auth_item as item
WHERE allowed = 1;
sql;

            $rows = $this->db->createCommand($sql)->queryAll();
            $items = [];
            foreach ($rows as $row) {
                $items[$row['platform_id']][] = $row['item_name'];
            }

            //拿到所有平台数据,依次将授权项目,存入缓存
            foreach ($platforms as $platform) {
                $tempCacheKey = $this->getCacheKey(null, $platform);
                $this->cache->set($tempCacheKey, isset($items[$platform]) ? $items[$platform] : []);
            }

            //获取,此次调用函数所需要的平台授权项目数据
            $result = isset($items[$platformId]) ? $items[$platformId] : [];
        }

        return $result;
    }

    /**
     * 获取缓存键
     * @param $userId
     * @param $platformId
     * @return string
     * @throws \Exception
     */
    private function getCacheKey($userId, $platformId)
    {
        if (empty($userId) && empty($platformId)) {
            throw new \Exception("userId 和 platformId 不可以同时为空");
        }
        return $this->cacheKey . Html::encode($userId) . Html::encode($platformId);
    }

    /**
     * 获取所有平台 ID数组
     * @return array
     */
    private function getAllPlatform()
    {
        $cacheKey = $this->cacheKey . __FUNCTION__;
        if (($result = $this->cache->get($cacheKey)) === false) {
            $result = Platform::find()->select('platform_id')->asArray()->all();
            $this->cache->set($cacheKey, $result);
        }

        return ArrayHelper::getColumn($result, 'platform_id');
    }
}