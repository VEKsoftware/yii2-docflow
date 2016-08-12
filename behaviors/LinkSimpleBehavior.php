<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:15
 *
 * Поведение предназначено для работы с простыми связями.
 * Подключается только к классу - наследнику Documents
 *
 * Обязательные параметры:
 * 1)linkClass - полное имя класса связи
 * 2)documentQuery - callback, содержащий ActiveQuery запрос на получение документов
 *
 * Не обязательные параметры:
 * 1)orderedField  - поле, по которому будет идти упорядочивание
 * 2)indexBy - поле, по которому будет идти индексирование
 *
 * Методы:
 * 1)getDocuments() - получаем документы по переданному в поведение запросу
 * 2)getSimpleLinks() - получение простых связей у документа, к которому прикреплено поведение
 * 3)setSimpleLinks([Obj, Obj, ..]) - массово устанавливаем простые связи между документом, к которому прикреплено поведение
 *                                    и документами переданными в массиве
 * 4)addSimpleLink(Obj) - добавляем простую связь между документом, к которому прикреплено поведение и документом переданным в поведении
 * 5)delSimpleLink(Obj) - удаляем простую связь между документом, к которому прикреплено поведение и документом переданным в поведении
 *
 *
 * Behavior is designed to work with simple links.
 * It connects only to the class - successor Documents
 *
 * Required parameters:
 * 1)linkClass - the full name of the node class
 * 2)documentQuery - callback, containing ActiveQuery request for documents
 *
 * Optional parameters:
 * 1)orderedField  - field on which will go ordering
 * 2)indexBy - field on which will go Indexed
 *
 * Methods:
 * 1)getDocuments() - obtain the documents transmitted to the behavior of the request
 * 2)getSimpleLinks() - obtaining simple links in the document to which the behavior is attached
 * 3)setSimpleLinks([Obj, Obj, ..]) - mass establishes a simple links between the document to which the behavior is attached,
 *                                    and the documents transmitted in the array
 * 4)addSimpleLink(Obj) - add a simple link between the document, which is attached to the conduct and document transmitted in behavior
 * 5)delSimpleLink(Obj) - delete a simple links between the document, which is attached to the conduct and document transmitted in behavior
 */

namespace docflow\behaviors;

use docflow\Docflow;
use docflow\messages\behaviors\BehaviorsMessages;
use docflow\models\base\DocFlowBase;
use docflow\models\base\Document;
use docflow\models\base\Link;
use docflow\models\statuses\Statuses;
use yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\db\Transaction;

