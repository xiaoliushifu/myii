<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\AuthItem */

$this->title = '创建授权项目';
$this->params['breadcrumbs'][] = ['label' => '授权项目管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-info">
    <div class="box-body">
        <h1><?= Html::encode($this->title) ?></h1>

        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>
