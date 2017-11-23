<?php

use yii\helpers\Html;
use app\common\widgets\HelloWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Test */

$this->title = 'Test页面';
$this->params['breadcrumbs'][] = ['label' => 'Tests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="test-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
		<?= HelloWidget::widget(['message'=>'你好2呀']); ?>
	</p>

    <div class="form-group">
        <?= Html::submitButton('Login') ?>
    </div>

</div>
