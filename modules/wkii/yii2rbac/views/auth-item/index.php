<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '授权项目管理';
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

                echo $form->field($model, 'item_name')
                    ->inline()
                    ->label(false)// 不显示label（input前面的字段名，只使用placeholder来显示字段名）
                    ->error(false)// 不在input下方显示该field的错误信息（显示错误信息会在input下方增加一个显示错误信息的<p></p>）
                    ->textInput(['placeholder' => $model->getAttributeLabel('item_name')]);

                echo Html::submitButton('搜索', ['class' => 'btn btn-primary']);

                $form->end();
                ?>
            </div>
        </div>
        <div class="box box-primary">
            <div class="box-body">
                <h1><?= Html::encode($this->title) ?></h1>

                <p>
                    <?= Html::a('创建项目', ['create'], ['class' => 'btn btn-success']) ?>
                    <?= Html::a('扫描创建项目', ['scan-create'], ['class' => 'btn btn-success']) ?>
                </p>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'item_name',
                            'contentOptions' => [
                                'width' => '40%;'
                            ]
                        ],
                        'platform.platform_name',
                        [
                            'attribute' => 'description',
                            'format' => 'raw',
                            'contentOptions' => [
                                'width' => '30%;'
                            ]
                        ],
                        [
                            'attribute' => 'allowed',
                            'value' => function ($model) {
                                return \Wkii\Yii2Rbac\models\AuthItem::$allowedEnum[$model->allowed];
                            }
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => "{related} {view} {update} {delete}",
                            'buttons' => [
                                'related' => function ($url, $model) {
                                    return Html::a("", ['task-item/index', 'item_id' => $model->primaryKey],
                                        ['class' => 'glyphicon glyphicon-user', 'title' => '已关联任务']);
                                }
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
                    <li><a href='<?= \yii\helpers\Url::to(['scan']) ?>'>扫描权限</a></li>
                    <li><a href='<?= \yii\helpers\Url::to(['check']) ?>'>检测方法</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
