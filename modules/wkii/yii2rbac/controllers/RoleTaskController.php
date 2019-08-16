<?php

namespace Wkii\Yii2Rbac\controllers;

use Wkii\Yii2Rbac\models\AuthRole;
use Wkii\Yii2Rbac\models\AuthTask;
use Wkii\Yii2Rbac\models\UserRole;
use Yii;
use Wkii\Yii2Rbac\models\RoleTask;
use yii\data\ActiveDataProvider;
use Wkii\Yii2Rbac\base\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RoleTaskController implements the CRUD actions for RoleTask model.
 */
class RoleTaskController extends Controller
{
    /**
     * Lists all RoleTask models.
     * @param int $task_id
     * @return mixed
     */
    public function actionIndex($task_id)
    {
        $model = $this->findTask($task_id);
        $roleTask = new RoleTask();
        $roleTask->task_id = $model->task_id;
        $dataProvider = $roleTask->search();

        return $this->render('index', compact('model', 'dataProvider'));
    }

    /**
     * Displays a single RoleTask model.
     * @param integer $role_id
     * @param integer $task_id
     * @return mixed
     */
    public function actionView($role_id, $task_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($role_id, $task_id),
        ]);
    }

    /**
     * Creates a new RoleTask model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new RoleTask();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'role_id' => $model->role_id, 'task_id' => $model->task_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing RoleTask model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $role_id
     * @param integer $task_id
     * @return mixed
     */
    public function actionUpdate($role_id, $task_id)
    {
        $model = $this->findModel($role_id, $task_id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'role_id' => $model->role_id, 'task_id' => $model->task_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing RoleTask model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $role_id
     * @param integer $task_id
     * @return mixed
     */
    public function actionDelete($role_id, $task_id)
    {
        $this->findModel($role_id, $task_id)->delete();

        return $this->redirect(['index', 'task_id' => $task_id]);
    }

    /**
     * Finds the RoleTask model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $role_id
     * @param integer $task_id
     * @return RoleTask the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($role_id, $task_id)
    {
        if (($model = RoleTask::findOne(['role_id' => $role_id, 'task_id' => $task_id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finds the AuthRole model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $task_id
     * @return AuthTask the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTask($task_id)
    {
        if (($model = AuthTask::findOne($task_id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
