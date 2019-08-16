<?php

namespace Wkii\Yii2Rbac\controllers;

use Wkii\Yii2Rbac\models\AuthRole;
use Yii;
use Wkii\Yii2Rbac\models\UserRole;
use yii\data\ActiveDataProvider;
use Wkii\Yii2Rbac\base\Controller;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UserRoleController implements the CRUD actions for UserRole model.
 */
class UserRoleController extends Controller
{
    /**
     * Lists all UserRole models.
     * @param int $role_id
     * @return mixed
     */
    public function actionIndex($role_id)
    {
        $model = $this->findRole($role_id);
        $userRole = new UserRole();
        $userRole->role_id = $role_id;
        $dataProvider = $userRole->search();

        return $this->render('index', compact('model', 'dataProvider'));
    }

    /**
     * Displays a single UserRole model.
     * @param integer $user_id
     * @param integer $role_id
     * @return mixed
     */
    public function actionView($user_id, $role_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($user_id, $role_id),
        ]);
    }

    /**
     * 关联用户功能,列表展示所有用户, 如果有该角色的用户,列表复选框被勾选
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param int $role_id
     * @return mixed
     */
    public function actionCreate($role_id)
    {
        $model = $this->findRole($role_id);
        $userRole = new UserRole();
        $dataProvider = $userRole->searchUsers();

        //如果是post请求,那么就认为要传输数据,进行操作了
        if (Yii::$app->request->isPost) {
            $selection = Yii::$app->request->post('selection', []);
            //获取该页所有的userIds,进行求交集,证明是安全提交
            $userIds = ArrayHelper::getColumn($dataProvider->getKeys(), 'user_id');
            $pool = array_intersect($selection, $userIds);
            //获取角色下已经分配的用户, 找出$pool中为分配的用户ID
            $allocatedUserIds = $userRole->allocatedUserIds($role_id);
            $pool = array_diff($pool, $allocatedUserIds);
            //批量插入数据
            $userRole->batchInsertUser($role_id, $pool);
            //进行数据库操作之后,从新查询
            $dataProvider = $userRole->searchUsers();
            Yii::$app->session->setFlash('success', "授权用户成功!");
        }

        return $this->render('create', compact('model', 'dataProvider'));
    }

    /**
     * Updates an existing UserRole model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $user_id
     * @param integer $role_id
     * @return mixed
     */
    public function actionUpdate($user_id, $role_id)
    {
        $model = $this->findModel($user_id, $role_id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'user_id' => $model->user_id, 'role_id' => $model->role_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing UserRole model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $user_id
     * @param integer $role_id
     * @return mixed
     */
    public function actionDelete($user_id, $role_id)
    {
        $this->findModel($user_id, $role_id)->delete();

        return $this->redirect(['index', 'role_id' => $role_id]);
    }

    /**
     * Finds the UserRole model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $user_id
     * @param integer $role_id
     * @return UserRole the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($user_id, $role_id)
    {
        if (($model = UserRole::findOne(['user_id' => $user_id, 'role_id' => $role_id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finds the UserRole model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $role_id
     * @return AuthRole the loaded model
     * @throws NotFoundHttpException
     */
    protected function findRole($role_id)
    {
        if (($model = AuthRole::findOne($role_id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
