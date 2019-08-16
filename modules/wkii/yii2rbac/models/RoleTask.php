<?php

namespace Wkii\Yii2Rbac\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "yii2rbac_role_task".
 *
 * @property integer $role_id
 * @property integer $task_id
 */
class RoleTask extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii2rbac_role_task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['role_id', 'task_id'], 'required'],
            [['role_id', 'task_id'], 'integer'],
            [['role_id', 'task_id'], 'unique', 'targetAttribute' => ['role_id', 'task_id'], 'message' => 'The combination of Role ID and Task ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'role_id' => 'Role ID',
            'task_id' => 'Task ID',
        ];
    }

    /**
     * 查询角色与任务之间的关联
     * @return ActiveDataProvider
     */
    public function search()
    {
        $query = self::find();

        $query->andFilterWhere([
            'task_id' => $this->task_id
        ]);

        $query->with('role');

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(AuthRole::className(), ['role_id' => 'role_id']);
    }
    /**
     * 批量为某个角色分配任务
     * @param $roleId
     * @param $taskIds
     * @return bool
     */
    public function batchInsert($roleId, $taskIds)
    {
        $data = [];
        foreach ($taskIds as $taskId) {
            array_push($data, [$roleId, $taskId]);
        }
        if (empty($data)) {
            return false;
        }
        return $this->getDb()->createCommand()
            ->batchInsert(self::tableName(), ['role_id', 'task_id'], $data)->execute() > 0 ? true : false;
    }

    /**
     * 批量删除已分配任务
     * @param int $roleId  角色ID
     * @param array $taskIds  任务ID集合
     * @return bool
     */
    public function batchDelete($roleId, $taskIds)
    {
        if (!empty($taskIds)) {
            return self::deleteAll(['role_id' => $roleId, 'task_id' => $taskIds]) > 0 ? true : false;
        }
        return false;
    }
}
