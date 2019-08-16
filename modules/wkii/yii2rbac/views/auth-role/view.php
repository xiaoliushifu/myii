<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Wkii\Yii2Rbac\models\AuthRole */

$this->title = "角色";
$this->params['breadcrumbs'][] = ['label' => '授权角色管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-info">
    <div class="box-body">
        <div class="col-sm-12">
            <h1><?= Html::encode($this->title) ?></h1>


            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'role_name',
                    'description',
                    'weight',
                    [
                        'attribute' => 'status',
                        'value' => \Wkii\Yii2Rbac\models\AuthRole::$statusEnum[$model->status]
                    ],
                    [
                        'attribute' => 'create_time',
                        'value' => date("Y-m-d H:i:s", $model->create_time)
                    ]
                ],
            ]) ?>


            <p>
                <?= Html::a('更新', ['update', 'id' => $model->role_id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('删除', ['delete', 'id' => $model->role_id], [
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
