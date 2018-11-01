<?php

namespace app\controllers;

use Yii;
use app\models\Test;
use app\models\TestSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\common\job\DownloadJob;

/**
 * TestController implements the CRUD actions for Test model.
 */
class TestController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    //控制器第一个调用的方法
    public function init()
    {
//        var_dump(__FUNCTION__);
    }
    
    //第二个调用的方法，且返回false时后续不再执行，直接返回响应
    public function beforeAction($action)
    {
		return true;
    }
    /**
     * Lists all Test models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Test model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Test model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Test();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Test model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Test model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Test model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Test the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Test::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

	public function actionTest()
	{
		//$cache = Yii::$app->getCache();
		//如果使用DbCache的话，会报重复的key,所以在使用上，应该每次先判断key存在否，比如$cache->exists('hello');
		//$cache->set('hello','i am a boy');
		//var_dump($cache->get('hello'));
		/*
		$db = Yii::$app->getDb();

		Yii::beginProfile('b');
		$result = $db->cache(function ($db) {
			return $db->createCommand('SELECT * FROM t2 limit 100000')->queryAll();
		});
		Yii::endProfile('b');
		*/
		return $this->render('test');
	}

    public function actionTime()
    {
        return $this->render('_time',['time'=>date("h:i:s"),'mp'=>'tt']);
    }

    public function actionPjax($_pjax)
    {
        return $this->renderAjax('_time',['time'=>date("h:i:s"),'mp'=>$_pjax]);
    }

    /**
     * 生成一个任务并把它加入到队列里，返回队列里到id
     * 这是队列操作的第一步
     */
    public function actionQueue()
    {


        //加入队列里
        return Yii::$app->queue->push(new DownloadJob([
            'url' => 'http://example.com/image.jpg',
            'file' => '/tmp/image.jpg',
        ]));
    }

    /**
     * 加入到队列里，返回队列里到id
     */
    public function actionQueueDelay()
    {

        //先绑定EVENT_BEFORE_EXEC事件。该事件在这里绑定是无效的，因为触发执行是在控制台，这是两个PHP进程
        //为调试起见，在执行execute方法之前退出，观察reserved_at字段的状态
//        Yii::$app->queue->on(Queue::EVENT_BEFORE_EXEC, function ($event) {
//            \Yii::error($event,'eventObj');
//            $event->handled = true;//阻止后续的执行
//        });

        //加入队列里,延迟3分钟后再执行
        return Yii::$app->queue->delay(3*60)->push(new DownloadJob([
            'url' => 'http://example.com/image.jpg',
            'file' => '/tmp/image.jpg',
        ]));
    }
}
