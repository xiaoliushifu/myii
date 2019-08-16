<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\TaskItem */

$this->title = 'Create Task Item';
$this->params['breadcrumbs'][] = ['label' => 'Task Items', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-item-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
