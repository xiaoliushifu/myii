<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\bootstrap\ActiveForm;

$this->title = '授权角色管理';
$this->params['breadcrumbs'][] = ['label' => '授权角色管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = '关联用户';
?>

<div class="row">
    <div class="col-sm-12">
        <div class="box">
            <?php
            $form = ActiveForm::begin([
                'method' => 'POST',
                'options' => ['class' => 'form-inline'],
                'encodeErrorSummary' => false,
            ]);
            ?>
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'table table-bordered table-hover'],
                    'layout' => "{items}<div class='col-sm-5'>{summary}</div><div class='col-sm-7'><div class='dataTables_paginate'>{pager}</div></div>",
                    'columns' => \yii\helpers\ArrayHelper::merge([
                        [
                            'class' => \yii\grid\CheckboxColumn::className(),
                            'checkboxOptions' => function ($userRole, $key, $index, $column) use ($model) {
                                return ['value' => $userRole->user_id, 'checked' => $userRole->role_id == $model->primaryKey];
                            },
                        ]
                    ], Yii::$app->controller->module->getUserModel()->getRoleRelatedUserColumn()),
                ]);
                ?>
            </div>
            <div class="box-body">
                <button type="submit" class="btn btn-primary">添加</button>
            </div>
            <?php $form->end() ?>
        </div>
    </div>
</div>

