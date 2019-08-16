<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '角色授权任务';
$this->params['breadcrumbs'][] = ['label' => '授权角色管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\Wkii\Yii2Rbac\RbacAssets::register($this);
?>
<div class="box box-info">
    <div class="box-body">
        <div class="col-xs-12">
            <div class="box-body">
                <?php
                $form = ActiveForm::begin([
                    'action' => ['assign-task', 'id' => $model->primaryKey],
                    'method' => 'GET',
                    'options' => ['class' => 'form-inline', 'id' => 'assign-task-frm'],
                    'encodeErrorSummary' => false,
                ]);
                ?>
                <h3>任务授权：<?= Html::encode($model->role_name) ?></h3>
                按分类过滤
                <?= Html::dropDownList('category_id', $currentCategory, $categories,
                    ['prompt' => '全部分类', 'id' => 'category_id', 'onchange' => 'this.form.submit()']);
                ?>
                <?php $form->end(); ?>
            </div>
            <?php
            ActiveForm::begin([
                'method' => 'POST',
                'options' => ['class' => 'form-inline', 'id' => 'assign-task-frm'],
                'encodeErrorSummary' => false,
            ]);
            ?>
            <div class="col-sm-4" style="margin-top: 20px;">
                <label>已分配的任务：</label>
                <?= Html::dropDownList('allocated', null, $allocatedTask,
                    ['multiple' => true, 'id' => 'allocated', 'size' => 30, 'style' => 'width:360px']); ?>
            </div>
            <div class="col-sm-1" style="padding-top: 200px;text-align: center;">
                <p>
                    <button type="submit" name="operate" value="toleft" class="btn btn-link glyphicon glyphicon-arrow-left"></button>
                </p>
                <p>
                    <button type="submit" name="operate" value="toright" class="btn btn-link glyphicon glyphicon-arrow-right"></button>
                </p>
                <div id="assignMessage"></div>
            </div>

            <div class="col-sm-4" style="margin-top: 20px;">
                <label class="control-label">未分配的任务：</label>
                <?=
                Html::dropDownList('unallocated', null, $unallocatedTask,
                    ['multiple' => true, 'id' => 'unallocated', 'size' => 30, 'style' => 'width:360px']);
                ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>

    </div>
</div>

