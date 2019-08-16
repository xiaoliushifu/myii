<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\TaskItem */

$this->title = 'Update Task Item: ' . ' ' . $model->task_id;
$this->params['breadcrumbs'][] = ['label' => 'Task Items', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->task_id, 'url' => ['view', 'task_id' => $model->task_id, 'item_id' => $model->item_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="task-item-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
