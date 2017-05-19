<?php

namespace app\controllers;

#导入基准父控制器
use yii\web\Controller;
//导入分页组件（小部件）
use yii\data\Pagination;
//导入模型类
use app\models\Country;

class CountryController extends Controller
{
    public function actionIndex()
    {
        
        $query = Country::find();
        
        $pagination = new Pagination([
            'defaultPageSize'=>5,
            'totalCount'=>$query->count(),
        ]);
        
        $countries = $query->orderBy('name')->offset($pagination->offset)->limit($pagination->limit)->all();
        
        return $this->render('index',[
            'countries'=>$countries,
            'pagination'=>$pagination,
            
        ]);
    }
}
