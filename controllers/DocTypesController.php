<?php

namespace docflow\controllers;

use docflow\behaviors\LinkOrderedBehavior;
use docflow\behaviors\LinkSimpleBehavior;
use docflow\models\base\Document;
use docflow\models\statuses\StatusesTreeSearch;
use docflow\widgets\FlTreeWidgetWithLeaf;
use docflow\widgets\FlTreeWidgetWithSimpleLinks;
use yii;

use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

use docflow\models\base\docType\DocTypes;
use docflow\models\base\DocType\DocTypesSearch;
use docflow\models\statuses\Statuses;
use docflow\models\statuses\StatusesSearch;

use yii\web\Response;

/**
 * DocTypesController implements the CRUD actions for DocTypes model.
 */
class DocTypesController extends Controller
{
    /**
     * Описываем поведения
     *
     * @return array
     */
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
     *
     * @return mixed
     *
     * @throws InvalidParamException
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        $searchModel = new DocTypesSearch();

        if (!$searchModel->isAllowed('docflow.doctypes.view')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render(
            'index',
            [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider
            ]
        );
    }

    /**
     * Displays a single DocTypes model.
     *
     * @param int $doc type_doc tag
     *
     * @return mixed
     *
     * @throws InvalidParamException
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

        return $this->render(
            'view',
            [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'flTreeWidgetParam' => [
                    'titleList' => Yii::t('docflow', 'List of statuses'),
                    'flTreeUrl' => [
                        'doc-types/ajax-get-child',
                        'docType' => $doc,
                        'extra' => json_encode(['doc_type_id' => $model->id])
                    ]
                ]
            ]
        );
    }

    /**
     * Получаем документы для родительского документа, которые находятся на переданной странице(номер)
     *
     * @param string|null  $docType     - тип документа
     * @param integer      $page        - номер страницы
     * @param string|null  $extra       - json строка содержащая данные для доп фильтрации документов
     * @param integer|null $nodeIdValue - значение идентификатора документа
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     * @throws NotFoundHttpException
     */
    public function actionAjaxGetNext($docType = null, $page, $extra = null, $nodeIdValue = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findTreeStatusModel($nodeIdValue);

        $searchModel = new StatusesTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';

        if ($extra !== null) {
            $searchModel->extraFilter = (array)json_decode($extra);
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $config = [
            'links' => [
                'documentView' => [
                    'route' => 'status-view',
                    'params' => [
                        'doc' => $docType,
                        'tag' => [
                            'value' => 'tag',
                            'type' => 'property'
                        ]
                    ]
                ],
                'next' => [
                    'route' => 'doc-types/ajax-get-next',
                    'params' => [
                        'docType' => $docType,
                        'page' => ++$page,
                        'extra' => $extra,
                        'nodeIdValue' => $nodeIdValue,
                    ]
                ],
                'child' => [
                    'route' => 'doc-types/ajax-get-child',
                    'params' => [
                        'docType' => $docType,
                        'nodeIdValue' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                        'extra' => $extra,
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithLeaf::getStructure($dataProvider, $config);
    }

    /**
     * Формируем детей документа, идентификатор которого передан в аргументе
     *
     * @param string|null  $docType     - тип документа
     * @param string|null  $extra       - json строка содержащая данные для доп фильтрации документов
     * @param integer|null $nodeIdValue - значение идентификатора документа
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     * @throws NotFoundHttpException
     */
    public function actionAjaxGetChild($docType = null, $extra = null, $nodeIdValue = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findTreeStatusModel($nodeIdValue);

        $searchModel = new StatusesTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';

        if ($extra !== null) {
            $searchModel->extraFilter = (array)json_decode($extra);
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $config = [
            'links' => [
                'documentView' => [
                    'route' => 'status-view',
                    'params' => [
                        'doc' => $docType,
                        'tag' => [
                            'value' => 'tag',
                            'type' => 'property'
                        ],
                    ]
                ],
                'next' => [
                    'route' => 'doc-types/ajax-get-next',
                    'params' => [
                        'docType' => $docType,
                        'page' => 2,
                        'extra' => $extra,
                        'nodeIdValue' => $nodeIdValue,
                    ]
                ],
                'child' => [
                    'route' => 'doc-types/ajax-get-child',
                    'params' => [
                        'docType' => $docType,
                        'nodeIdValue' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                        'extra' => $extra,
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithLeaf::getStructure($dataProvider, $config);
    }

    /**
     * Получаем документы для родительского документа, которые находятся на переданной странице(номер)
     *
     * @param integer     $page          - номер страницы
     * @param integer     $fromNodeId    - значение индентификатора документа от которого будем работать с простыми связями
     * @param string|null $extra         - json строка содержащая данные для доп фильтрации документов
     * @param integer     $currentNodeId - идентификатор текущего документв в структуре с простыми связями
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     * @throws NotFoundHttpException
     */
    public function actionAjaxGetNextWithSimple($page, $fromNodeId, $extra = null, $currentNodeId = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findTreeStatusModel($currentNodeId);

        $searchModel = new StatusesTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';

        if ($extra !== null) {
            $searchModel->extraFilter = (array)json_decode($extra);
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /* Получаем документы с родительскими связями 1 уровня */
        $parentDocument = Statuses::getDocumentByNodeId($fromNodeId)->one();
        $simpleBehavior = $parentDocument->getBehavior('transitions');

        $config = [
            'simpleLinks' => $simpleBehavior->statusesTransitionTo,
            'nodeIdField' => $document->linkFieldsArray['node_id'],
            'links' => [
                'addSimple' => [
                    'route' => 'doc-types/ajax-add-simple-link',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ]
                    ]
                ],
                'delSimple' => [
                    'route' => 'doc-types/ajax-remove-simple-link',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ]
                    ]
                ],
                'next' => [
                    'route' => 'doc-types/ajax-get-next-with-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'page' => ++$page,
                        'extra' => $extra,
                        'currentNodeId' => $currentNodeId,
                    ]
                ],
                'child' => [
                    'route' => 'doc-types/ajax-get-child-with-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'currentNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                        'extra' => $extra,
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithSimpleLinks::getStructure($dataProvider, $config);
    }

    /**
     * Формируем детей документа, идентификатор которого передан в аргументе
     *
     * @param integer     $fromNodeId    - значение индентификатора документа от которого будем работать с простыми связями
     * @param string|null $extra         - json строка содержащая данные для доп фильтрации документов
     * @param integer     $currentNodeId - идентификатор текущего документв в структуре с простыми связями
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     * @throws NotFoundHttpException
     */
    public function actionAjaxGetChildWithSimple($fromNodeId, $extra = null, $currentNodeId = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findTreeStatusModel($currentNodeId);

        $searchModel = new StatusesTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';

        if ($extra !== null) {
            $searchModel->extraFilter = (array)json_decode($extra);
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /* Получаем документы с родительскими связями 1 уровня */
        $parentDocument = Statuses::getDocumentByNodeId($fromNodeId)->one();
        $simpleBehavior = $parentDocument->getBehavior('transitions');

        $config = [
            'simpleLinks' => $simpleBehavior->statusesTransitionTo,
            'nodeIdField' => $document->linkFieldsArray['node_id'],
            'links' => [
                'addSimple' => [
                    'route' => 'doc-types/ajax-add-simple-link',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ]
                    ]
                ],
                'delSimple' => [
                    'route' => 'doc-types/ajax-remove-simple-link',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ]
                    ]
                ],
                'next' => [
                    'route' => 'doc-types/ajax-get-next-with-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'page' => 2,
                        'extra' => $extra,
                        'currentNodeId' => $currentNodeId,
                    ]
                ],
                'child' => [
                    'route' => 'doc-types/ajax-get-child-with-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'currentNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                        'extra' => $extra,
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithSimpleLinks::getStructure($dataProvider, $config);
    }

    /**
     * Finds the DocTypes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param string $docTag doc_type tag
     *
     * @return DocTypes the loaded model
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($docTag)
    {
        $model = DocTypes::getDocType($docTag);

        if (empty($model)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $model;
    }

    /**
     * Creates a new DocTypes model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     *
     * @throws InvalidParamException
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
            return $this->render('create', ['model' => $model]);
        }
    }

    /**
     * Updates an existing DocTypes model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $doc type_doc tag
     *
     * @return mixed
     *
     * @throws InvalidParamException
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
            return $this->render('update', ['model' => $model]);
        }
    }

    /**
     * Deletes an existing DocTypes model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param int $doc doc_type tag
     *
     * @return mixed
     *
     * @throws StaleObjectException
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
     *
     * @param string $doc the tag of the doc_type
     *
     * @return mixed
     *
     * @throws InvalidParamException
     * @throws NotFoundHttpException
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
            return $this->render(
                'create-status',
                [
                    'doc' => $docObj,
                    'model' => $model
                ]
            );
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
     * @throws InvalidParamException
     * @throws InvalidConfigException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionStatusView($doc, $tag)
    {
        $document = $this->findModel($doc);
        $extra = json_encode(['doc_type_id' => $document->id]);

        $model = $document->statuses[$tag];

        if (empty($model)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$model->isAllowed('docflow.status.view')) {
            throw new ForbiddenHttpException(Yii::t('docflow', 'Access restricted'));
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax(
                'view-status',
                [
                    'doc' => $doc,
                    'model' => $model,
                    'extra' => $extra,
                ]
            );
        } else {
            return $this->render(
                'view-status',
                [
                    'doc' => $doc,
                    'model' => $model,
                    'extra' => $extra,
                ]
            );
        }
    }

    /**
     * Получаем объект документа
     *
     * @param string $docTag - тэг типа документа
     * @param string $tag    - тэг документа
     *
     * @return Statuses
     *
     * @throws NotFoundHttpException
     */
    protected function findStatusModel($docTag, $tag)
    {
        $docModelObj = $this->findModel($docTag);

        if (empty($docModelObj->statuses[$tag])) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $docModelObj->statuses[$tag];
    }

    /**
     * Обновляем статус документа
     *
     * @param string $doc    Тэг документа
     * @param string $status Тэг статуса документа
     *
     * @return string|Response
     *
     * @throws InvalidParamException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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
            return $this->render(
                'update-status',
                [
                    'model' => $model,
                    'doc' => $doc
                ]
            );
        }
    }

    /**
     * Удаляем статус документа
     *
     * @param string $doc    Тэг документа
     * @param string $status Тэг Статуса документа
     *
     * @return Response
     *
     * @throws StaleObjectException
     * @throws \Exception
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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
     * @param string      $statusTag - Тэг статуса
     * @param string      $docTag    - Тэг документа
     * @param string|null $extra     - json строка содержащая данные для доп фильтрации документов
     *
     * @return mixed
     *
     * @throws ErrorException
     * @throws NotFoundHttpException
     */
    public function actionAjaxStatusTreeUp($statusTag, $docTag, $extra = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findStatusModel($docTag, $statusTag);

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $model->getBehavior('structure');

        if ($extra !== null) {
            $behavior->extraFilter = (array)json_decode($extra);
        }

        return $behavior->orderUp();
    }

    /**
     * Перемещаем статус на позицию ниже в древе (в пределах своего уровня вложенности)
     *
     * @param string      $statusTag - Тэг статуса
     * @param string      $docTag    - Тэг документа
     * @param string|null $extra     - json строка содержащая данные для доп фильтрации документов
     *
     * @return mixed
     *
     * @throws ErrorException
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionAjaxStatusTreeDown($statusTag, $docTag, $extra = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findStatusModel($docTag, $statusTag);

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $model->getBehavior('structure');

        if ($extra !== null) {
            $behavior->extraFilter = (array)json_decode($extra);
        }

        return $behavior->orderDown();
    }

    /**
     * Перемещение стутуса из текущего уровня во внутренний верх лежащего статуса
     *
     * @param string      $statusTag - Тэг статуса
     * @param string      $docTag    - Тэг документа
     * @param string|null $extra     - json строка содержащая данные для доп фильтрации документов
     *
     * @return array
     *
     * @throws ErrorException
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws InvalidConfigException
     */
    public function actionAjaxStatusTreeRight($statusTag, $docTag, $extra = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findStatusModel($docTag, $statusTag);

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $model->getBehavior('structure');

        if ($extra !== null) {
            $behavior->extraFilter = (array)json_decode($extra);
        }

        return $behavior->levelUp();
    }

    /**
     * Перемещение статуса из текущего уровня родительского статуса во внешний уровень, к родительскому статусу
     *
     * @param string      $statusTag - Тэг статуса
     * @param string      $docTag    - Тэг документа
     * @param null|string $extra     - json строка содержащая данные для доп фильтрации документов
     *
     * @return array
     *
     * @throws ErrorException
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws InvalidConfigException
     */
    public function actionAjaxStatusTreeLeft($statusTag, $docTag, $extra = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findStatusModel($docTag, $statusTag);

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $model->getBehavior('structure');

        if ($extra !== null) {
            $behavior->extraFilter = (array)json_decode($extra);
        }

        return $behavior->levelDown();
    }

    /**
     * Действие добавления SimpleLink
     *
     * @param string $fromNodeId - значение идентификатора документа From (node_id)
     * @param string $toNodeId   - значение идентификатора документа To (node_id)
     *
     * @return array - ['error' => .....] or ['success' => .....]
     *
     * @throws NotFoundHttpException
     * @throws ErrorException
     * @throws InvalidConfigException
     */
    public function actionAjaxAddSimpleLink($fromNodeId, $toNodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /* @var Document $documentFrom */
        $documentFrom = Statuses::getDocumentByNodeId($fromNodeId)->one();
        /* @var Document $documentTo */
        $documentTo = Statuses::getDocumentByNodeId($toNodeId)->one();

        /* @var LinkSimpleBehavior $behavior */
        $behavior = $documentFrom->getBehavior('transitions');

        return $behavior->addSimpleLink($documentTo);
    }

    /**
     * Действие удаления SimpleLink
     *
     * @param string $fromNodeId - значение идентификатора документа From (node_id)
     * @param string $toNodeId   - значение идентификатора документа To (node_id)
     *
     * @return array - ['error' => .....] or ['success' => .....]
     *
     * @throws ErrorException
     * @throws NotFoundHttpException
     * @throws StaleObjectException
     * @throws \Exception
     * @throws InvalidConfigException
     */
    public function actionAjaxRemoveSimpleLink($fromNodeId, $toNodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /* @var Document $documentFrom */
        $documentFrom = Statuses::getDocumentByNodeId($fromNodeId)->one();
        /* @var Document $documentTo */
        $documentTo = Statuses::getDocumentByNodeId($toNodeId)->one();

        /* @var LinkSimpleBehavior $behavior */
        $behavior = $documentFrom->getBehavior('transitions');

        return $behavior->delSimpleLink($documentTo);
    }


    /**
     * Получаем модель статуса для построеня древа
     *
     * @param null|integer $nodeIdValue - id ноды
     *
     * @return Statuses
     */
    protected function findTreeStatusModel($nodeIdValue)
    {
        if ($nodeIdValue === null) {
            $statuses = new Statuses();
        } else {
            $statuses = Statuses::getDocumentByNodeId($nodeIdValue)->one();
        }

        return $statuses;
    }

    /**
     * Устанавливаем родителя
     *
     * @param string $childName  - имя ребенка
     * @param string $parentName - имя родителя
     *
     * @return array
     *
     * @throws StaleObjectException
     * @throws \Exception
     * @throws InvalidConfigException
     * @throws ErrorException
     */
    public function actionSetParent($childName, $parentName)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /* @var Document $child */
        $child = Statuses::getStatusByDocName($childName);

        /* @var Document $parent */
        $parent = Statuses::getStatusByDocName($parentName);

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $child->getBehavior('structure');

        if ($parentName !== 'null') {
            $behavior->setParent($parent);
        } else {
            $behavior->removeParents();
        }

        /* TODO отдавать ответы от методов, а не такие.... */
        return ['success' => 'Родитель изменен'];
    }
}
