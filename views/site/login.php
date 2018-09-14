<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\assets\MyAsset;

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
MyAsset::register($this);

//单独加载的JS代码块，通过参数二指定加载位置在<head>。
$this->registerJs("console.log('this is registerJs')",$this::POS_HEAD, $key = null);
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please fill out the following fields to login:</p>

    <?php $form = ActiveForm::begin([
        
        //如何为form标签单独添加一个属性，哪怕自定义属性？
        //那就来个options呀
        //本质上ActiveForm是一个类，所以如何利用它，就看类的源文件，肯定能看出个123来。
        'options'=>['aaaaa'=>'ddddd'],
        
        
		//下述对AF的参数的配置，也会实际反映到前端yiiActiveForm中
        //不对表单的submit事件进行绑定。
		//如何传递给客户端呢？就是yiiActiveForm()方法的第二个参数
        //'validateOnSubmit'=>false,
        /**
         * 这个数组里总共有多少个元素，各个元素都是干啥的，其实它都是ActiveForm类的属性映射，
         * 也就是说，我们直接阅读ActiveForm类的属性，就知道这个数组里能有哪些配置了
         */
        'id' => 'login-form',
        'layout' => 'horizontal',
        //还有一种布局设置
        //其中{items}指table表格的一行行数据
        //{summary} 指的是"第1-5条，共7条数据."这样的总结字符串
        //{pager}则是各个可点击的分页a标签
        //如果不想分页，那可以只保留{items}即可。
        //'layout' => '{items}<div class="col-sm-2">{summary}</div><div class="col-sm-7"><div class="dataTables_paginate">{pager}</div></div>',
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
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
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
        <!-- 增加验证码  官网给出使用widget方式生成验证码-->
        <!-- widget到底是啥，应该是视图小部件吧，前端的东西 -->
        <?= $form->field($model, 'verifyCode')
//             ->error(false)
            ->label('验证码')
            ->widget(\yii\captcha\Captcha::class, [
                'options' => [
                        'class' => 'form-control',
                        'placeholder' => '验证码',
                        'maxlength' => 6,
                        'style' => 'width:60px;padding:6px;ime-mode:disabled; display:inline;'
                ],
                //修改下模板
                'template' => '{input} {image}',
                'attribute' => 'captcha',
                'imageOptions' => [
                            'class' => 'cpatcha-img',
                            'style' => 'height:34px;cursor:pointer;',
                            'title' => '点击更换一张'
                ]
            ]); ?>

        <?= $form->field($model, 'rememberMe')->checkbox([
            'template' => "<div class=\"col-lg-offset-1 col-lg-3\">{input} {label}</div>\n<div class=\"col-lg-8\">{error}</div>",
        ]) ?>

        <div class="form-group">
            <div class="col-lg-offset-1 col-lg-11">
                <?= Html::submitButton('Login', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            </div>
        </div>
<!-- 顺着widget的end,run方法，发现在用BaseHtml::beginForm()方法里生成csrf隐藏域的 -->
    <?php ActiveForm::end(); ?>

    <div class="col-lg-offset-1" style="color:#999;">
        You may login with <strong>admin/admin</strong> or <strong>demo/demo</strong>.<br>
        To modify the username/password, please check out the code <code>app\models\User::$users</code>.
    </div>
</div>
<!-- 下述使用最原始的方式在视图里引用js文件，这种方式与AssetBundle无关，会直接在页面位置加载，写在哪就从哪里加载。
        与AssetBundle加载js的区别是，AssetBundle全部加载到</body>。
        如果原始加载的js无需其他依赖，可以写在这里。
        如果有需要jquery等其他库的依赖，也需要自己处理依赖关系。
        如果当前js依赖的Query在layout中可以加载进来，就不必在该视图中再次重复了，但是加载顺序问题怎么解决呢？
        因为平时开发时一般情况下个别js文件只是某个视图需要，就样就不能直接去编辑layout，也就不能直接写在</body>前的jquery.js之后，
        这种时候就不要使用原始加载方式了，只能写个AssetBundle，其中的position位置是View::POS_END，depends写好才能解决问题
 -->
<!-- <script type="text/javascript" src="static/js/yz_stock_in.js"></script> -->

<!-- 单独加载JS代码块，通过参数二指定位置是 .POS_LOAD会嵌入到HTMl的</body>里单独一个Jquery的load函数中-->
<?= $this->registerJs("console.log('this is registerJs2')",$this::POS_LOAD, $key = null);?>
<!--  如下的Css文件将加载到<head>区里所有css文件的最上面 , css文件只能加载到<head>中 -->
<?= $this->registerCssFile('static/css/word_day.css'); ?>
<!--  如下的Css文件将按顺序加载到上一个Css文件后面 -->
<?= $this->registerCssFile('static/css/exam-my.css'); ?>
