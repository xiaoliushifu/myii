<?php

namespace Wkii\Yii2Rbac\controllers;

use Wkii\Yii2Rbac\models\AuthItem;
use Yii;
use Wkii\Yii2Rbac\models\TaskItem;
use yii\data\ActiveDataProvider;
use Wkii\Yii2Rbac\base\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TaskItemController implements the CRUD actions for TaskItem model.
 */
class TaskItemController extends Controller
{

    /**
     * Lists all TaskItem models.
     * @param int $item_id
     * @return mixed
     */
    public function actionIndex($item_id)
    {
        $model = $this->findItem($item_id);
        $taskItem = new TaskItem();
        $taskItem->item_id = $item_id;
        $dataProvider = $taskItem->search();

        return $this->render('index', compact('model', 'dataProvider'));
    }

    /**
     * Displays a single TaskItem model.
     * @param integer $task_id
     * @param integer $item_id
     * @return mixed
     */
    public function actionView($task_id, $item_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($task_id, $item_id),
        ]);
    }

    /**
     * Creates a new TaskItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new TaskItem();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'task_id' => $model->task_id, 'item_id' => $model->item_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing TaskItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $task_id
     * @param integer $item_id
     * @return mixed
     */
    public function actionUpdate($task_id, $item_id)
    {
        $model = $this->findModel($task_id, $item_id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'task_id' => $model->task_id, 'item_id' => $model->item_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing TaskItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $task_id
     * @param integer $item_id
     * @return mixed
     */
    public function actionDelete($task_id, $item_id)
    {
        $this->findModel($task_id, $item_id)->delete();

        return $this->redirect(['index', 'item_id' => $item_id]);
    }

    /**
     * Finds the TaskItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $task_id
     * @param integer $item_id
     * @return TaskItem the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($task_id, $item_id)
    {
        if (($model = TaskItem::findOne(['task_id' => $task_id, 'item_id' => $item_id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param $item_id
     * @return null|static
     * @throws NotFoundHttpException
     */
    protected function findItem($item_id)
    {
        if (($model = AuthItem::findOne($item_id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
