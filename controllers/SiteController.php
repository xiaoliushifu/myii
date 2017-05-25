<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\EntryForm;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     * //过滤器本质上是一类特殊的 行为(方法)，所以使用过滤器和 使用 行为一样
     * 预过滤器
     * 后过滤器
     * 依次执行过滤器，其中某个过滤器返回false,后续过滤器不再执行
     */
    public function behaviors()
    {
        return [
            'access' => [
                //声明过滤器
                'class' => AccessControl::className(),
                //默认对该控制器的所有动作，这里通过only只许logout方法使用
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                //声明过滤器
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionMail()
    {
        
//         Yii::$app->mailer->compose('html',[
//             'title'=>'标题啊'
//         ])//compose参数可以使用视图和传参
        Yii::$app->mailer->compose()
        ->setFrom('liumingwei@yunzongnet.com')
        ->setTo('872140945@qq.com')
        ->setSubject('Message subject')
        ->setTextBody('Plain text content')
        ->setHtmlBody('<b>HTML content</b>')
        ->send();
        
        //return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
    
    /**
     * Display a message.
     * 
     * @param string $message
     */
    public function actionSay($message='Hello')
    {
        return $this->render('say',['message'=>$message]);
    }
    
    /**
     * 
     */
    public function actionEntry()
    {
        $m = new EntryForm();
        if ($m->load(Yii::$app->request->post()) && $m->validate()) {
            //接收提交数据进行处理
            return $this->render('entry-confirm',['model'=>$m]);
        } else {
            return $this->render('entry',['model'=>$m]);
        }
    }
}
