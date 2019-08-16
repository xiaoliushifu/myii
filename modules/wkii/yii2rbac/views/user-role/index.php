<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\bootstrap\ActiveForm;

$this->title = '授权角色管理';
$this->params['breadcrumbs'][] = ['label' => '授权角色管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->role_name . '的授权用户列表';

?>
<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary">
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'table table-bordered table-hover'],
                    'layout' => "{items}<div class='col-sm-5'>{summary}</div><div class='col-sm-7'><div class='dataTables_paginate'>{pager}</div></div>",
                    'columns' => \yii\helpers\ArrayHelper::merge(Yii::$app->controller->module->getUserModel()->getRoleRelatedUserColumn(), [
                        [
                            'header' => '操作',
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{delete}',
                            'buttons' => [
                                'delete' => function ($url, $model) {
                                    return Html::a("", ['user-role/delete', 'user_id' => $model->user_id,'role_id' => $model->role_id], ['class' => 'glyphicon glyphicon-trash', 'title' => '删除']);
                                },
                            ]
                        ],
                    ]),
                ]);
                ?>
            </div>
        </div>
    </div>
</div>

