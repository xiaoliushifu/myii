<?php
namespace Wkii\Yii2Rbac;

use yii\web\AssetBundle;

/**
 * Created by PhpStorm.
 * User: chaitao
 * Date: 16/3/2
 * Time: 15:41
 */
class RbacAssets extends AssetBundle
{
    public $sourcePath = '@Wkii/Yii2Rbac/assets';

    public $js = [
        'rbacQuery.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset'
    ];
}