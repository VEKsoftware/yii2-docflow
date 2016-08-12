<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 24.06.16
 * Time: 10:30
 *
 * Данное поведение содержит:
 * - базовые методы доступа к БД
 * - первичную проверку и настройка свойств поведений
 */

namespace docflow\behaviors;

use Closure;
use docflow\base\ActivePropertiesBehavior;
use docflow\messages\behaviors\BehaviorsMessages;
use docflow\models\base\DocFlowBase;
use docflow\models\base\Document;
use docflow\models\statuses\Statuses;
use yii\base\ErrorException;
use yii\db\ActiveQuery;

class LinkBaseBehavior extends ActivePropertiesBehavior
{
    /**
     * Владелей документа
     *
     * @var Statuses|Document
     */
    public $owner;

    /**
     * Имя класса связи - обязательное свойство
     *
     * @var string
     */
    public $linkClass;

    /**
     * Массив, содержащий имена полей в таблице со связями - формируется в attach
     *
     * @var array
     */
    public $linkFieldsArray;

    /**
     * Callback, содержащий запрос ActiveQuery - обязательное свойство
     *
     * @var Closure
     */
    public $documentQuery;

    /**
     * Поле, которое отвечает сотрировку в таблице - обязательное свойство для LinksOrderedBehavior
     *
     * @var string
     */
    public $orderedFieldDb;

    /**
     * Поле, которое содержит имя свойства объекта,
     * в котором содержится значние сортировки - обязательное свойство для LinksOrderedBehavior
     *
     * @var string
     */
    public $orderedFieldValue;

    /**
     * Поле, по которому будет идти индексирование - обязательное свойство
     *
     * @var string
     */
    public $indexBy;

    /**
     * Условия дополнителоьной фильтрации из внешних источников
     *
     * @var array
     */
    public $extraFilter = [];

    public function attach($owner)
    {
        parent::attach($owner);

        $this->setLinkFieldsArray();

        if (!($owner instanceof DocFlowBase)) {
            throw new ErrorException(BehaviorsMessages::B_OWNER_NOT_DOCUMENT);
        }

        /* Проверяем все обязательные параметры на наличие */
        $this->processRequiredParameters();

        /*
        if (empty($this->linkClass) || !($this->linkObject instanceof Link)) {
            throw new ErrorException('Отсутствует класс связей или не принадлежит Link');
        }*/
    }

    /**
     * Устанавливаем имена полей таблицы со связями
     *
     * @return void
     */
    protected function setLinkFieldsArray()
    {
        $linkClass = $this->linkClass;

        $this->linkFieldsArray = [
            'status_to' => $linkClass::$_fieldLinkTo,
            'status_from' => $linkClass::$_fieldLinkFrom,
            'type' => $linkClass::$_typeField,
            'node_id' => $linkClass::$_fieldNodeId,
            'node_tag' => $linkClass::$_fieldNodeTag
        ];

        if (!empty($linkClass::$_rightTagField)) {
            $this->linkFieldsArray['right_tag'] = $linkClass::$_rightTagField;
        }

        if (!empty($linkClass::$_levelField)) {
            $this->linkFieldsArray['level'] = $linkClass::$_levelField;
        }

        if (!empty($linkClass::$_relationTypeField)) {
            $this->linkFieldsArray['relation_type'] = $linkClass::$_relationTypeField;
        }
    }

    /**
     * The method returns a list of all links leading to the source statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksFrom()
    {
        return $this->owner
            ->hasMany(
                $this->linkClass,
                [$this->linkFieldsArray['status_to'] => $this->linkFieldsArray['node_id']]
            );
    }

    /**
     * The method returns a list of all links leading to the target statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTo()
    {
        return $this->owner
            ->hasMany(
                $this->linkClass,
                [$this->linkFieldsArray['status_from'] => $this->linkFieldsArray['node_id']]
            );
    }

    /**
     * Получаем связи по переданному в аргументе статусу
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return mixed
     */
    public function getLinksFromByStatus($documentObj)
    {
        return $documentObj
            ->hasMany(
                $this->linkClass,
                [$this->linkFieldsArray['status_to'] => $this->linkFieldsArray['node_id']]
            );
    }

    /**
     * Получаем связи по переданному в аргументе статусу
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return mixed
     */
    public function getLinksToByStatus($documentObj)
    {
        return $documentObj
            ->hasMany(
                $this->linkClass,
                [$this->linkFieldsArray['status_from'] => $this->linkFieldsArray['node_id']]
            );
    }

    /**
     * Получаем документы по переданному в поведения запросу
     *
     * @param ActiveQuery $query - запрос
     *
     * @return ActiveQuery
     */
    public function getDocuments(ActiveQuery $query = null)
    {
        $owner = $this->owner;

        if ($query === null) {
            $query = $owner::find();
        }

        return call_user_func($this->documentQuery, $query)
            ->andWhere($this->extraFilter)
            ->indexBy($this->indexBy);
    }

    /**
     * Проверяем на наличие всех обязательных праметров при инициализации поведения
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected function processRequiredParameters()
    {
        if (empty($this->linkClass) || !is_string($this->linkClass)) {
            throw new ErrorException(BehaviorsMessages::B_LINK_CLASS_EMPTY_OR_NOT_STRING);
        }

        if (($this->documentQuery === null) || !($this->documentQuery instanceof Closure)) {
            throw new ErrorException(BehaviorsMessages::B_DOCUMENT_QUERY_NULL_OR_NOT_INSTANCEOF_CLOSURE);
        }

        if (($this instanceof LinkOrderedBehavior) && (empty($this->orderedFieldDb) || !is_string($this->orderedFieldDb))) {
            throw new ErrorException('Отутствует обязательный параметр orderedFieldDb при объявлении поведения');
        }

        if (($this instanceof LinkOrderedBehavior) && (empty($this->orderedFieldValue) || !is_string($this->orderedFieldValue))) {
            throw new ErrorException('Отутствует обязательный параметр orderedFieldValue при объявлении поведения');
        }

        if (empty($this->indexBy) || !is_string($this->indexBy)) {
            throw new ErrorException('Отутствует обязательный параметр indexBy при объявлении поведения');
        }
    }
}