class LinkSimpleBehavior extends LinkBaseBehavior
{
    /**
     * Получаем все простые связи для данного документа
     *
     * @return ActiveQuery
     *
     * @throws ErrorException
     */
    public function getSimpleLinks()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_OWNER_NOT_HAS_AVAILABLE);
        }

        return $this->getLinksTransitionsTo();
    }

    /**
     * Массово устанавливаем простые связи между текущим документом (owner) и
     * документами содержащимися в массиве, передаваемом через аргумент.
     *
     * @param array $documentsArray - массив содержит объекты документов,
     *                              к которым будет установлена простая связь от текущего документа
     *
     * @return array
     *
     * @throws ErrorException
     * @throws Exception
     */
    public function setSimpleLinks(array $documentsArray)
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_FROM_SET_NOT_HAS_AVAILABLE);
        }

        if (count($documentsArray) < 1) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENTS_ARRAY_EMPTY);
        }

        /* Проверяем документы содержащиеся в массиве на соответвтвие условиям */
        $this->checkDocumentsArray($documentsArray);

        $return = ['success' => 'Простые связи установлены'];

        /* Назначаем название класса связи на переменную ради удобства */
        $linkClass = $this->linkClass;

        /* @var $transaction Transaction */
        $transaction = Yii::$app->{Docflow::getInstance()->db}->beginTransaction();

        try {
            /* Удаляем все текущие простые связи */
            $linkClass::batchDeleteSimpleLinks($this->owner->{$this->linkFieldsArray['node_id']});

            /* Массово добавляем новые простые связи */
            $linkClass::batchAddSimpleLinks($this->owner, $documentsArray);

            $transaction->commit();
        } catch (\Exception $e) {
            $return = ['error' => $e->getMessage()];
            $transaction->rollBack();
        }

        return $return;
    }

    /**
     * Проверяем документы в массиве на соответствие:
     * 1)Должны принадлежать классу Document.
     * 2)Документ должен присутствовать с списке разрешенных документов.
     * 3)Нельзя создать связь самому с собой.
     * P.S Разрешенные документы - это документы, которые получаем по запросу,
     * переданным ползователем в поведение при инициализации
     *
     * @param array $documentsArray - массив документов
     *
     * @return void
     *
     * @throws \yii\base\ErrorException
     */
    protected function checkDocumentsArray(array $documentsArray)
    {
        foreach ($documentsArray as $value) {
            if (!($value instanceof DocFlowBase)) {
                throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_SET_NOT_INSTANCEOF_DOCUMENT);
            }

            if (($value->{$this->linkFieldsArray['node_id']} === null) || !is_int($value->{$this->linkFieldsArray['node_id']})) {
                throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_SET_NODE_ID_EMPTY_OR_NOT_INT);
            }

            if ($this->owner->{$this->linkFieldsArray['node_id']} === $value->{$this->linkFieldsArray['node_id']}) {
                throw new ErrorException(BehaviorsMessages::U_IF_SET_LINK_BY_SELF);
            }

            if (!array_key_exists($value->{$this->indexBy}, $this->documents)) {
                throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_SET_NOT_HAS_AVAILABLE);
            }
        }
    }

    /**
     * Добавляем простую связь между текущим документом (owner) и документом передаваемом в аргументе
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidConfigException
     */
    public function addSimpleLink($documentObj)
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_FROM_SET_NODE_ID_EMPTY_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_FROM_SET_NOT_HAS_AVAILABLE);
        }

        if (!($documentObj instanceof DocFlowBase)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_SET_NOT_INSTANCEOF_DOCUMENT);
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_SET_NODE_ID_EMPTY_OR_NOT_INT);
        }

        if (!array_key_exists($documentObj->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_SET_NOT_HAS_AVAILABLE);
        }

        $result = ['error' => 'Добавление простой связи не удалось'];

        try {
            if ($this->owner->{$this->linkFieldsArray['node_id']} === $documentObj->{$this->linkFieldsArray['node_id']}) {
                throw new ErrorException(BehaviorsMessages::U_IF_SET_LINK_BY_SELF);
            }

            /* Проверяем, есть ли в БД уже такая связь */
            $statusSimpleLink = $this->getSimpleLinkByDocument($documentObj)->one();

            if (is_object($statusSimpleLink)) {
                throw new ErrorException(BehaviorsMessages::SL_IS_SET);
            }

            /* Сохраняем простую связь */
            $isSave = $this->prepareAndAddSimpleLink($documentObj);

            if ($isSave === true) {
                $result = ['success' => 'Простая связь успешно добавлена'];
            }
        } catch (ErrorException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Подготавливаем и добавляем простую связь
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return bool
     */
    protected function prepareAndAddSimpleLink($documentObj)
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        $relationType = $linkClass::getRelationType();
        $rightTagFormat = '%s.%s.%s';

        /* @var Link $statusLinkClass */
        $statusLinkClass = new $linkClass;
        $statusLinkClass->setScenario($linkClass::LINK_TYPE_SIMPLE);

        $statusLinkClass->{$this->linkFieldsArray['status_from']} = $this->owner->{$this->linkFieldsArray['node_id']};
        $statusLinkClass->{$this->linkFieldsArray['status_to']} = $documentObj->{$this->linkFieldsArray['node_id']};
        $statusLinkClass->{$this->linkFieldsArray['type']} = $linkClass::LINK_TYPE_SIMPLE;

        if (!empty($this->linkFieldsArray['right_tag']) && is_string($this->linkFieldsArray['right_tag'])) {
            $statusLinkClass->{$this->linkFieldsArray['right_tag']} = sprintf(
                $rightTagFormat,
                $this->owner->docType->tag,
                $this->owner->{$this->linkFieldsArray['node_tag']},
                $documentObj->{$this->linkFieldsArray['node_tag']}
            );
        }

        if (!empty($relationType) && is_string($relationType)) {
            $statusLinkClass->{$this->linkFieldsArray['relation_type']} = $relationType;
        }

        return $statusLinkClass->save();
    }

    /**
     * Удаляем простую связь между текущим документом (owner) и документом передаваемом в аргументе
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return array
     *
     * @throws StaleObjectException
     * @throws \Exception
     * @throws ErrorException
     */
    public function delSimpleLink($documentObj)
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_FROM_DEL_NODE_ID_EMPTY_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_FROM_DEL_NOT_HAS_AVAILABLE);
        }

        if (!($documentObj instanceof DocFlowBase)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_DEL_NOT_INSTANCEOF_DOCUMENT);
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_DEL_NODE_ID_EMPTY_OR_NOT_INT);
        }

        if (!array_key_exists($documentObj->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::SL_DOCUMENT_TO_DEL_NOT_HAS_AVAILABLE);
        }

        $result = ['error' => 'Просатя связь не удалена'];

        /* Проверяем, есть ли в БД уже такая связь */
        $statusSimpleLink = $this->getSimpleLinkByDocument($documentObj)->one();

        if (!is_object($statusSimpleLink)) {
            throw new ErrorException(BehaviorsMessages::SL_NOT_SET);
        }

        /* Удаляем простую связь */
        $isDelete = $statusSimpleLink->delete();

        if ((bool)$isDelete === true) {
            $result = ['success' => 'Простая связь удалена'];
        }

        return $result;
    }

    /**
     * Получаем простую связь между текущим документом (owner) и документом передаваемом в аргументе
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return ActiveQuery
     */
    public function getSimpleLinkByDocument($documentObj)
    {
        return $this->getLinksTransitionsTo()
            ->andWhere(
                ['=', $this->linkFieldsArray['status_to'], $documentObj->{$this->linkFieldsArray['node_id']}]
            );
    }

    /**
     * Получаем документы, которые записаны в поле status_to
     *
     * @return ActiveQuery
     */
    public function getStatusesTransitionTo()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via(
                'linksTo',
                function (ActiveQuery $query) use ($linkClass) {
                    $query->andOnCondition($linkClass::extraWhere());
                }
            )
            ->indexBy($this->indexBy);
    }

    /**
     * Получаем документы, которые записаны в поле status_from
     *
     * @return ActiveQuery
     */
    public function getStatusesTransitionFrom()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via(
                'linksFrom',
                function (ActiveQuery $query) use ($linkClass) {
                    $query->andOnCondition($linkClass::extraWhere());
                }
            )
            ->indexBy($this->indexBy);
    }

    /**
     * The method returns a list of Transitions links leading to the source statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksTransitionsFrom()
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksFrom()
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * The method returns a list of Transitions links leading to the target statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksTransitionsTo()
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksTo()
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * Находим простые связи между текущим документом и документами, переданными в аргумент массивом
     *
     * @param array $list - список документов вложенных в корневой статус
     *
     * @return ActiveQuery
     */
    public function getSimpleLinksByList(array $list)
    {
        return $this->getLinksTransitionsTo()
            ->andWhere(['in', $this->linkFieldsArray['status_to'], $list]);
    }
}
