<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\RoleTask */

$this->title = 'Update Role Task: ' . ' ' . $model->role_id;
$this->params['breadcrumbs'][] = ['label' => 'Role Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->role_id, 'url' => ['view', 'role_id' => $model->role_id, 'task_id' => $model->task_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="role-task-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
