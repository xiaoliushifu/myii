<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '任务管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-sm-10">
        <div class="box box-primary">
            <div class="box-body">
                <?php
                $form = \yii\bootstrap\ActiveForm::begin([
                    'action' => ['index'],
                    'method' => 'GET',
                    'options' => ['class' => 'form-inline'],
                    'enableClientScript' => false
                ]);
                echo $form->field($model, 'task_name')
                    ->inline()
                    ->label(false)// 不显示label（input前面的字段名，只使用placeholder来显示字段名）
                    ->error(false)// 不在input下方显示该field的错误信息（显示错误信息会在input下方增加一个显示错误信息的<p></p>）
                    ->textInput(['placeholder' => $model->getAttributeLabel('task_name')]);

                echo $form->field($model, 'task_category_id')
                    ->inline()->label(false)->error(false)
                    ->dropDownList((new \Wkii\Yii2Rbac\models\TaskCategory())->getAllCategory(), ['prompt' => '全部']);

                echo Html::submitButton('搜索', ['class' => 'btn btn-primary']);

                $form->end();
                ?>
            </div>
        </div>
        <div class="box box-primary">
            <div class="box-body">
                <h1><?= Html::encode($this->title) ?></h1>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        'task_name',
                        [
                            'attribute' => 'task_category_id',
                            'value' => function ($model) {
                                return $model->category->task_category_name;
                            }
                        ],
                        'description',
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => "{assign-item} {related} {view} {update} {delete}",
                            'buttons' => [
                                'assign-item' => function ($url, $model) {
                                    return Html::a("", ['assign-item', 'id' => $model->primaryKey], ['class' => 'glyphicon glyphicon-check', 'title' => '分配项目权限']);
                                },
                                'related' => function ($url, $model) {
                                    return Html::a("", ['role-task/index', 'task_id' => $model->primaryKey], ['class' => 'glyphicon glyphicon-user', 'title' => '已关联角色']);
                                },
                            ]
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="box box-primary">
            <div class="box-body">
                <div class="box-header with-border">
                    <h3 class="box-title">操作列表</h3>
                </div><!-- /.box-header -->
                <ul>
                    <li><a href='<?= \yii\helpers\Url::to(['create']) ?>'>创建任务</a></li>
                    <li><a href='<?= \yii\helpers\Url::to(['task-category/index']) ?>'>任务分类管理</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
