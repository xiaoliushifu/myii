<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\TaskCategory */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="col-sm-12">

    <?php
    $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}<div class=\"col-sm-5\">{input}</div>",
            'labelOptions' => ['class' => 'col-lg-3 control-label'],
        ]
    ]);
    ?>

    <?= $form->errorSummary($model); ?>

    <?= $form->field($model, 'task_category_name')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php $form->end(); ?>

</div>
