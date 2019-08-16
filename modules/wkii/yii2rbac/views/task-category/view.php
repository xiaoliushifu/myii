<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\TaskCategory */

$this->title = "分类信息";
$this->params['breadcrumbs'][] = ['label' => '分类管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-info">
    <div class="box-body">
        <div class="col-sm-12">
            <h1><?= Html::encode($this->title) ?></h1>

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'task_category_name',
                ],
            ]) ?>

            <p>
                <?= Html::a('更新', ['update', 'id' => $model->task_category_id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('删除', ['delete', 'id' => $model->task_category_id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => '确定删除该条数据?',
                        'method' => 'post',
                    ],
                ]) ?>
            </p>
        </div>
    </div>
</div>
