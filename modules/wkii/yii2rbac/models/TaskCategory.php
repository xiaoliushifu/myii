<?php

namespace Wkii\Yii2Rbac\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "yii2rbac_task_category".
 *
 * @property integer $task_category_id
 * @property string $task_category_name
 */
class TaskCategory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii2rbac_task_category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_category_name'], 'required'],
            [['task_category_name'], 'string', 'max' => 50],
            [['task_category_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'task_category_id' => 'Task Category ID',
            'task_category_name' => '分类名称',
        ];
    }

    public function getAllCategory()
    {
        $categories = self::find()->asArray()->all();
        return ArrayHelper::map($categories, 'task_category_id', 'task_category_name');
    }

    public function getCategoryByID($id)
    {
        $categories = self::getAllCategory();
        return isset($categories[$id]) ? $categories[$id] : "";
    }
}
