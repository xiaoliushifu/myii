<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\AuthTask */

$this->title = '更新任务';
$this->params['breadcrumbs'][] = ['label' => '授权任务管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = '更新任务';
?>
<div class="box box-info">
    <div class="box-body">
        <h1><?= Html::encode($this->title) ?></h1>

        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>
