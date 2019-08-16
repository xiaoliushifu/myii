<?php

namespace Wkii\Yii2Rbac\controllers;

use Wkii\Yii2Rbac\base\Controller;
use Wkii\Yii2Rbac\models\AuthTask;
use Wkii\Yii2Rbac\models\RoleTask;
use Wkii\Yii2Rbac\models\TaskCategory;
use Wkii\Yii2Rbac\models\UserRole;
use Yii;
use Wkii\Yii2Rbac\models\AuthRole;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * AuthRoleController implements the CRUD actions for AuthRole model.
 */
class AuthRoleController extends Controller
{
    /**
     * Lists all AuthRole models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new AuthRole();
        $model->status = AuthRole::STATUS_ACTIVE;

        $dataProvider = $model->search(Yii::$app->request->getQueryParams());

        return $this->render('index', compact('model', 'dataProvider'));
    }

    /**
     * Displays a single AuthRole model.
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
     * Creates a new AuthRole model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AuthRole();
        $model->scenario = AuthRole::SCENARIO_CREATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->role_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing AuthRole model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = AuthRole::SCENARIO_UPDATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->role_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing AuthRole model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        (new AuthRole())->deleteRole($id);

        return $this->redirect(['index']);
    }

    /**
     * 为角色分配任务
     * 1. 获取角色实例,
     * 2. 获取所有任务分类,
     * 3. 角色下所有已经存在的任务
     * 4. 分类对应的任务,
     * 步骤三跟步骤四的交集就是分类中已经存在的任务, 出去交集部分,就是为分配的
     * @param int $id 角色ID
     * @param int $category_id 分类
     * @return string
     */
    public function actionAssignTask($id)
    {
        $model = $this->findModel($id);
        $taskModel = new AuthTask();
        $categories = (new TaskCategory())->getAllCategory();
        //当前选中的分类
        $currentCategory = Yii::$app->request->getQueryParam('category_id', null);
        $allocatedTask = $taskModel->allocatedTaskByRoleID($id, $currentCategory);
        $unallocatedTask = $taskModel->unallocatedTask($id, $currentCategory);

        //如果是post请求,证明是发送了任务分配操作
        if (Yii::$app->request->isPost) {
            $operate = Yii::$app->request->post('operate');
            $roleTask = new RoleTask();
            if ($operate == 'toleft') {
                //任务分配到角色, 获取发送过来的未分配的任务,一一验证为分配列表,然后写入到数据库中
                $unallocated = Yii::$app->request->post('unallocated', []);
                $pool = array_intersect($unallocated, array_keys($unallocatedTask));
                $roleTask->batchInsert($id, $pool);
            } elseif ($operate == "toright") {
                //已分配的任务移除角色任务列表, 获取要移除已分配列表数据,一一验证,然后从数据库中移除
                $allocated = Yii::$app->request->post('allocated', []);
                $pool = array_intersect($allocated, array_keys($allocatedTask));
                $roleTask->batchDelete($id, $pool);
            }
            //操作之后,进行重新查询
            $allocatedTask = $taskModel->allocatedTaskByRoleID($id, $currentCategory);
            $unallocatedTask = $taskModel->unallocatedTask($id, $currentCategory);
        }

        return $this->render('assign-task', compact('model', 'categories', 'currentCategory', 'allocatedTask', 'unallocatedTask'));
    }

    /**
     * Finds the AuthRole model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AuthRole the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AuthRole::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
