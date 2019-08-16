<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\AuthItem */

$this->title = "授权项目查看";
$this->params['breadcrumbs'][] = ['label' => '项目管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-info">
    <div class="box-body">
        <div class="col-sm-12">
            <h1><?= Html::encode($this->title) ?></h1>

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'item_name',
                    [
                        'attribute' => 'module',
                        'value' => $model->module ? : "无"
                    ],
                    'controller',
                    'action',
                    [
                        'attribute' => 'platform_id',
                        'value' => $model->platform->platform_name
                    ],
                    [
                        'attribute' => 'description',
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'allowed',
                        'value' => $model::$allowedEnum[$model->allowed]
                    ],
                ],
            ]) ?>

            <p>
                <?= Html::a('更新', ['update', 'id' => $model->item_id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('删除', ['delete', 'id' => $model->item_id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => '确定删除该条记录吗?',
                        'method' => 'post',
                    ],
                ]) ?>
            </p>

        </div>
    </div>
</div>
