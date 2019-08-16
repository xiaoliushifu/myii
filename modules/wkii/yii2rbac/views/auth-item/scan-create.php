<?php
$this->title = '扫描创建授权项目';
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
                    'action' => ['scan-create'],
                    'method' => 'GET',
                    'encodeErrorSummary' => false,
                    'options' => ['class' => 'form-horizontal'],
                    'fieldConfig' => [
                        'template' => "{label}<div class=\"col-sm-6\">{input}</div><div class='col-sm-4'>{error}</div>",
                        'labelOptions' => ['class' => 'col-lg-2 control-label'],
                    ]
                ]);
                echo $form->field($model, 'controller', [
                    'inputTemplate' => '<div class="input-group">{input}<span class="input-group-btn"><button type="submit" class="btn btn-default">扫描</button></span></div>'
                ])->textInput();
                $form->end();
                ?>

                <?php
                $form = \yii\bootstrap\ActiveForm::begin([
                    'method' => 'POST',
                    'options' => ['class' => 'form-horizontal'],
                    'encodeErrorSummary' => false,
                    'fieldConfig' => [
                        'template' => "{label}<div class=\"col-sm-6\">{input}</div><div class='col-sm-4'>{error}</div>",
                        'labelOptions' => ['class' => 'col-lg-2 control-label'],
                    ]
                ]);
                ?>
                <table class="table table-striped table-bordered table-condensed table-hover" id="actionTable">
                    <thead>
                    <th style="width:60px;text-align: center">
                        <?= \yii\helpers\Html::checkbox('check_all', false,
                            ['class' => 'select-all', 'data-class' => 'action']); ?>
                    </th>
                    <th>Action</th>
                    <th style="width:50%;">描述</th>
                    <th class="button-column" style="width:60px;">始终允许</th>
                    </thead>
                    <tbody>
                    <?php foreach ($actions as $action): ?>
                        <tr>
                            <td style="text-align: center"><?= \yii\helpers\Html::checkbox("action[{$action}]", false,
                                    ['class' => 'action']) ?></td>
                            <td><?= $action ?></td>
                            <td><?= $model->getActionDoc($model->controller, $action) ?></td>
                            <td><?= \yii\helpers\Html::checkbox("allowed[{$action}]") ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="col-sm-12">
                    <?php
                    echo $form->field($model, 'module')->textInput(['maxlength' => true]);

                    echo $form->field($model,
                        'platform_id')->dropDownList((new \Wkii\Yii2Rbac\models\Platform())->allPlatform());

                    echo \yii\helpers\Html::button('添加', ['type' => 'submit', 'class' => 'btn btn-primary']);

                    $form->end();
                    ?>
                </div>
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


