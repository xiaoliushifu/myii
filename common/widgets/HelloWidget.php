<?php
namespace app\common\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class HelloWidget extends Widget
{
    public $message;

	//写个小部件，简单就是两个方法OK,init。run
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = 'Hello World';
        }
		ob_start();
    }

    public function run()
    {
		$content = ob_get_clean();//获得缓冲区的内容，然后关闭（当前）缓冲区
        return Html::encode($content);
    }
}