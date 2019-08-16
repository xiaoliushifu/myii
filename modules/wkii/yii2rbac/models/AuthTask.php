<?php

namespace Wkii\Yii2Rbac\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "yii2rbac_auth_task".
 *
 * @property integer $task_id
 * @property string $task_name
 * @property integer $task_category_id
 * @property string $description
 */
class AuthTask extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii2rbac_auth_task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_category_id'], 'integer'],
            [['task_name'], 'string', 'max' => 64],
            [['description'], 'string', 'max' => 200],
            [['task_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'task_id' => 'Task ID',
            'task_name' => '任务名称',
            'task_category_id' => '分类',
            'description' => '描述',
        ];
    }

    /**
     * 查询所有authtask数据
     * @param array $attributes
     * @return ActiveDataProvider
     */
    public function search($attributes)
    {
        $query = self::find()->innerJoinWith('category');

        $this->load($attributes);

        $query->andFilterWhere([
            'and',
            [
                self::tableName() . '.task_category_id' => $this->task_category_id
            ],
            ['like', 'task_name', $this->task_name]
        ]);

        return new ActiveDataProvider([
            'query' => $query,
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(TaskCategory::className(), ['task_category_id' => 'task_category_id']);
    }

    /**
     * 根据角色ID (和分类ID),查找出来角色对应的任务
     * @param int $id 角色ID
     * @param int $category_id 分类ID
     * @return array
     * 样例:
     * [
     *  ['task_id' => 'task_name'],....
     * ]
     */
    public function allocatedTaskByRoleID($id, $category_id = null)
    {
        $tasks = self::find()->select([self::tableName() . '.task_id as task_id', 'task_name'])
            ->innerJoin(RoleTask::tableName(), self::tableName() . '.task_id' . "=" . RoleTask::tableName() . '.task_id')
            ->where(['role_id' => $id])
            ->andFilterWhere(['task_category_id' => $category_id])
            ->asArray()->all();

        return ArrayHelper::map($tasks, 'task_id', 'task_name');
    }

    /**
     * (根据分类) 获取所有为分配的任务
     * @param int $id 角色ID
     * @param int|null $category_id
     * @return array
     * 样例:
     * [
     *  ['task_id' => 'task_name'],....
     * ]
     */
    public function unallocatedTask($id, $category_id = null)
    {
        $tasks = self::find()->select([self::tableName() . ".task_id as task_id", 'task_name'])
            ->leftJoin(RoleTask::tableName(), [
                'and',
                self::tableName() . '.task_id' . "=" . RoleTask::tableName() . '.task_id',
                ['role_id' => $id]
            ])
            ->where([
                'role_id' => null
            ])
            ->andFilterWhere([
                'task_category_id' => $category_id
            ])->asArray()->all();

        return ArrayHelper::map($tasks, 'task_id', 'task_name');
    }

    /**
     * 删除任务,以及删除任务与角色之间的关联和任务与项目之间的关联
     * 内部会开启事物
     * @param int|array $id     任务ID
     * @return bool
     * @throws \yii\db\Exception
     */
    public function deleteTask($id)
    {
        $transaction = $this->getDb()->beginTransaction();

        try {
            $this->deleteAll(['task_id' => $id]);
            RoleTask::deleteAll(['task_id' => $id]);
            TaskItem::deleteAll(['task_id' => $id]);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
        }

        return false;
    }
}
