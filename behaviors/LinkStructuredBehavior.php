<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:15
 *
 * Поведение предназначено для работы с fltree связями
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
 * 2)removeParents() - удаление родителей у документа, к которому прикреплено поведение
 * 3)getParents() - получение родителей у документа, к которому прикреплено поведение
 * 4)setParent(Obj) - устанавливаем нового родителя документу, к которому прикреплено поведение
 * 5)getChildes() - получение детей у документа, к которому прикреплено поведение
 * 6)setChild(Obj) - добавляем нового ребенка документу, к которому прикреплено поведение
 *
 *
 * Behavior is designed to work with fltree links
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
 * 2)removeParents() - removal of the parents from the document, to which is attached the behavior
 * 3)getParents() - parents receive in the document to which the behavior is attached
 * 4)setParent(Obj) - set a new parent document, which is attached behavior
 * 5)getChildes() - getting children in the document to which the behavior is attached
 * 6)setChild(Obj) - add a new child to the document, which is attached behavior
 */

namespace docflow\behaviors;

use docflow\messages\behaviors\BehaviorsMessages;
use docflow\models\Document;
use docflow\models\Link;
use docflow\models\Statuses;
use Exception;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\db\ActiveQuery;
use yii\db\StaleObjectException;
use yii\helpers\Url;

