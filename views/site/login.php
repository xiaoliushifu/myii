<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please fill out the following fields to login:</p>

    <?php $form = ActiveForm::begin([
        /**
         * 这个数组里总共有多少个元素，各个元素都是干啥的，其实它都是ActiveForm类的属性映射，
         * 也就是说，我们直接阅读ActiveForm类的属性，就知道这个数组里能有哪些配置了
         */
        'id' => 'login-form',
        'layout' => 'horizontal',
        //fieldConfig给所有的表单项的总体配置，某个表单项的个性配置可以在$form->field()方法里写
        //具体这个fieldConfig都有哪些配置项？相信你也猜到了，它既然是表单项的配置，那么它肯定是ActiveField类的属性映射了
        //具体详细配置请阅读http://www.yiichina.com/doc/api/2.0/yii-widgets-activefield吧
        'fieldConfig' => [
            /**
             * template配置一个表单项的模板
             * {label}就是指<label>xxx</label>标签
             * {input}就是指具体的表单域如<input type='xxx'>或者<select> <textarea>等
             * {error}就是错误模板，一般是一个<div class="help-block help-block-error "></div>。当验证出错误时，可在这里显示错误信息
             * \n是换行符，不会替换为html的<br/>。在真正替换时绝对换行动作，在响应的html原文中可以看到效果，
             * 通过F12--->network-->第一个请求url-----response可以查看出效果。
             */
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>aaa\nbbb",
            //针对{label}标签具体属性的配置
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
            //针对{input}标签具体属性的配置
            'inputOptions' => ['data-dd' => '小说的'],
            //针对{error}标签具体属性的配置
            'errorOptions' => ['data-error' => 'lalal'],
        ],
    ]); ?>

        <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>

        <?= $form->field($model, 'password')->passwordInput() ?>

        <?= $form->field($model, 'rememberMe')->checkbox([
            'template' => "<div class=\"col-lg-offset-1 col-lg-3\">{input} {label}</div>\n<div class=\"col-lg-8\">{error}</div>",
        ]) ?>

        <div class="form-group">
            <div class="col-lg-offset-1 col-lg-11">
                <?= Html::submitButton('Login', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            </div>
        </div>

    <?php ActiveForm::end(); ?>

    <div class="col-lg-offset-1" style="color:#999;">
        You may login with <strong>admin/admin</strong> or <strong>demo/demo</strong>.<br>
        To modify the username/password, please check out the code <code>app\models\User::$users</code>.
    </div>
</div>
