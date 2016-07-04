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
use docflow\messages\behaviors\BehaviorsMessages;
use docflow\models\Document;
use docflow\models\Statuses;
use yii\base\Behavior;
use yii\base\ErrorException;
use yii\db\ActiveQuery;

class LinkBaseBehavior extends Behavior
{
    /**
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
     * Callback, содержащий запрос ActiveQuery
     *
     * @var Closure
     */
    public $documentQuery;

    /**
     * Массив, содержащий доступные документы
     *
     * @var array
     */
    public $availableDocuments;

    /**
     * Поле, по которому будет идти сортировка
     *
     * @var string
     */
    public $orderedField = 'order_idx';

    /**
     * Поле, по которому будет идти индексирование
     *
     * @var string
     */
    public $indexBy = 'tag';

    public function attach($owner)
    {
        parent::attach($owner);

        $this->setLinkFieldsArray();

        if (!($owner instanceof Document)) {
            throw new ErrorException(BehaviorsMessages::B_OWNER_NOT_DOCUMENT);
        }

        if (empty($this->linkClass) || !is_string($this->linkClass)) {
            throw new ErrorException(BehaviorsMessages::B_LINK_CLASS_EMPTY_OR_NOT_STRING);
        }

        if (($this->documentQuery === null) || !($this->documentQuery instanceof Closure)) {
            throw new ErrorException(BehaviorsMessages::B_DOCUMENT_QUERY_NULL_OR_NOT_INSTANCEOF_CLOSURE);
        }

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
     * @return ActiveQuery
     */
    public function getDocuments()
    {
        return call_user_func($this->documentQuery)
            ->indexBy($this->indexBy);
    }

    /**
     * Получаем массив доступных документов.
     * Если свойство пусто, то получаем из базы
     *
     * @return array
     */
    public function getAvailableDocuments()
    {
        if ($this->availableDocuments === null) {
            $this->availableDocuments = $this->getDocuments()->all();
        }

        return $this->availableDocuments;
    }
}