class LinkStructuredBehavior extends LinkBaseBehavior
{
    /**
     * Удаляем все родительские связи у документа - переносим документ в корень
     *
     * @return void
     *
     * @throws StaleObjectException
     * @throws Exception
     * @throws ErrorException
     */
    public function removeParents()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_OWNER_DEL_PARENT_NOT_HAS_AVAILABLE);
        }

        /**
         * Получаем родительскую связь с ближайшим родителем
         *
         * @var Link $flTreeLink
         */
        $flTreeLink = $this->getLinksParent()->one();

        if (empty($flTreeLink->id)) {
            throw new ErrorException(BehaviorsMessages::STR_PARENT_LINK_1_LVL_NOT_SET);
        }

        $flTreeLink->delete();
    }

    /**
     * Получаем все документы, которые являются родительскими по отношению к текущему (owner) документу
     *
     * @return ActiveQuery
     *
     * @throws ErrorException
     */
    public function getParents()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_OWNER_GET_PARENT_NOT_HAS_AVAILABLE);
        }

        $owner = $this->owner;
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksFrom', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere());
            })
            ->indexBy('tag');
    }

    /**
     * Устанавливаем нового родителя текущему документу (owner)
     *
     * @param Statuses|Document $documentObj - Объект документа, новый родитель
     *
     * @return void
     *
     * @throws ErrorException
     * @throws InvalidConfigException
     */
    public function setParent($documentObj)
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_FROM_SET_PARENT_NOT_HAS_AVAILABLE);
        }

        if (!($documentObj instanceof Document)) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_TO_SET_PARENT_NOT_INSTANCEOF_DOCUMENT);
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_TO_SET_PARENT_NODE_ID_EMPTY_OR_NOT_INT);
        }

        if (!array_key_exists($documentObj->tag, $this->getAvailableDocuments())) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_TO_SET_PARENT_NOT_HAS_AVAILABLE);
        }

        if ($this->owner->{$this->linkFieldsArray['node_id']} === $documentObj->{$this->linkFieldsArray['node_id']}) {
            throw new ErrorException(BehaviorsMessages::U_IF_SET_LINK_BY_SELF);
        }

        /* Получаем детей у текущего документа */
        $childes = $this->getChildes()->all();

        if (array_key_exists($documentObj->tag, $childes)) {
            throw new ErrorException(BehaviorsMessages::STR_DENIED_SET_ONE_OF_CHILDES_HOW_PARENT);
        }

        /**
         * Получаем ближайшую родительскую связь для текущего документа (owner)
         *
         * @var Link $flTreeLink
         */
        $flTreeLink = $this->getParentLinkByStatus($this->owner)->one();

        /**
         * В зависимости от того, существует ли связь между документами или нет, будет:
         * 1)Если связи нет, то будет создана новая
         * 2)Если связь есть, то будет обновлена
         */
        if (empty($flTreeLink->id)) {
            /* Если отсутствует ближайшая родительская связь, то создаем новую */
            $this->prepareAndAddFlTreeLinks($documentObj);
        } else {
            if ($flTreeLink->{$this->linkFieldsArray['status_from']} === $documentObj->{$this->linkFieldsArray['node_id']}) {
                throw new ErrorException(BehaviorsMessages::STR_NEW_PARENT_IS_CURRENT);
            }

            /* Меняем родителя */
            $this->prepareAndUpdateFlTreeLinks($flTreeLink, $documentObj);
        }
    }

    /**
     * Получаем все дочерние документы текущего документа (owner)
     *
     * @return ActiveQuery
     *
     * @throws ErrorException
     */
    public function getChildes()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_OWNER_GET_CHILD_NOT_HAS_AVAILABLE);
        }

        $owner = $this->owner;
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTo', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere());
            })
            ->indexBy('tag');
    }

    /**
     * Добавляем к текущему документу (owner) дочерний документ, который передан через аргумент
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return void
     *
     * @throws ErrorException
     * @throws InvalidConfigException
     */
    public function setChild($documentObj)
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_FROM_SET_CHILD_NOT_HAS_AVAILABLE);
        }

        if (!($documentObj instanceof Document)) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_TO_SET_CHILD_NOT_INSTANCEOF_DOCUMENT);
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_TO_SET_CHILD_NODE_ID_EMPTY_OR_NOT_INT);
        }

        if (!array_key_exists($documentObj->tag, $this->getAvailableDocuments())) {
            throw new ErrorException(BehaviorsMessages::STR_DOCUMENT_TO_SET_CHILD_NOT_HAS_AVAILABLE);
        }

        if ($this->owner->{$this->linkFieldsArray['node_id']} === $documentObj->{$this->linkFieldsArray['node_id']}) {
            throw new ErrorException(BehaviorsMessages::U_IF_SET_LINK_BY_SELF);
        }

        /* Получаем родителей у текущего документа */
        $parents = $this->getParents()->all();

        if (array_key_exists($documentObj->tag, $parents)) {
            throw new ErrorException(BehaviorsMessages::STR_DENIED_SET_ONE_OF_PARENTS_HOW_CHILD);
        }

        /**
         * Получаем ближайшую родительскую связь нового дочернего документа
         *
         * @var Link $flTreeLink
         */
        $flTreeLink = $this->getParentLinkByStatus($documentObj)->one();

        if (empty($flTreeLink->id)) {
            /* Если ближайшая родительская связь отсутствует, то создаём новую */
            $this->prepareAndAddFlTreeLinks($documentObj, false);
        } else {
            if ($flTreeLink->{$this->linkFieldsArray['status_from']} === $this->owner->{$this->linkFieldsArray['node_id']}) {
                throw new ErrorException(BehaviorsMessages::STR_DENIED_SET_ONE_OF_PARENTS_HOW_CHILD);
            }

            /* Меняем родителя */
            $this->prepareAndUpdateFlTreeLinks($flTreeLink, $this->owner);
        }
    }


    /**
     * Подготавливаем и добавляем flTree связь
     *
     * @param Statuses $documentObj - Объект документа
     * @param bool     $parent      - Индикатор, показывающий что мы добавляем:
     *                              true - создаем родительскую связь,
     *                              false - создаем дочернюю связь
     *
     * @return void
     */
    protected function prepareAndAddFlTreeLinks($documentObj, $parent = true)
    {
        /* Назначаем название класса связи на переменную ради удобства */
        $linkClass = $this->linkClass;
        /**
         * @var Link $statusesLinksClass
         */
        $statusesLinksClass = new $linkClass;

        $statusesLinksClass->setScenario($linkClass::LINK_TYPE_FLTREE);

        /* В зависимости от того какую связь мы добавляем (родительскую или дочернюю) определятся значения полей */
        if ($parent === true) {
            $statusesLinksClass->{$this->linkFieldsArray['status_from']} = $documentObj->{$this->linkFieldsArray['node_id']};
            $statusesLinksClass->{$this->linkFieldsArray['status_to']} = $this->owner->{$this->linkFieldsArray['node_id']};
        } else {
            $statusesLinksClass->{$this->linkFieldsArray['status_from']} = $this->owner->{$this->linkFieldsArray['node_id']};
            $statusesLinksClass->{$this->linkFieldsArray['status_to']} = $documentObj->{$this->linkFieldsArray['node_id']};
        }

        $statusesLinksClass->{$this->linkFieldsArray['type']} = $linkClass::LINK_TYPE_FLTREE;
        $statusesLinksClass->{$this->linkFieldsArray['level']} = 1;

        $relationType = $linkClass::getRelationType();
        if (!empty($relationType) && is_string($relationType)) {
            $statusesLinksClass->{$this->linkFieldsArray['relation_type']} = $relationType;
        }

        /* Сохраняем изменеия */
        $statusesLinksClass->save();
    }

    /**
     * Подготавливаем и обновляем flTree связь
     *
     * @param Link              $flTreeLink  - Объект связи типа flTree
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return void
     */
    protected function prepareAndUpdateFlTreeLinks($flTreeLink, $documentObj)
    {
        /* Назначаем название класса связи на переменную ради удобства */
        $linkClass = $this->linkClass;

        /* Меняем id родетеля */
        $flTreeLink->{$this->linkFieldsArray['status_from']} = $documentObj->{$this->linkFieldsArray['node_id']};

        $flTreeLink->setScenario($linkClass::LINK_TYPE_FLTREE);

        /* Сохраняем изменения */
        $flTreeLink->save();
    }

    /**
     * Получаем структуру дерева
     *
     * @return array
     */
    public function getTree()
    {
        return array_map([$this, 'treeBranch'], $this->owner->docType->statusesStructure);
    }

    /**
     * Формируем ветви
     *
     * @param mixed $val Ветка
     *
     * @return array
     *
     * @throws InvalidParamException
     */
    protected function treeBranch($val)
    {
        return array_merge(
            [
                'text' => $val->name,
                'href' => Url::to(['status-view', 'doc' => $val->docType->tag, 'tag' => $val->tag]),
            ],
            (empty($val->statusChildren))
                ? []
                : ['nodes' => array_map([$this, 'treeBranch'], $val->statusChildren)]
        );
    }

    /**
     * Получаем детей статуса
     *
     * @return ActiveQuery
     */
    public function getStatusChildren()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        $query = $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTo', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere())
                    ->andOnCondition([$this->linkFieldsArray['level'] => 1]);
            })
            ->inverseOf('statusParent');

        if (!empty($this->orderedField) && is_string($this->orderedField)) {
            $query->orderBy([
                $this->orderedField => SORT_ASC
            ]);
        }

        return $query;
    }

    /**
     * The method returns a structure link with level=1 leading to the source statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksParent()
    {
        return $this->getLinksStructureFrom()
            ->andOnCondition([$this->linkFieldsArray['level'] => 1]);
    }

    /**
     * The method returns a list of structure links with level=1 leading to the target statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksChildren()
    {
        return $this->getLinksStructureTo()
            ->andOnCondition([$this->linkFieldsArray['level'] => 1]);
    }

    /**
     * @return ActiveQuery
     */
    public function getStatusesTo()
    {
        $owner = $this->owner;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTo')
            ->indexBy('tag');
    }

    /**
     * @return ActiveQuery
     */
    public function getStatusesLower()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTo', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere());
            })
            ->indexBy('tag');
    }

    /**
     * @return ActiveQuery
     */
    public function getStatusesUpper()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksFrom', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere());
            })
            ->indexBy('tag');
    }

    /**
     * Получаем родительский документ текущего документа
     *
     * @return ActiveQuery
     */
    public function getStatusParent()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasOne(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksParent', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere())
                    ->andOnCondition([$this->linkFieldsArray['level'] => 1]);
            });
    }

    /**
     * Получаем родительскую связь 1 уровня(т.е ближайшую) для объекта переданного в аргументе
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return ActiveQuery
     */
    public function getParentLinkByStatus($documentObj)
    {
        return $this->getLinksStructureFromByStatus($documentObj)
            ->andWhere(
                ['=', $this->linkFieldsArray['level'], 1]
            );
    }

    /**
     * The method returns a list of structure links leading to the source statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksStructureFrom()
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksFrom()
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * The method returns a list of structure links leading to the target statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksStructureTo()
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksTo()
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * Получаем связи и фильтруем по условиям, указанным в extraWhere
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return ActiveQuery
     */
    public function getLinksStructureFromByStatus($documentObj)
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksFromByStatus($documentObj)
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * Получаем связи и фильтруем по условиям, указанным в extraWhere
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return ActiveQuery
     */
    public function getLinksStructureToByStatus($documentObj)
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksToByStatus($documentObj)
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * Получаем все статусы со связью с родителем
     *
     * @return ActiveQuery
     */
    public function getDocumentsWithFlTreeLinks1Level()
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getDocuments()
            ->orderBy([$this->orderedField => SORT_ASC])
            ->with([
                'linksFrom' => function (ActiveQuery $query) use ($linkClass) {
                    $query->andOnCondition($linkClass::extraWhere())
                        ->andOnCondition([$this->linkFieldsArray['level'] => 1]);
                }
            ]);
    }

    /**
     * Получаем родительские связи 1 и 2 уровня
     *
     * @return ActiveQuery
     */
    public function getParentLinks1And2Levels()
    {
        return $this->getLinksStructureFrom()
            ->andWhere(['in', $this->linkFieldsArray['level'], [1, 2]])
            ->indexBy($this->linkFieldsArray['level']);
    }

    /**
     * Получаем родительские документы 1 и 2 уровня
     *
     * @return ActiveQuery
     */
    public function getParentDocuments1And2Levels()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksFrom', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere())
                    ->andWhere(['in', $this->linkFieldsArray['level'], [1, 2]])
                    ->indexBy($this->linkFieldsArray['level']);
            })
            ->indexBy($this->linkFieldsArray['node_id']);
    }
}
