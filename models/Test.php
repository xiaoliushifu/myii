<?php

namespace app\models;

use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "test".
 *
 * @property integer $id
 * @property string $name
 */
class Test extends \yii\db\ActiveRecord
{
    //sluggable行为需要在AR的模型对象里写
    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'title',//从该字段中解析slug
                 'slugAttribute' => 'slug',//最终存储slug值的字段
                'skipOnEmpty' => true,
                'ensureUnique' => true,//验证唯一性
            ],
        ];
    }
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
            [['age', 'name', 'addr'], 'required', 'on' => 'register'],
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
            'title' => 'Name',
            'addr' => '地址',
        ];
    }
}
