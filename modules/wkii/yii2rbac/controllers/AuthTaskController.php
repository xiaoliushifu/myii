<?php

namespace Wkii\Yii2Rbac\controllers;

use Wkii\Yii2Rbac\models\AuthItem;
use Wkii\Yii2Rbac\models\Platform;
use Wkii\Yii2Rbac\models\RoleTask;
use Wkii\Yii2Rbac\models\TaskItem;
use Yii;
use Wkii\Yii2Rbac\models\AuthTask;
use yii\data\ActiveDataProvider;
use Wkii\Yii2Rbac\base\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AuthTaskController implements the CRUD actions for AuthTask model.
 */
class AuthTaskController extends Controller
{
    /**
     * Lists all AuthTask models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new AuthTask();

        $dataProvider = $model->search(Yii::$app->request->getQueryParams());

        return $this->render('index', compact('model', 'dataProvider'));
    }

    /**
     * Displays a single AuthTask model.
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
     * Creates a new AuthTask model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AuthTask();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->task_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing AuthTask model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->task_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing AuthTask model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        (new AuthTask())->deleteTask($id);

        return $this->redirect(['index']);
    }

    /**
     * 为任务分配项目权限
     * @param $id
     * @return string
     */
    public function actionAssignItem($id)
    {
        $model = $this->findModel($id);
        $authItem = new AuthItem();
        $platforms = (new Platform())->allPlatform();
        $currentPlatform = Yii::$app->request->getQueryParam('platform_id', Yii::$app->controller->module->platformId);
        //已分配和未分配的项目
        $allocatedItem = $authItem->allocatedItemByTaskID($id, $currentPlatform);
        $unallocatedItem = $authItem->unallocatedItem($id, $currentPlatform);
        //如果是post请求,证明是发送了任务分配操作
        if (Yii::$app->request->isPost) {
            $operate = Yii::$app->request->post('operate');
            $taskItem = new TaskItem();
            if ($operate == 'toleft') {
                //任务分配到角色, 获取发送过来的未分配的任务,一一验证为分配列表,然后写入到数据库中
                $unallocated = Yii::$app->request->post('unallocated', []);
                $pool = array_intersect($unallocated, array_keys($unallocatedItem));
                $taskItem->batchInsert($id, $pool);
            } elseif ($operate == "toright") {
                //已分配的任务移除角色任务列表, 获取要移除已分配列表数据,一一验证,然后从数据库中移除
                $allocated = Yii::$app->request->post('allocated', []);
                $pool = array_intersect($allocated, array_keys($allocatedItem));
                $taskItem->batchDelete($id, $pool);
            }
            //操作之后,进行重新查询
            $allocatedItem = $authItem->allocatedItemByTaskID($id, $currentPlatform);
            $unallocatedItem = $authItem->unallocatedItem($id, $currentPlatform);
        }
        return $this->render('assign-item', compact('model', 'allocatedItem', 'unallocatedItem', 'platforms', 'currentPlatform'));
    }

    /**
     * Finds the AuthTask model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AuthTask the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AuthTask::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
