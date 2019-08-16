<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\RoleTask */

$this->title = 'Create Role Task';
$this->params['breadcrumbs'][] = ['label' => 'Role Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="role-task-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
