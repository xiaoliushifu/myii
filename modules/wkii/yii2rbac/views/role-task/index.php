<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = $model->task_name . '的授权用户列表';
$this->params['breadcrumbs'][] = ['label' => '授权角色管理', 'url' => ['auth-task/index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary">
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'table table-bordered table-hover'],
                    'layout' => "{items}<div class='col-sm-5'>{summary}</div><div class='col-sm-7'><div class='dataTables_paginate'>{pager}</div></div>",
                    'columns' => [
                        "role.role_name",
                        [
                            'attribute' => "role.status",
                            'value' => function ($model) {
                                return \Wkii\Yii2Rbac\models\AuthRole::$statusEnum[$model->role->status];
                            }
                        ],
                        [
                            'header' => '操作',
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{delete}',
                        ],
                    ],
                ]);
                ?>
            </div>
        </div>
    </div>
</div>

