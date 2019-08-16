<?php

namespace Wkii\Yii2Rbac\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "yii2rbac_user_role".
 *
 * @property integer $user_id
 * @property integer $role_id
 */
class UserRole extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii2rbac_user_role';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'role_id'], 'required'],
            [['user_id', 'role_id'], 'integer'],
            [['user_id', 'role_id'], 'unique', 'targetAttribute' => ['user_id', 'role_id'], 'message' => 'The combination of User ID and Role ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'role_id' => 'Role ID',
        ];
    }

    /**
     * 查询某角色已绑定的用户
     * @return ActiveDataProvider
     */
    public function search()
    {
        $query = self::find();

        $query->andFilterWhere([
            'role_id' => $this->role_id
        ]);

        $query->with('user');

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
    }

    /**
     * 用户查询列表,
     * 该功能使用了"右联"来展示用户信息,并且如果关联中有该用户,则有该用户选中状态
     * @return ActiveDataProvider
     */
    public function searchUsers()
    {
        //获取
        $primaryKey = Yii::$app->controller->module->getUserModel()->tableName() . "." . Yii::$app->controller->module->getUserPrimaryKey();
        $query = self::find()->select([$primaryKey, 'role_id']);
        /* @var \yii\db\ActiveQuery $query */

        $query->joinWith('user', true, 'RIGHT JOIN');

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
    }

    /**
     * 在某角色ID下已经分配的用户IDs
     * @param $roleId
     * @return array
     */
    public function allocatedUserIds($roleId)
    {
        $users = self::find()->select(['user_id'])->where(['role_id' => $roleId])->asArray()->all();

        return ArrayHelper::getColumn($users, 'user_id');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        $primaryKey = Yii::$app->controller->module->getUserPrimaryKey();
        return $this->hasOne(Yii::$app->controller->module->userClass, ['user_id' => $primaryKey]);
    }

    /**
     * 批量为角色非配给用户
     * @param int $roleId
     * @param int[] $userIds
     * @return bool
     * @throws \yii\db\Exception
     */
    public function batchInsertUser($roleId, $userIds)
    {
        $data = [];
        foreach ($userIds as $userId) {
            array_push($data, [$userId, $roleId]);
        }
        if (empty($data)) {
            return false;
        }
        return $this->getDb()->createCommand()
            ->batchInsert(self::tableName(), ['user_id', 'role_id'], $data)->execute() > 0 ? true : false;
    }

    /**
     * 查询用户所对应的角色列表
     * @param $user_id
     * @return array
     * [
     *     'role_id' => 'role_name',
     *      ……
     * ]
     */
    public function getRolesByUserId($user_id)
    {
        $query = self::find()->select(['t1.role_id', 'role_name'])->from(['t1' => self::tableName()]);
        $query->innerJoin(['t2' => AuthRole::tableName()], "t1.role_id = t2.role_id AND t1.user_id = :user_id", [':user_id' => $user_id]);
        $query->where(["t1.user_id" => $user_id]);

        return ArrayHelper::map($query->asArray()->all(), 'role_id', 'role_name');
    }

    /**
     * 批量为某个用户分配角色
     * @param $userId
     * @param $roleIds
     * @return bool
     */
    public function batchInsertRole($userId, $roleIds)
    {
        $data = [];
        $roleIds = (array)$roleIds;
        // 过滤空值
        $roleIds = array_diff($roleIds,['',0]);
        if (empty($roleIds)) {
            return true;
        }
        foreach ($roleIds as $roleId) {
            array_push($data, [$userId, $roleId]);
        }

        return $this->getDb()->createCommand()
            ->batchInsert(self::tableName(), ['user_id', 'role_id'], $data)->execute() > 0 ? true : false;
    }

    /**
     * 批量删除已分配角色
     * @param int $userId 用户ID
     * @param array $roleIds 角色ID
     * @return bool
     */
    public function batchDeleteRole($userId, $roleIds)
    {
        // 过滤空值
        $roleIds = array_diff($roleIds,['',0]);
        if (!empty($roleIds)) {
            return self::deleteAll(['user_id' => $userId, 'role_id' => $roleIds]) > 0 ? true : false;
        }
        return false;
    }

    /**
     * 查询user 对应的 角色的最大权重值
     * @param int $userId 用户ID
     * @return int 0-999
     * @author cjw
     */
    public static function queryUserWeight($userId)
    {
        // 查询用户角色
        $roleList = self::find()->select('role_id')->where(['user_id'=>$userId])->asArray()->all();
        if (empty($roleList)) {
            return 0;
        }
        $roleIds = ArrayHelper::map($roleList,'role_id','role_id');
        // 查询角色最大权重值
        return AuthRole::find()->where(['role_id' => $roleIds])->max('weight');
    }
}
