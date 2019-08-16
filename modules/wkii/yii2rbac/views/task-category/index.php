<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '任务分类管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-sm-10">

        <div class="box box-primary">
            <div class="box-body">
                <h1><?= Html::encode($this->title) ?></h1>

                <p>
                    <?= Html::a('创建任务分类', ['create'], ['class' => 'btn btn-success']) ?>
                </p>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'task_category_name',

                        ['class' => 'yii\grid\ActionColumn'],
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
                    <li><a href='<?= \yii\helpers\Url::to(['auth-task/create']) ?>'>创建任务</a></li>
                    <li><a href='<?= \yii\helpers\Url::to(['auth-task/index']) ?>'>任务管理</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
