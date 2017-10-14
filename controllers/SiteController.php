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
                //声明过滤器的名字
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
                        //（这里的全部就是指上述的logout和about)
                        'actions' => ['logout'],
						//'controllers'=>[],//适用的控制器，一般在父类控制器中使用才有效
						//'ips'=>[''],//用户ip的配置，默认所有IP
						//还能配置允许的http方法
						//'verbs' => ['POST'],
                        //roles指出约束的角色，@表示已登录用户，？表示未登录用户，还可以关联rbac的角色
						//为空则代表适用于所有角色
                        'roles' => ['@'],
                        
                        //allow表示当上述规则（ips,verbs,roles,controllers等）全部匹配后，是允许还是不允许。
                        //设置为true，表示规则全部匹配后，是允许，即放行。
                        //设置为false,表示规则全部匹配后，是不允许，即不放行
                        'allow' => true,
                    ],
					//第二个规则（只针对about动作）
                    [
                    //actions指出当前规则对about有用
                    'actions' => ['about','logout'],
                    'roles' => ['@'],
                    'allow' => true,
                    //总结来说，第二个规则就是 在当前控制器中，已登录的用户，才能去访问about,logout方法
                    ],
					//第三个规则.......
					//从上到下。依次按照规则进行匹配。有一个规则与控制器的动作匹配上了，后续的rule就
					//不再匹配了。
                ],
            ],
            //verbs过滤器
            'verbs' => [
                //指出具体的过滤器类
                'class' => VerbFilter::className(),
                'actions' => [
                   // 'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * 独立动作登记处
     * 独立动作是error和captcha
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            //请求验证码的，是个独立动作，非行内动作
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                //当测试环境时，可以固定验证码，比如8888,testme等
                //'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'fixedVerifyCode' => YII_ENV_DEV? 'testme' : null,
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
        echo "不在视图文件里的输出语句,因为输出量足够小，程序缓存，跟php没有关系，若换个phpinfo(),你看看输出不输出？";
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
            //使用了web\Controller控制器的方法。其实是Response组件的跳转方法（redirect)快捷方式
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

        //控制器的goHome()方法，其实是Response里redirect()方法的快捷方式
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
     * 展示ajax响应的测试,不带布局视图。
     * 响应头部还是html,没有什么区别
     * @param string $message
     */
    public function actionAjax($msg='One',$id=1)
    {
        return $this->renderAjax('/ajax',['msg'=>$msg]);
    }
    
    /**
     * 展示响应里无需布局视图，和ajax那个区别，倒还没有看出来
     * 响应头部还是text/html;到没有啥区别
     * 重点就是没有布局视图。
     * @param string $message
     */
    public function actionPartial($msg='One',$id=1)
    {
        return $this->renderPartial('/ajax',['msg'=>$msg]);
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
    
    /**
     * 
     * 测试视图文件，视图文件的路径是带有斜杠的
     * 这种路径是从应用主体的视图目录下找。
     */
    public function actionMsg()
    {
            //var_dump(Yii::$app->request->getcookies());
            //视图路径，是以单斜杠"/"开始的，则说明从应用主体的viewPath开始。不是控制器的viewPath
            //对于当前控制器来说，它的模块就是应用主体，故视图前面是一个斜杠"/"，或者两个斜杠"//"。都会从
            //basic\views\下开始寻找视图
            return $this->render('/Message/msg');
    }
    
    //响应为下载文件
    public function actionFile()
     {
       $filename='d1.txt';
/** ==============下面是用firebug抓取到的，下载d1.txt时的响应头部==============
HTTP/1.1 200 OK
Date: Sat, 16 Sep 2017 23:50:55 GMT
Server: Apache/2.4.23 (Win64)
Pragma: public
Accept-Ranges: bytes
Expires: 0
Cache-Control: must-revalidate, post-check=0, pre-check=0
Content-Disposition: attachment; filename="d1.txt"
Content-Length: 11
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Content-Type: text/plain;charset=UTF-8
*/
 /**================下面就是响应内容===============
This is NO.
  */
        $filename='test.mkv';
 /*该文件大概19M。由于Response的分块大小默认是8M，故会分三次echo和flush()。但是filebug只抓取到一个完整的19M大小的http响应
这就说明，在web服务器端可以汇总后然后一并发送给浏览器吗？
应该这样理解：虽然会分三次echo和flush，但是Yii框架却只使用一个响应头，而不是三个响应头。

刚使用debug走了一遍，使用的是谷歌浏览器。三次echo都是直接到了浏览器。谷歌浏览器底部的下载进度条分别是8,16,19。这说明web服务器并没有汇总，
在Yii直接echo还没有flush时，就把8M的数据交给web服务器，而web服务器也直接交给浏览器，浏览器就显示进度条。且浏览器标签不再转动。
注意，echo一次后此时文件流数据尚未发送完毕，故服务端进程继续运行，第二次echo又像上次那样向浏览器发送了8M数据，此时进度条显示16。
第三次echo时，浏览器不显示进度条了，直接显示下载完成。但是服务端PHP程序尚未结束，Yii会继续flush,执行shutdown函数，打日志。最终结束本次PHP进程。
浏览器是如何计算进度条的呢？因为header是先发送的，且header中有Content-Length。浏览器应该会计算响应实体的数据量与这个Content-Length的比例吧。
从网络协议来说，http响应是分三次发送到客户端的，可见一次完整的http响应到底分几次（发送几个响应网络包数据）是看数据量的大小才能决定的，因为网络
数据包一次发送的数据量的大小是有上限的，IP是1500大小的限制（可以调小些）,TCP是1460？
刚又换FIleFox浏览器测试了一下，效果跟谷歌浏览器一样，也是每次echo都到了浏览器端，有进度条显示。并未抓取到三个包。
如果想测试看到抓包效果，用别的工具才行吧。
IP可以分片（拆包），tcp是端到端。所以，理论上可以断定：这个debug下载文件的过程，从网络协议上来说，
    就是一次TCP连接，多个IP包，不止3个IP包,因为一个8M大小的数据量，一个ip包肯定不够。
*/

/**===================下面是filebug 下载test.mkv的响应头的原始信息==============
HTTP/1.1 200 OK
Date: Sun, 17 Sep 2017 00:22:25 GMT
Server: Apache/2.4.23 (Win64)
Pragma: public
Accept-Ranges: bytes
Expires: 0
Cache-Control: must-revalidate, post-check=0, pre-check=0
Content-Disposition: attachment; filename="test.mkv"
Content-Length: 19950833
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Content-Type: video/x-matroska

=====================断点续传后，Yii这边的响应头信息==================
在服务端echo一次后，此时浏览器的进度条是8M,于是在浏览器下载进度条上点击暂停。但是服务端继续echo剩下的11M（因浏览器此时是暂停状态，故这11M数据应该被浏览器丢弃了吧）
服务器端应该不知道浏览器拒收了吧？会知道吗？略过。此时服务端进程也结束了。
然后，再次点击浏览器的按钮，继续开始下载，此时浏览器就又发送请求了。从debug视图里看到$_SERVER多了一个HTTP_RANGE请求头字段。值为"838808-",这就意味着浏览器已经
下载了0-838807部分，本次请求是下载从838808开始的部分。下面是针对本次请求，服务端给出的响应。
2017-09-17 10:31:37 [error][application] Content-Range: bytes 8388608-19950832/19950833
2017-09-17 10:31:54 [error][application] Pragma: public
2017-09-17 10:32:24 [error][application] Accept-Ranges: bytes
2017-09-17 10:32:32 [error][application] Expires: 0
2017-09-17 10:32:36 [error][application] Cache-Control: must-revalidate, post-check=0, pre-check=0
2017-09-17 10:32:41 [error][application] Content-Disposition: attachment; filename="test.mkv"
2017-09-17 10:32:45 [error][application] Content-Type: video/x-matroska
2017-09-17 10:32:50 [error][application] Content-Length: 11562225
2017-09-17 10:33:22 [error][application] 8388608
2017-09-17 10:33:39 [error][application] 3173617

由于是第一次看到断点续传的过程，总结一下：
Content-Range：8388608-19950832/19950833        对应请求里的       RANGE : 8388608-
Content-Length: 11562225
其中8388608-19950832是本次响应给客户端的数据范围，/19950833则是完整文件数据总大小
11562225 正好是本次响应数据的大小（19950832-8388608+1）
 */
        $options = ['inline'=>true];
         $storagePath = Yii::getAlias('@app/files');
         // check filename for allowed chars (do not allow ../ to avoid security issue: downloading arbitrary files)
         if (!preg_match('/^[a-z0-9]+\.[a-z0-9]+$/i', $filename) || !is_file("$storagePath/$filename")) {
                 throw new \yii\web\NotFoundHttpException('The file does not exists.');
             }
        //第一个参数是服务端文件系统的路径，第二个参数是展示给浏览器端的文件名。两者有可能相同
             return Yii::$app->response->sendFile("$storagePath/$filename", $filename);
         }
}
