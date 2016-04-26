<?php

namespace docflow\controllers;

use docflow\models\DocTypes;
use docflow\models\DocTypesSearch;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * DocTypesController implements the CRUD actions for DocTypes model.
 */
class DocTypesController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all DocTypes models.
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        $searchModel = new DocTypesSearch();

        if (!$searchModel->isAllowed('statuses.doctypes.view')) {
            throw new ForbiddenHttpException(Yii::t('statuses', 'Access restricted'));
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single DocTypes model.
     *
     * @param int $id
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if (!$model->isAllowed('statuses.doctypes.view')) {
            throw new ForbiddenHttpException(Yii::t('statuses', 'Access restricted'));
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the DocTypes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return DocTypes the loaded model
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DocTypes::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Creates a new DocTypes model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionCreate()
    {
        $model = new DocTypes();

        if (!$model->isAllowed('statuses.doctypes.create')) {
            throw new ForbiddenHttpException(Yii::t('statuses', 'Access restricted'));
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing DocTypes model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $id
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (!$model->isAllowed('statuses.doctypes.update')) {
            throw new ForbiddenHttpException(Yii::t('statuses', 'Access restricted'));
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing DocTypes model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param int $id
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if (!$model->isAllowed('statuses.doctypes.delete')) {
            throw new ForbiddenHttpException(Yii::t('statuses', 'Access restricted'));
        }

        $model->delete();

        return $this->redirect(['index']);
    }
}
