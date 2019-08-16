<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = $model->item_name . '的授权任务列表';
$this->params['breadcrumbs'][] = ['label' => '授权角色管理', 'url' => ['auth-item/index']];
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
                        "task.task_name",
                        [
                            'attribute' => "task.task_category_id",
                            'value' => function ($model) {
                                if ($model->task != null) {
                                    return (new \Wkii\Yii2Rbac\models\TaskCategory())->getCategoryByID($model->task->task_category_id);
                                }
                            }
                        ],
                        'task.description',
                        [
                            'header' => '操作',
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{delete}'
                        ],
                    ],
                ]);
                ?>
            </div>
        </div>
    </div>
</div>

