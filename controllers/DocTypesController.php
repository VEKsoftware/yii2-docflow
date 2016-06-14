<?php

namespace docflow\controllers;

use docflow\Docflow;
use docflow\models\StatusSimpleLink;
use docflow\models\StatusTreePosition;
use Yii;

use yii\base\ErrorException;
use yii\di\Instance;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

use docflow\models\DocTypes;
use docflow\models\DocTypesSearch;

use docflow\models\Statuses;
use docflow\models\StatusesLinks;
use docflow\models\StatusesLinksSearch;
use docflow\models\StatusesSearch;
use yii\web\Response;

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

        if (!$searchModel->isAllowed('docflow.doctypes.view')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
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
     * @param int $doc type_doc tag
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionView($doc)
    {
        $model = $this->findModel($doc);
        if (empty($model)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$model->isAllowed('docflow.doctypes.view')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        $searchModel = new StatusesSearch(['doc_type_id' => $model->id]);
        if (!$searchModel->isAllowed('docflow.statuses.view')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('view', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Finds the DocTypes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $doc doc_type tag
     *
     * @return DocTypes the loaded model
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($doc)
    {
        if (!empty(($model = DocTypes::getDocType($doc)))) {
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

        if (!$model->isAllowed('docflow.doctypes.create')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'doc' => $model->tag]);
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
     * @param int $doc type_doc tag
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionUpdate($doc)
    {
        $model = $this->findModel($doc);

        if (!$model->isAllowed('docflow.doctypes.update')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'doc' => $model->tag]);
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
     * @param int $doc doc_type tag
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionDelete($doc)
    {
        $model = $this->findModel($doc);

        if (!$model->isAllowed('docflow.doctypes.delete')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        $model->delete();

        return $this->redirect(['index']);
    }

// ------------------------------- STATUSES ---------------------------------

    /**
     * Creates a new Statuses model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param string $tag the tag of the doc_type
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionCreateStatus($doc)
    {
        $docObj = DocTypes::getDocType($doc);
        if (empty($docObj)) {
            throw new NotFoundHttpException('The doc_type does not exist.');
        }

        $model = new Statuses(['doc_type_id' => $docObj->id]);

        if (!$model->isAllowed('docflow.statuses.create')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }


        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'doc' => $docObj->tag, 'tag' => $model->tag]);
        } else {
            return $this->render('create-status', [
                'doc' => $docObj,
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays a single Statuses model.
     *
     * @param string $doc doc tag
     * @param string $tag status tag
     *
     * @return mixed
     *
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionStatusView($doc, $tag)
    {
        /**
         * @var StatusTreePosition $treePositionsClass
         */
        $treePositionsClass = Instance::ensure([], StatusTreePosition::className());
        $document = $this->findModel($doc);

        $model = $document->statuses[$tag];

        if (empty($model)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$model->isAllowed('docflow.status.view')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        $tree = $treePositionsClass->getTreeWithSimpleLinks(
            $document->statusesStructure,
            $model
        );

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view-status', [
                'doc' => $doc,
                'model' => $model,
                'tree' => $tree,
            ]);
        } else {
            return $this->render('view-status', [
                'doc' => $doc,
                'model' => $model,
                'tree' => $tree,
            ]);
        }
    }

    protected function findStatusModel($doc, $tag)
    {
        $doc_model = $this->findModel($doc);
        if (!empty($doc_model->statuses[$tag])) {
            return $doc_model->statuses[$tag];
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Обновляем статус документа
     * @param string $doc    Тэг документа
     * @param string $status Тэг статуса документа
     * @return string|\yii\web\Response
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionStatusUpdate($doc, $status)
    {
        $model = $this->findStatusModel($doc, $status);

        if (!$model->isAllowed('docflow.docstatuses.update')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['status-view', 'doc' => $doc, 'tag' => $model->tag]);
        } else {
            return $this->render('update-status', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Удаляем статус документа
     * @param string $doc    Тэг документа
     * @param string $status Тэг Статуса документа
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionStatusDelete($doc, $status)
    {
        $model = $this->findStatusModel($doc, $status);

        if (!$model->isAllowed('docflow.docstatuses.delete')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        $model->delete();

        return $this->redirect(['view', 'doc' => $doc]);
    }

    /**
     * Перемещаем статус на позицию выше (в пределах своего уровня вложенности)
     *
     * @param string $statusTag - Тэг статуса
     *
     * @return mixed
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxStatusTreeUp($statusTag)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /**
         * @var StatusTreePosition $treePositionsClass
         */
        $treePositionsClass = Instance::ensure([], StatusTreePosition::className());

        return $treePositionsClass->setStatusInTreeVertical($statusTag, 'Up');
    }

    /**
     * Перемещаем статус на позицию ниже в древе (в пределах своего уровня вложенности)
     *
     * @param string $statusTag - Тэг статуса
     *
     * @return mixed
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxStatusTreeDown($statusTag)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /**
         * @var StatusTreePosition $treePositionsClass
         */
        $treePositionsClass = Instance::ensure([], StatusTreePosition::className());

        return $treePositionsClass->setStatusInTreeVertical($statusTag, 'Down');
    }

    /**
     * Получаем древо по Ajax запросу
     *
     * @param string $docTag - Тэг документа
     *
     * @return array
     *
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxTree($docTag)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /**
         * @var StatusTreePosition $treePositionsClass
         */
        $treePositionsClass = Instance::ensure([], StatusTreePosition::className());

        /**
         * @var DocTypes $model
         */
        $model = $this->findModel($docTag);
        if (empty($model)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $treePositionsClass->getTree($model->statusesStructure);
    }

    /**
     * Перемещение стутуса из текущего уровня во внутренний верх лежащего статуса
     *
     * @param string $statusTag - Тэг статуса
     * @param string $docTag    - Тэг документа
     *
     * @return array
     *
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxStatusTreeRight($statusTag, $docTag)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /**
         * @var StatusTreePosition $treePositionsClass
         */
        $treePositionsClass = Instance::ensure([], StatusTreePosition::className());

        return $treePositionsClass->setStatusInTreeHorizontal($statusTag, $docTag, 'Right');
    }

    /**
     * Перемещение статуса из текущего уровня родительского статуса во внешний уровень, к родительскому статусу
     *
     * @param string $statusTag - Тэг статуса
     * @param string $docTag    - Тэг документа
     *
     * @return array
     *
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxStatusTreeLeft($statusTag, $docTag)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /**
         * @var StatusTreePosition $treePositionsClass
         */
        $treePositionsClass = Instance::ensure([], StatusTreePosition::className());

        return $treePositionsClass->setStatusInTreeHorizontal($statusTag, $docTag, 'Left');
    }

    /**
     * Действие добавления SimpleLink
     *
     * @param string $tagTo   - Тэг статуса To
     * @param string $tagFrom - Тэг статуса From
     * @param string $tagDoc  - Тэг документа
     *
     * @return array - ['error' => .....] or ['success' => .....]
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxAddSimpleLink($tagTo, $tagFrom, $tagDoc)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /**
         * @var StatusSimpleLink $statusSimpleLink
         */
        $statusSimpleLink = Instance::ensure([], StatusSimpleLink::className());

        return $statusSimpleLink->addSimpleLink($tagDoc, $tagFrom, $tagTo);
    }

    /**
     * Действие удаления SimpleLink
     *
     * @param string $tagTo   - Тэг статуса To
     * @param string $tagFrom - Тэг статуса From
     * @param string $tagDoc  - Тэг документа
     *
     * @return array - ['error' => .....] or ['success' => .....]
     *
     * @throws \yii\db\StaleObjectException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxRemoveSimpleLink($tagTo, $tagFrom, $tagDoc)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /**
         * @var StatusSimpleLink $statusSimpleLink
         */
        $statusSimpleLink = Instance::ensure([], StatusSimpleLink::className());

        return $statusSimpleLink->removeSimpleLink($tagFrom, $tagTo);
    }
}
