<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

//注册已经定义好的资源包，使得该视图中依赖的css,js等文件可以加载进来
AppAsset::register($this);
?>
<!-- 布局视图文件的开头用beginPage开启新一轮的输出缓冲，故下述的输出语句都不会立即在页面输出来 -->
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 跨站请求伪造也是共享的，故写在了布局视图文件里 -->
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title><!-- 应用View组件的title。这下明白为啥在普通视图中设置了吧， 因为布局视图文件是共用的，所以每个页面的不同，其实
    都是由普通视图文件决定的，所以应该在普通视图文件里设置title,一般不会重复的-->
    <!-- 下面就是head()部分的内容 -->
    <?php $this->head() ?>
</head>
<body>
<!-- 下面的body部分的内容 -->
<?php $this->beginBody() ?>

<div class="wrap">
<!-- 导航栏，也写成了小部件，分别是beigin,echo,end三部分组成 -->
    <?php
    NavBar::begin([
        'brandLabel' => 'My Company',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label' => 'Home', 'url' => ['/site/index']],
            ['label' => 'msg', 'url' => ['/site/msg']],
            ['label' => 'file', 'url' => ['/site/file']],
            ['label' => 'About', 'url' => ['/site/about']],
            ['label' => 'Contact', 'url' => ['/site/contact']],
            Yii::$app->user->isGuest ? (
                ['label' => 'Login', 'url' => ['/site/login']]
            ) : (
                '<li>'
                . Html::beginForm(['/site/logout'], 'post')
                . Html::submitButton(
                    'Logout (' . Yii::$app->user->identity->username . ')',
                    ['class' => 'btn btn-link logout']
                )
                . Html::endForm()
                . '</li>'
            )
        ],
    ]);
    NavBar::end();
    ?>

    <div class="container">
    <!-- 面包屑也做成了一个小部件widget -->
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <!-- 关键的内容，也就是普通视图渲染后的结果，就放到这里了 -->
        <?= $content ?>
    </div>
</div>
<!-- 下面就是footer部分了 -->
<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; My Company <?= date('Y') ?></p>
		<!-- power部分，是直接从Yii助手类而来的 -->
        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>
<!-- 结束body -->
<?php $this->endBody() ?>
</body>
</html>
<!-- 结束整个页面 -->
<?php $this->endPage() ?>
