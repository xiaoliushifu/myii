<?php
if (!function_exists('createScanActionLink')) {
    function createScanActionLink($model, $controller) {
        return \yii\helpers\Html::a("", [
            'scan-action',
            \yii\helpers\Html::getInputName($model, 'controller') => $controller],
            [
                'class' => 'glyphicon glyphicon-search',
                "title" => '扫描控制器操作项'
            ]);
    }
}
if (!function_exists("delActionLink")) {
    function delActionLink($model, $controller) {
        return \yii\helpers\Html::a("", [
            'del-action',
            \yii\helpers\Html::getInputName($model, 'controller') => $controller],
            [
            'class' => 'glyphicon glyphicon-trash',
            'title' => '删除控制器授权项'
        ]);
    }
}
$this->title = '控制器扫描';
$this->params['breadcrumbs'][] = ['label' => '授权项目管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-sm-10">
        <div class="box box-primary">
            <div class="box-body">
                <table class="table grid-view table-hover">
                    <thead>
                    <tr>
                        <th style="width: 30%;">控制器</th>
                        <th style="width: 50%;">描述</th>
                        <th style="width: 20%;" class="button-column">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($controllers as $module => $controller): ?>
                        <?php if (is_numeric($module)): //如果module是数字,证明是app控制器目录,如果不是数字的,证明属于modules中的控制器?>
                            <tr>
                                <td><?= $controller ?></td>
                                <td><?= $model->getControllerDoc($controller) ?></td>
                                <td><?= createScanActionLink($model, $controller) . str_repeat("&nbsp;", 3) . delActionLink($model, $controller) ?></td>
                            </tr>
                        <?php else: //以下是模块扫描?>
                            <tr>
                                <td colspan="3">
                                    <div class="text-center bg-warning">模块: <?php echo $module; ?></div>
                                </td>
                            </tr>
                            <?php foreach ($controller as $module_controller):?>
                                <tr>
                                    <td><?= $module_controller ?></td>
                                    <td><?= $model->getControllerDoc($module_controller) ?></td>
                                    <td><?= createScanActionLink($model, $module_controller) . str_repeat("&nbsp;", 3) . delActionLink($model, $module_controller) ?></td>
                                </tr>
                            <?php endforeach;?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
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
                    <li><a href='<?= \yii\helpers\Url::to(['check']) ?>'>检测方法</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>