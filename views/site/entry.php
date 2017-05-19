<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

var_dump(Yii::$app->db);
?>
<!--表单开始 -->
<?php $form = ActiveForm::begin();?>
<?= $form->field($model,'name')?>
<?= $form->field($model,'email')?>

<div class="form-group">
    <?= Html::submitButton('Submit',['class'=>'btn btn-primary'])?>
</div>

<!--表单结束-->
<?php ActiveForm::end(); ?>