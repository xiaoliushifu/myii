<?php
use yii\widgets\Pjax;
use yii\helpers\Html;
?>

<?php Pjax::begin()?>
<?= Html::a('time',['test/time'],['class'=>'btn btn-lg btn-primary'])?>
<h3>Current Time:<?=$time?></h3>
<h3>Current Time:<?=$mp?></h3>
<?php Pjax::end()?>
