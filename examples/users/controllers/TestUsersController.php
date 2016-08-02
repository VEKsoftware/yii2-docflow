<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 12.07.16
 * Time: 16:07
 */

namespace docflow\examples\users\controllers;

use docflow\behaviors\LinkOrderedBehavior;
use docflow\behaviors\LinkSimpleBehavior;
use docflow\examples\users\models\Users;
use docflow\examples\users\models\UsersTreeSearch;
use docflow\models\Document;
use docflow\widgets\FlTreeWidget;
use docflow\widgets\FlTreeWidgetWithSimpleLinks;
use yii;
use yii\base\Action;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class TestUsersController extends Controller
{
    public $defaultAction = 'view';

    /**
     * Перед действием
     *
     * @param Action $action - действие
     *
     * @return bool
     *
     * @throws InvalidParamException
     * @throws BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->setViewPath('@docflow' . DIRECTORY_SEPARATOR .
            'examples' . DIRECTORY_SEPARATOR .
            'users' . DIRECTORY_SEPARATOR .
            'views' . DIRECTORY_SEPARATOR .
            'test-users'
        );

        return parent::beforeAction($action);
    }

    /**
     * Получаем общее представление
     *
     * @return string
     *
     * @throws InvalidParamException
     */
    public function actionView()
    {
        $param = [
            'flTreeWidgetParam' => [
                'flTreeUrl' => ['test-users/ajax-child'],
                'titleList' => 'Тест: список пользователей'
            ],
        ];

        return $this->render('view', $param);
    }

    /**
     * Смотрим документ
     *
     * @param integer $nodeId - id документа
     *
     * @return string
     *
     * @throws InvalidParamException
     */
    public function actionViewDocument($nodeId)
    {
        $document = Users::getDocumentByNodeId($nodeId)->one();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view-document', ['document' => $document]);
        } else {
            return $this->render('view-document', ['document' => $document]);
        }
    }

    /**
     * Смотрим следеющие
     *
     * @param integer      $page        - номер страницы
     * @param null|integer $nodeIdValue - id документа
     *
     * @return array
     *
     * @throws InvalidParamException
     * @throws ErrorException
     */
    public function actionAjaxNext($page, $nodeIdValue = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findModel($nodeIdValue);

        $searchModel = new UsersTreeSearch();
        $dataProvider = $searchModel->search($document, 'firmTree', Yii::$app->request->queryParams);

        $config = [
            'links' => [
                'documentView' => [
                    'route' => 'test-users/view-document',
                    'params' => [
                        'nodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ]
                    ]
                ],
                'next' => [
                    'route' => 'test-users/ajax-next',
                    'params' => [
                        'page' => ++$page,
                        'nodeIdValue' => $nodeIdValue,
                    ]
                ],
                'child' => [
                    'route' => 'test-users/ajax-child',
                    'params' => [
                        'nodeIdValue' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ]
            ]
        ];

        return FlTreeWidget::getStructure($dataProvider, $config);
    }

    /**
     * Получаем детей
     *
     * @param null|integer $nodeIdValue - id корневого документа
     *
     * @return array
     *
     * @throws InvalidParamException
     * @throws ErrorException
     */
    public function actionAjaxChild($nodeIdValue = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findModel($nodeIdValue);

        $searchModel = new UsersTreeSearch();
        $dataProvider = $searchModel->search($document, 'firmTree', Yii::$app->request->queryParams);

        $config = [
            'links' => [
                'documentView' => [
                    'route' => 'test-users/view-document',
                    'params' => [
                        'nodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'next' => [
                    'route' => 'test-users/ajax-next',
                    'params' => [
                        'page' => 2,
                        'nodeIdValue' => $nodeIdValue,
                    ]
                ],
                'child' => [
                    'route' => 'test-users/ajax-child',
                    'params' => [
                        'nodeIdValue' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ]
            ]
        ];

        return FlTreeWidget::getStructure($dataProvider, $config);
    }

    /**
     * Получаем следующие документы для корневого документа
     *
     * @param integer      $page          - номер страницы
     * @param integer      $fromNodeId    - id документа, от которого сморим простые связи
     * @param null|integer $currentNodeId - id корневого документа, от него смотрим детей и следующие записи
     *
     * @return array
     *
     * @throws InvalidParamException
     * @throws ErrorException
     */
    public function actionAjaxNextSimple($page, $fromNodeId, $currentNodeId = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findModel($currentNodeId);

        $searchModel = new UsersTreeSearch();
        $dataProvider = $searchModel->search($document, 'firmTree', Yii::$app->request->queryParams);

        /* Получаем документы с родительскими связями 1 уровня */
        $parentDocument = Users::getDocumentByNodeId($fromNodeId)->one();
        $simpleBehavior = $parentDocument->getBehavior('firmTreeAllS');

        $config = [
            'simpleLinks' => $simpleBehavior->statusesTransitionTo,
            'nodeIdField' => $document->linkFieldsArray['node_id'],
            'links' => [
                'addSimple' => [
                    'route' => 'test-users/ajax-add-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'delSimple' => [
                    'route' => 'test-users/ajax-del-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'next' => [
                    'route' => 'test-users/ajax-next-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'page' => ++$page,
                        'currentNodeId' => $currentNodeId,
                    ]
                ],
                'child' => [
                    'route' => 'test-users/ajax-child-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'currentNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithSimpleLinks::getStructure($dataProvider, $config);
    }

    /**
     * Получаем детей для корневого родителя с простыми связями
     *
     * @param integer      $fromNodeId    - id документа, от которого сморим простые связи
     * @param null|integer $currentNodeId - id корневого документа, от него смотрим детей и следующие записи
     *
     * @return array
     *
     * @throws InvalidParamException
     * @throws ErrorException
     */
    public function actionAjaxChildSimple($fromNodeId, $currentNodeId = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = $this->findModel($currentNodeId);

        $searchModel = new UsersTreeSearch();
        $dataProvider = $searchModel->search($document, 'firmTree', Yii::$app->request->queryParams);

        /* Получаем документы с родительскими связями 1 уровня */
        $parentDocument = Users::getDocumentByNodeId($fromNodeId)->one();
        $simpleBehavior = $parentDocument->getBehavior('firmTreeAllS');

        $config = [
            'simpleLinks' => $simpleBehavior->statusesTransitionTo,
            'nodeIdField' => $document->linkFieldsArray['node_id'],
            'links' => [
                'addSimple' => [
                    'route' => 'test-users/ajax-add-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'delSimple' => [
                    'route' => 'test-users/ajax-del-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'next' => [
                    'route' => 'test-users/ajax-next-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'page' => 2,
                        'currentNodeId' => $currentNodeId,
                    ]
                ],
                'child' => [
                    'route' => 'test-users/ajax-child-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'currentNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ]
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithSimpleLinks::getStructure($dataProvider, $config);
    }

    /**
     * Добавляем простую связь
     *
     * @param integer $fromNodeId - значение идентификатора документа From (node_id)
     * @param integer $toNodeId   - значение идентификатора документа To (node_id)
     *
     * @return array
     *
     * @throws InvalidConfigException
     * @throws ErrorException
     */
    public function actionAjaxAddSimple($fromNodeId, $toNodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /* @var Document $documentFrom */
        $documentFrom = Users::getDocumentByNodeId($fromNodeId)->one();
        /* @var Document $documentTo */
        $documentTo = Users::getDocumentByNodeId($toNodeId)->one();

        /* @var LinkSimpleBehavior $behavior */
        $behavior = $documentFrom->getBehavior('firmTreeAllS');

        return $behavior->addSimpleLink($documentTo);
    }

    /**
     * Удаляем простую связь
     *
     * @param integer $fromNodeId - значение идентификатора документа From (node_id)
     * @param integer $toNodeId   - значение идентификатора документа To (node_id)
     *
     * @return array
     *
     * @throws StaleObjectException
     * @throws \Exception
     * @throws ErrorException
     */
    public function actionAjaxDelSimple($fromNodeId, $toNodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /* @var Document $documentFrom */
        $documentFrom = Users::getDocumentByNodeId($fromNodeId)->one();
        /* @var Document $documentTo */
        $documentTo = Users::getDocumentByNodeId($toNodeId)->one();

        /* @var LinkSimpleBehavior $behavior */
        $behavior = $documentFrom->getBehavior('firmTreeAllS');

        return $behavior->delSimpleLink($documentTo);
    }

    /**
     * Вверх
     *
     * @param integer $nodeId - id документа
     *
     * @return array
     * @throws ErrorException
     */
    public function actionAjaxUp($nodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = Users::getDocumentByNodeId($nodeId)->one();

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $document->getBehavior('firmTreeAllO');

        return $behavior->orderUp();
    }

    /**
     * Вниз
     *
     * @param integer $nodeId - id документа
     *
     * @return array
     * @throws ErrorException
     */
    public function actionAjaxDown($nodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = Users::getDocumentByNodeId($nodeId)->one();

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $document->getBehavior('firmTreeAllO');

        return $behavior->orderDown();
    }

    /**
     * В право
     *
     * @param integer $nodeId - id документа
     *
     * @return array
     * @throws ErrorException
     */
    public function actionAjaxRight($nodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = Users::getDocumentByNodeId($nodeId)->one();
        /* @var LinkOrderedBehavior $behavior */
        $behavior = $document->getBehavior('firmTreeAllO');

        return $behavior->levelUp();
    }

    /**
     * В лево
     *
     * @param integer $nodeId - id документа
     *
     * @return array
     * @throws ErrorException
     */
    public function actionAjaxLeft($nodeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $document = Users::getDocumentByNodeId($nodeId)->one();

        /* @var LinkOrderedBehavior $behavior */
        $behavior = $document->getBehavior('firmTreeAllO');

        return $behavior->levelDown();
    }

    /**
     * Находим модельку по её идентификатору
     *
     * @param integer $currentNodeId - идентификатор модели
     *
     * @return Users
     */
    protected function findModel($currentNodeId)
    {
        if ($currentNodeId === null) {
            $document = new Users();
        } else {
            $document = Users::getDocumentByNodeId($currentNodeId)->one();
        }

        return $document;
    }
}
