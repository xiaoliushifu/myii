<?php

namespace Wkii\Yii2Rbac\models;

use Yii;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "yii2rbac_task_item".
 *
 * @property integer $task_id
 * @property integer $item_id
 */
class TaskItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii2rbac_task_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_id', 'item_id'], 'required'],
            [['task_id', 'item_id'], 'integer'],
            [['task_id', 'item_id'], 'unique', 'targetAttribute' => ['task_id', 'item_id'], 'message' => 'The combination of Task ID and Item ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'task_id' => 'Task ID',
            'item_id' => 'Item ID',
        ];
    }

    /**
     * 查询任务与项目之间的关联
     * @return ActiveDataProvider
     */
    public function search()
    {
        $query = self::find();

        $query->andFilterWhere([
            'item_id' => $this->item_id
        ]);

        $query->with('task');

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
    public function getTask()
    {
        return $this->hasOne(AuthTask::className(), ['task_id' => 'task_id']);
    }

    /**
     * 批量为某个角色分配任务
     * @param int $taskId
     * @param array $itemIds
     * @return bool
     */
    public function batchInsert($taskId, $itemIds)
    {
        $data = [];
        foreach ($itemIds as $itemId) {
            array_push($data, [$taskId, $itemId]);
        }
        if (empty($data)) {
            return false;
        }
        return $this->getDb()->createCommand()
            ->batchInsert(self::tableName(), ['task_id', 'item_id'], $data)->execute() > 0 ? true : false;
    }

    /**
     * 批量删除已分配任务
     * @param int $taskId
     * @param array $itemIds
     * @return bool
     */
    public function batchDelete($taskId, $itemIds)
    {
        if (!empty($itemIds)) {
            return self::deleteAll(['task_id' => $taskId, 'item_id' => $itemIds]) > 0 ? true : false;
        }
        return false;
    }
}
