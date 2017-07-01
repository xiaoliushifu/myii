<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "test".
 *
 * @property integer $id
 * @property string $name
 */
class Test extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			//这一行没有on，那就是所有场景都适用
            [['name'], 'string', 'max' => 20],
			//下面这一行，就是属于register场景下的验证的，会生成required验证对象。
			['storage_name','org_id','is_open','!org_name'], 'required','on'=>'register'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }
}
