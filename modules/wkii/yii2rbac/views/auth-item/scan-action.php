<?php
$this->title = '授权项目扫描: ' . $model->controller;
$this->params['breadcrumbs'][] = ['label' => '授权项目管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\Wkii\Yii2Rbac\RbacAssets::register($this);
?>
<div class="row">
    <div class="col-sm-10">
        <div class="box box-primary">
            <div class="box-body">
                <?php
                $form = \yii\bootstrap\ActiveForm::begin([
                    'method' => 'POST',
                    'options' => ['class' => 'form-inline'],
                    'encodeErrorSummary' => false,
                ]);
                ?>
                <table class="table table-striped table-bordered table-condensed table-hover" id="actionTable">
                    <thead>
                        <th style="width:60px;text-align: center">
                            <?= \yii\helpers\Html::checkbox('check_all', false, ['class' => 'select-all', 'data-class' => 'action']); ?>
                        </th>
                        <th>Action</th>
                        <th style="width:50%;">描述</th>
                        <th class="button-column" style="width:60px;">始终允许</th>
                    </thead>
                    <tbody>
                        <?php foreach ($actions as $action): ?>
                        <tr>
                            <td style="text-align: center"><?= \yii\helpers\Html::checkbox("action[{$action}]", false, ['class' => 'action']) ?></td>
                            <td><?= $action ?></td>
                            <td><?= $model->getActionDoc($model->controller, $action) ?></td>
                            <td><?= \yii\helpers\Html::checkbox("allowed[{$action}]") ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
                if (!empty($actions)) {
                    echo '提示：勾选新增项纳入控制（可同时勾选始终允许），取消勾选已存在项则取消纳入控制。点击保存时已失效项会清除。';
                    echo "<div class=\"form-actions\">" . \yii\bootstrap\Html::button('保存', array(
                        'type' => 'submit',
                        'class' => 'btn btn-primary'
                    )) . "</div>";
                } else {
                    echo '未发现新增加的授权项，现有授权项已经全部添加到数据集中。';
                }
                $form->end();
                ?>

            </div>
        </div>
    </div>

    <div class="col-sm-2">
        <div class="box box-primary">
            <div class="box-body">
                <div class="box-header with-border">
                    <h3 class="box-title">操作列表</h3>
                </div>
                <!-- /.box-header -->
                <ul>
                    <li><a href='<?= \yii\helpers\Url::to(['scan']) ?>'>扫描权限</a></li>
                    <li><a href='<?= \yii\helpers\Url::to(['check']) ?>'>检测方法</a>
                    </li>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>


