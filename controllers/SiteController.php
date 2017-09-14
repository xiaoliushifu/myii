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
    //怎么回事呢？
    //use \TestTrait;
    const EVENT_TEST='testEvent';
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                //声明过滤器
                'class' => AccessControl::className(),
                //默认对该控制器的所有动作，这里通过only数组指定ACF
				//只对logout,about方法起作用，其他方法不受限制。only指出动作的范围，或者约束的对象。
                //具体的对象如何约束。约束规则，还得看rules数组。
                'only' => ['logout','about'],
				//rule里各个字段的匹配顺序，看看filters/AccessRule.php的allows方法就清楚了
				//顺序是：action-->role-->ip-->verb-->controllers-->callback

				//rules数组是针对控制器的某个动作或者某几个动作来划分为一个个具体的rule的。
                'rules' => [
					//第一个规则（只针对logout动作）
                    [
                        //actions指出当前rule规则对logout有用，不写则当前规则对全部动作都起作用
                        'actions' => ['logout'],
						//'controllers'=>[],//适用的控制器，一般在父类控制器中使用才有效
						//'ips'=>[''],//用户ip的配置，默认所有IP
                        'allow' => true,
						//还能配置允许的http方法
						//'verbs' => ['POST'],
                        //roles指出约束的角色，@表示已登录用户，？表示未登录用户，还可以关联rbac的角色
						//为空则代表适用于所有角色
                        'roles' => ['@'],
                    ],
					//第二个规则（只针对about动作）
                    [
                    //actions指出当前规则对about有用
                    'actions' => ['about'],
                    'allow' => true,
                    'roles' => ['@'],
                    ],
					//第三个规则.......
					//从上到下。依次按照规则进行匹配。有一个规则与控制器的动作匹配上了，后续的rule就
					//不再匹配了。
					


                //总结来说，就是 在当前控制器中，已登录的用户，才能去访问about,logout方法
                ],
            ],
            //verbs过滤器，与
            'verbs' => [
                //声明过滤器
                'class' => VerbFilter::className(),
                'actions' => [
                   // 'logout' => ['post'],
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
        ->setFrom('xxxxx@xxxxx.com')
        ->setTo('xxxxxx@qq.com')
        ->setCc('xxxxxxxx@163.com')
        ->setSubject('Message subject')
        ->setTextBody('Plain text content 你好')
        ->setHtmlBody('<b>HTML content</b>联通')
        ->send();
        
//         Yii::$app->mailer->compose()
//         ->setFrom('laoli','xxxxxxx@xxxxxx.com')
//         ->setTo('872140945@qq.com')
//         ->setSubject('Message subject')
//         ->setHtmlBody('<b>HTML content</b>联通2')
//         ->send();
//         $end = microtime(true);
//         $duration = $end-$start;
        return 'mail finished'.$duration;
    }

    /**
     * Login action.
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        //不是我们想到的使用认证User，而是用LoginForm模型，作为中间件和认证User关联
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
    public function actionMsg()
    {
            var_dump(Yii::$app->request->getcookies());
            return $this->render('/Message/msg');
    }
}
