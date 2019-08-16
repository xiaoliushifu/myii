<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\AuthRole */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="col-sm-12">

    <?php
    $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}<div class=\"col-sm-5\">{input}<br/>{hint}</div>",
            'labelOptions' => ['class' => 'col-lg-3 control-label'],
        ]
    ]);
    ?>

    <?= $form->errorSummary($model); ?>

    <?= $form->field($model, 'role_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>

    <?= $form->field($model, 'weight')->textInput(['maxlength' => 3])->hint('<div class="text-muted">权重值说明:<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;权重最大值为999,表示超级管理员/rbac管理员 等级别的角色<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;其它普通用户分配小于999的值<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;分配权重大于900的角色的用户,必须开启二次验证<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;权重值范围: 1-999</div>') ?>

    <?= $form->field($model, 'status')->dropDownList(\Wkii\Yii2Rbac\models\AuthRole::$statusEnum) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '更新',
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php $form->end(); ?>

</div>
