<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\TaskItem */

$this->title = $model->task_id;
$this->params['breadcrumbs'][] = ['label' => 'Task Items', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-item-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'task_id' => $model->task_id, 'item_id' => $model->item_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'task_id' => $model->task_id, 'item_id' => $model->item_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'task_id',
            'item_id',
        ],
    ]) ?>

</div>
