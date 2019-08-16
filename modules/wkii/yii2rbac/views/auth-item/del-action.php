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
                        <th class="button-column" style="width:60px;">状态</th>
                    </thead>
                    <tbody>
                        <?php foreach ($actions as $item_id => $item): ?>
                        <tr>
                            <td style="text-align: center"><?= \yii\helpers\Html::checkbox("action[{$item_id}]", false, ['class' => 'action']) ?></td>
                            <td><?= $item['action'] ?></td>
                            <td><?= $item['description'] ?></td>
                            <td><?= $model->enableAction($model->controller, $item['action']) ? "有效" : "失效"?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
                if (!empty($actions)) {
                    echo "<div class=\"form-actions\">" . \yii\bootstrap\Html::button('删除', array(
                        'type' => 'submit',
                        'class' => 'btn btn-primary',
                        'onclick' => "if(!confirm('删除后不可恢复，确定要删除这些授权项吗?')) return false;"
                    )) . "</div>";
                } else {
                    echo '未发现已纳入管理的授权项';
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


