<?php
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
                    <tr>
                        <th style="width:60px;text-align: center">
                            <?= yii\helpers\Html::checkbox('check_all', false,
                                ['class' => 'select-all', 'data-class' => 'action']); ?>
                        </th>
                        <th style="width: 35%;">授权项</th>
                        <th>平台</th>
                        <th style="width: 30%">描述</th>
                        <th>状态</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($items as $key => $item) : ?>
                        <tr>
                            <td style="text-align: center">
                                <?= yii\helpers\Html::checkbox("action[{$item->primaryKey}]", false,
                                    ['class' => 'action']); ?>
                            </td>
                            <td><?php echo $item['item_name'] ?></td>
                            <td><?php echo $item->platform->platform_name; ?></td>
                            <td><?php echo $item['description'] ?></td>
                            <td>此权限已经不存在</td>
                        </tr>
                        <?php
                    endforeach;
                    // 判断是否有失效项，如果有则仅显示
                    ?>
                    </tbody>
                </table>
                <?php
                if (empty($items)) :
                    echo '未发现不存在的授权项';
                else :
                    ?>
                    <div class="form-actions">
                        <?php echo \yii\bootstrap\Html::button('删除', array(
                            'type' => 'submit',
                            'class' => 'btn btn-primary',
                            'onclick' => "if(!confirm('删除后不可恢复，确定要删除这些授权项吗?')) return false;"
                        )) ?>
                    </div>
                    <?php
                endif;
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
                </ul>
            </div>
        </div>
    </div>
</div>