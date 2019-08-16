<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\TaskCategory */

$this->title = '更新分类: ' . ' ' . $model->task_category_id;
$this->params['breadcrumbs'][] = ['label' => '授权分类管理', 'url' => ['index']];
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
