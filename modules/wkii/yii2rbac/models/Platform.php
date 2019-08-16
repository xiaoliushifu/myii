<?php

namespace Wkii\Yii2Rbac\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "yii2rbac_platform".
 *
 * @property integer $platform_id
 * @property string $platform_name
 */
class Platform extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii2rbac_platform';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_name'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'platform_id' => '平台ID',
            'platform_name' => '平台名称',
        ];
    }

    /**
     * 获取平台列表
     * @return array
     */
    public function allPlatform()
    {
        $platform = self::find()->select(['platform_id', 'platform_name'])->asArray()->all();

        return ArrayHelper::map($platform, 'platform_id', 'platform_name');
    }
}
