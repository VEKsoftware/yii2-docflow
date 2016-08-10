<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 09.08.16
 * Time: 16:12
 */

namespace docflow\controllers;

use docflow\behaviors\LinkSimpleBehavior;
use docflow\behaviors\LinkStructuredBehavior;
use docflow\models\base\Document;
use docflow\models\base\operations\flTree\OperationsFlTree;
use docflow\models\base\operations\flTree\OperationsFlTreeTreeSearch;
use docflow\widgets\FlTreeWidgetWithLeaf;
use docflow\widgets\FlTreeWidgetWithSimpleLinks;
use yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\db\StaleObjectException;
use yii\web\Controller;
use yii\web\Response;

class OperationsController extends Controller
{
    public $defaultAction = 'view';

    /**
     * Добавляем тестовые данные для проверки на работу лога
     *
     * @return void
     */
    public function actionAddFakeData()
    {
        $operation = new OperationsFlTree();

        $operation->operation_type = 'Nope';
        $operation->status_id = 2;
        $operation->unit_real_id = 1;
        $operation->unit_resp_id = 1;

        $operation->save();


        $operation1 = new OperationsFlTree();

        $operation1->operation_type = 'Nope';
        $operation1->status_id = 3;
        $operation1->unit_real_id = 1;
        $operation1->unit_resp_id = 1;

        $operation1->save();

        $operation2 = new OperationsFlTree();

        $operation2->operation_type = 'Nope';
        $operation2->status_id = 4;
        $operation2->unit_real_id = 1;
        $operation2->unit_resp_id = 2;

        $operation2->save();

        $operation3 = new OperationsFlTree();

        $operation3->operation_type = 'Nope';
        $operation3->status_id = 5;
        $operation3->unit_real_id = 1;
        $operation3->unit_resp_id = 2;

        $operation3->save();
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
                'flTreeUrl' => ['operations/ajax-child'],
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
        $document = OperationsFlTree::getDocumentByNodeId($nodeId);

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

        $searchModel = new OperationsFlTreeTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $config = [
            'links' => [
                'documentView' => [
                    'route' => 'operations/view-document',
                    'params' => [
                        'nodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ]
                    ]
                ],
                'next' => [
                    'route' => 'operations/ajax-next',
                    'params' => [
                        'page' => ++$page,
                        'nodeIdValue' => $nodeIdValue,
                    ]
                ],
                'child' => [
                    'route' => 'operations/ajax-child',
                    'params' => [
                        'nodeIdValue' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithLeaf::getStructure($dataProvider, $config);
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

        $searchModel = new OperationsFlTreeTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $config = [
            'links' => [
                'documentView' => [
                    'route' => 'operations/view-document',
                    'params' => [
                        'nodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'next' => [
                    'route' => 'operations/ajax-next',
                    'params' => [
                        'page' => 2,
                        'nodeIdValue' => $nodeIdValue,
                    ]
                ],
                'child' => [
                    'route' => 'operations/ajax-child',
                    'params' => [
                        'nodeIdValue' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ]
            ]
        ];

        return FlTreeWidgetWithLeaf::getStructure($dataProvider, $config);
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

        $searchModel = new OperationsFlTreeTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /* Получаем документы с родительскими связями 1 уровня */
        $parentDocument = OperationsFlTree::getDocumentByNodeId($fromNodeId);
        /* @var OperationsFlTree $parentDocument */
        $simpleBehavior = $parentDocument->getBehavior('simple');

        $config = [
            'simpleLinks' => $simpleBehavior->statusesTransitionTo,
            'nodeIdField' => $document->linkFieldsArray['node_id'],
            'links' => [
                'addSimple' => [
                    'route' => 'operations/ajax-add-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'delSimple' => [
                    'route' => 'operations/ajax-del-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'next' => [
                    'route' => 'operations/ajax-next-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'page' => ++$page,
                        'currentNodeId' => $currentNodeId,
                    ]
                ],
                'child' => [
                    'route' => 'operations/ajax-child-simple',
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

        $searchModel = new OperationsFlTreeTreeSearch();
        $searchModel->document = $document;
        $searchModel->behaviorName = 'structure';
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /* Получаем документы с родительскими связями 1 уровня */
        $parentDocument = OperationsFlTree::getDocumentByNodeId($fromNodeId);
        /* @var OperationsFlTree $parentDocument */
        $simpleBehavior = $parentDocument->getBehavior('simple');

        $config = [
            'simpleLinks' => $simpleBehavior->statusesTransitionTo,
            'nodeIdField' => $document->linkFieldsArray['node_id'],
            'links' => [
                'addSimple' => [
                    'route' => 'operations/ajax-add-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'delSimple' => [
                    'route' => 'operations/ajax-del-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'toNodeId' => [
                            'value' => $document->linkFieldsArray['node_id'],
                            'type' => 'property'
                        ],
                    ]
                ],
                'next' => [
                    'route' => 'operations/ajax-next-simple',
                    'params' => [
                        'fromNodeId' => $fromNodeId,
                        'page' => 2,
                        'currentNodeId' => $currentNodeId,
                    ]
                ],
                'child' => [
                    'route' => 'operations/ajax-child-simple',
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
        $documentFrom = OperationsFlTree::getDocumentByNodeId($fromNodeId);
        /* @var Document $documentTo */
        $documentTo = OperationsFlTree::getDocumentByNodeId($toNodeId);

        /* @var LinkSimpleBehavior $behavior */
        $behavior = $documentFrom->getBehavior('simple');

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
        $documentFrom = OperationsFlTree::getDocumentByNodeId($fromNodeId);
        /* @var Document $documentTo */
        $documentTo = OperationsFlTree::getDocumentByNodeId($toNodeId);

        /* @var LinkSimpleBehavior $behavior */
        $behavior = $documentFrom->getBehavior('simple');

        return $behavior->delSimpleLink($documentTo);
    }

    /**
     * Находим модельку по её идентификатору
     *
     * @param integer $currentNodeId - идентификатор модели
     *
     * @return OperationsFlTree
     */
    protected function findModel($currentNodeId)
    {
        if ($currentNodeId === null) {
            $document = new OperationsFlTree();
        } else {
            $document = OperationsFlTree::getDocumentByNodeId($currentNodeId);
        }

        return $document;
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
        $child = OperationsFlTree::getOperationsByName($childName);

        /* @var Document $parent */
        $parent = OperationsFlTree::getOperationsByName((int)$parentName);

        /* @var LinkStructuredBehavior $behavior */
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
