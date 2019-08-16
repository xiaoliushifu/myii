<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\RoleTask */

$this->title = $model->role_id;
$this->params['breadcrumbs'][] = ['label' => 'Role Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="role-task-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'role_id' => $model->role_id, 'task_id' => $model->task_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'role_id' => $model->role_id, 'task_id' => $model->task_id], [
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
            'role_id',
            'task_id',
        ],
    ]) ?>

</div>
