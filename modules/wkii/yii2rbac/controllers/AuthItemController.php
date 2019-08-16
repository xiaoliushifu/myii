<?php

namespace Wkii\Yii2Rbac\controllers;

use Wkii\Yii2Rbac\base\Controller;
use Wkii\Yii2Rbac\helpers\PhpDoc;
use Wkii\Yii2Rbac\models\TaskItem;
use Yii;
use Wkii\Yii2Rbac\models\AuthItem;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * 项目授权管理,包括:
 * 功能列表, 功能扫描, 功能所在任务, 功能检查
 */
class AuthItemController extends Controller
{

    /**
     * Lists all AuthItem models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new AuthItem();

        $dataProvider = $model->search(Yii::$app->request->getQueryParams());

        return $this->render('index', compact('model', 'dataProvider'));
    }

    /**
     * 扫描所有controller
     * @return string
     */
    public function actionScan()
    {
        $model = new AuthItem();

        $controllers = $model->getControllers();

        return $this->render("scan", compact('controllers', 'model'));
    }

    /**
     * 扫描controller中Action的功能
     * @return string
     */
    public function actionScanAction()
    {
        $model = new AuthItem();

        $model->load(Yii::$app->request->getQueryParams());
        $actions = array_diff($model->scanActions($model->controller), $model->hasActions($model->controller));
        if (Yii::$app->request->isPost) {
            $phpDoc = new PhpDoc($model->controller);
            foreach ($actions as $key => $action) {
                if (!array_key_exists($action, Yii::$app->request->post('action', []))) {
                    continue;
                }
                $item = new AuthItem();

                $item->setScenario(AuthItem::SCENARIO_SCAN_CREATE);
                $item->load(Yii::$app->request->getQueryParams());

                $item->action = $action;
                if (array_key_exists($action, Yii::$app->request->post('allowed', []))) {
                    $item->allowed = 1;
                }
                $item->description = $model->getActionDoc($item->controller, $action);
                if ($item->save()) {
                    //保存成功后从actions移除
                    unset($actions[$key]);
                }
            }
        }

        return $this->render("scan-action", compact('model', 'actions'));
    }

    /**
     * 扫描数据库中某个控制器的行为, 根据类中是否存在该方法,判断该授权项目是否已经失效
     * @return string
     */
    public function actionDelAction()
    {
        $model = new AuthItem();

        $model->load(Yii::$app->request->getQueryParams());
        $actions = $model->hasItems($model->controller);

        if (Yii::$app->request->isPost) {
            $keys = [];
            foreach ($actions as $key => $action) {
                if (!array_key_exists($key, Yii::$app->request->post('action', []))) {
                    continue;
                }
                array_push($keys, $key);
                unset($actions[$key]);
            }
            if (!empty($keys)) {
                $model->deleteItem($keys);
            }
        }

        return $this->render("del-action", compact('model', 'actions'));
    }

    /**
     * 检查权限对应的功能是否存在
     */
    public function actionCheck()
    {
        $model = new AuthItem();

        $items = $model->searchDirtyAction($model->getAllItems());
        if (Yii::$app->request->isPost) {
            $keys = [];
            foreach ($items as $key => $item) {
                if (!array_key_exists($item->primaryKey, Yii::$app->request->post('action', []))) {
                    continue;
                }
                array_push($keys, $item->primaryKey);
                unset($items[$key]);
            }
            if (!empty($keys)) {
                $model->deleteItem($keys);
            }
        }
        return $this->render("check", compact('model', 'items'));
    }

    /**
     * Displays a single AuthItem model.
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
     * Creates a new AuthItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AuthItem();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->item_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * 扫描创建授权项目,
     * 根据GET请求过来的controller名称, 扫描该类下的授权项目
     * 当POST请求过来判断发送过来的参数,依次遍历添加授权项目
     * @return string
     */
    public function actionScanCreate()
    {
        $model = new AuthItem();
        $actions = [];
        //如果是get请求,证明是填写了控制器名称
        $className = $model->formName();
        $model->load(Yii::$app->request->getQueryParams());

        if (isset($model->controller)) {
            $actions = array_diff($model->scanActions($model->controller), $model->hasActions($model->controller));
        }

        //证明是提交数据
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $phpDoc = new PhpDoc($model->controller);
            //循环添加授权项目
            foreach ($actions as $key => $action) {
                if (!array_key_exists($action, Yii::$app->request->post('action', []))) {
                    continue;
                }
                $item = new AuthItem();

                $item->setScenario(AuthItem::SCENARIO_SCAN_CREATE);
                $item->controller = $model->controller;
                $item->module = $model->module;
                $item->platform_id = $model->platform_id;

                $item->action = $action;
                if (array_key_exists($action, Yii::$app->request->post('allowed', []))) {
                    $item->allowed = 1;
                }
                $item->description = $model->getActionDoc($item->controller, $action);
                if ($item->save()) {
                    //保存成功后从actions移除
                    unset($actions[$key]);
                }
            }
        }

        return $this->render('scan-create', compact('model', 'actions'));
    }

    /**
     * Updates an existing AuthItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->item_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        (new AuthItem())->deleteItem($id);

        return $this->redirect(['index']);
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AuthItem the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AuthItem::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
