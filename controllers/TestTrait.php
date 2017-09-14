<?php
trait TestTrait {
    
    public function actionTest()
    {
        echo "hello";
        //var_dump(Yii::$app->request->getcookies());
        return $this->render('/Message/msg');
    }
}

