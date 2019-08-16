<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '角色管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-sm-12">

        <div class="box box-primary">
            <div class="box-body">
                <h1><?= Html::encode($this->title) ?></h1>
                <div class="box box-primary">
                    <div class="box-body">
                        <?php
                        $form = \yii\bootstrap\ActiveForm::begin([
                            'action' => ['index'],
                            'method' => 'GET',
                            'options' => ['class' => 'form-inline'],
                            'enableClientScript' => false
                        ]);
                        echo $form->field($model, 'role_name')
                            ->inline()
                            ->label(false)// 不显示label（input前面的字段名，只使用placeholder来显示字段名）
                            ->error(false)// 不在input下方显示该field的错误信息（显示错误信息会在input下方增加一个显示错误信息的<p></p>）
                            ->textInput(['placeholder' => $model->getAttributeLabel('role_name')]);

                        echo $form->field($model, 'status')
                            ->inline()->label(false)->error(false)
                            ->dropDownList(\Wkii\Yii2Rbac\models\AuthRole::$statusEnum);

                        echo Html::submitButton('搜索', ['class' => 'btn btn-primary']);

                        $form->end();
                        ?>
                    </div>
                </div>
                <p>
                    <?= Html::a('创建角色', ['create'], ['class' => 'btn btn-success']) ?>
                </p>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'role_name',
                        'description',
                        [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return \Wkii\Yii2Rbac\models\AuthRole::$statusEnum[$model->status];
                            }
                        ],
                        [
                            'attribute' => 'create_time',
                            'value' => function ($model) {
                                return date("Y-m-d H:i:s", $model->create_time);
                            }
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => "{assign-task} {related} {relate-user} {view} {update} {delete}",
                            'buttons' => [
                                'assign-task' => function ($url, $model) {
                                    return Html::a("", ['assign-task', 'id' => $model->primaryKey], ['class' => 'glyphicon glyphicon-check', 'title' => '分配任务']);
                                },
                                'related' => function ($url, $model) {
                                    return Html::a("", ['user-role/index', 'role_id' => $model->primaryKey], ['class' => 'glyphicon glyphicon-user', 'title' => '已关联用户']);
                                },
                                'relate-user' => function ($url, $model) {
                                    return Html::a("", ['user-role/create', 'role_id' => $model->primaryKey], ['class' => 'glyphicon glyphicon-plus', 'title' => '添加关联用户']);
                                }
                            ]
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
