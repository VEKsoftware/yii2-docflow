<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:15
 */

namespace docflow\behaviors;

use docflow\models\Document;
use docflow\models\Link;
use docflow\models\Statuses;
use yii;
use yii\base\Behavior;
use yii\base\ErrorException;

class LinkSimpleBehavior extends Behavior
{
    /**
     * @var Statuses|Document
     */
    public $owner;

    /**
     * Имя класса связи
     *
     * @var object
     */
    public $linkClass;

    /**
     * Массив, содержащий имена полей в таблице со связями
     *
     * @var array
     */
    public $linkFieldsArray;

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

        if (!($owner instanceof Statuses)) {
            throw new ErrorException('Класс узла не принадлежит Statuses');
        }

        if (empty($this->linkClass)) {
            throw new ErrorException('Отсутствует класс связей');
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
     * Получаем все простые связи для данного документа
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @throws \yii\base\ErrorException
     */
    public function getSimpleLinks()
    {
        if (!is_int($this->owner->id) || empty($this->owner->id)) {
            throw new ErrorException('Id статуса From не integer типа или пустой');
        }

        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $linkClass,
                [$this->linkFieldsArray['status_from'] => $this->linkFieldsArray['node_id']]
            )
            ->where(['=', $this->linkFieldsArray['status_from'], $this->owner->id])
            ->andWhere($linkClass::extraWhere());
    }

    /**
     * Массово устанавливаем простые связи
     *
     * @param array $tagsToArray - массив с объектами статусов To пуст
     *
     * @return void
     *
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     */
    public function setSimpleLinks(array $tagsToArray)
    {
        if (!is_string($this->owner->docType->tag) || empty($this->owner->docType->tag)) {
            throw new ErrorException('Тэг документа не строкового типа или пустой');
        }

        if (!is_string($this->owner->tag) || empty($this->owner->tag)) {
            throw new ErrorException('Тэг статуса From не строкового типа или пустой');
        }

        if (count(($tagsToArray)) < 1) {
            throw new ErrorException('Массив с объектами статусов пуст');
        }

        /* Удаляем все текущие простые связи */
        Link::batchDeleteSimpleLinks($this->owner->id);

        /* Массово добавляем */
        Link::batchAddSimpleLinks($this->owner, $tagsToArray);
    }

    /**
     * Добавляем простую связь между документами From и To
     *
     * @param object $statusObj - Стутаус
     *
     * @return array
     *
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function addSimpleLink($statusObj)
    {

        if (!($statusObj instanceof Statuses)) {
            throw new ErrorException('Аргумент не объект Statuses');
        }

        if (!is_string($this->owner->docType->tag) || empty($this->owner->docType->tag)) {
            throw new ErrorException('Тэг документа не строкового типа или пустой');
        }

        if (!is_int($this->owner->id) || empty($this->owner->id)) {
            throw new ErrorException('Id статуса From не integer типа или пустой');
        }

        if (!is_int($statusObj->id) || empty($statusObj->id)) {
            throw new ErrorException('Id статуса To не integer типа или пустой');
        }

        if ($this->owner->id === $statusObj->id) {
            throw new ErrorException('Нельзя назначить ссылку на себя');
        }

        $result = ['error' => 'Добавление не удалось'];

        /* Смотрим, есть ли в БД уже такая ссылка */
        $statusSimpleLink = $this->getSimpleLinkForStatusFromIdAndStatusToId(
            $this->owner->id,
            $statusObj->id
        )->one();

        if (is_object($statusSimpleLink)) {
            throw new ErrorException('Ссылка уже добавлена');
        }

        $relationType = Link::getRelationType();

        $isSave = $this->prepareAndAddSimpleLink($statusObj, $relationType);

        if ($isSave === true) {
            $result = ['success' => 'Ссылка добавлена'];
        }

        return $result;
    }

    /**
     * Подготавливаем и добавляем простую связь
     *
     * @param Statuses $statusObj    - Объект статуса
     * @param string   $relationType - указан relation_type
     *
     * @return bool
     */
    protected function prepareAndAddSimpleLink($statusObj, $relationType)
    {
        /**
         * @var Link $statusLinkClass
         */
        $statusLinkClass = new $this->linkClass;
        $statusLinkClass->setScenario(Link::LINK_TYPE_SIMPLE);

        $statusLinkClass->{$this->linkFieldsArray['status_from']} = $this->owner->id;
        $statusLinkClass->{$this->linkFieldsArray['status_to']} = $statusObj->id;
        $statusLinkClass->{$this->linkFieldsArray['type']} = Link::LINK_TYPE_SIMPLE;
        $statusLinkClass->{$this->linkFieldsArray['right_tag']} = $this->owner->docType->tag . '.' . $this->owner->tag . '.' . $statusObj->tag;

        if (!empty($relationType) && is_string($relationType)) {
            $statusLinkClass->{$this->linkFieldsArray['relation_type']} = $relationType;
        }

        return $statusLinkClass->save();
    }

    /**
     * Удаляем простую связь между документами From и To
     *
     * @param object $statusObj - Документ "TO". Документ "From" - это $this->owner
     *
     * @return array
     *
     * @throws \Exception
     */
    public function delSimpleLink($statusObj)
    {
        if (!($statusObj instanceof Statuses)) {
            throw new ErrorException('Аргумент не объект Statuses');
        }

        if (!is_int($this->owner->id) || empty($this->owner->id)) {
            throw new ErrorException('Id статуса From не integer типа или пуста');
        }

        if (!is_int($statusObj->id) || empty($statusObj->id)) {
            throw new ErrorException('Id статуса To не integer типа или пуста');
        }

        $result = ['error' => 'Удаление не удалось'];

        /* Получаем простую связь */
        $statusSimpleLink = $this->getSimpleLinkForStatusFromIdAndStatusToId(
            $this->owner->id,
            $statusObj->id
        )->one();

        if (!is_object($statusSimpleLink)) {
            throw new ErrorException('Ссылка не найдена');
        }

        $isDelete = $statusSimpleLink->delete();

        if ((bool)$isDelete === true) {
            $result = ['success' => 'Ссылка удалена'];
        }

        return $result;
    }

    /**
     * Получаем SimpleLink по id документов From и To
     *
     * @param integer $fromStatusId - тэг статуса From
     * @param integer $toStatusId   - тэг статуса To
     *
     * @return array|null|\yii\db\ActiveQuery
     */
    protected function getSimpleLinkForStatusFromIdAndStatusToId($fromStatusId, $toStatusId)
    {
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasOne(
                $linkClass,
                [$this->linkFieldsArray['status_from'] => $this->linkFieldsArray['node_id']]
            )
            ->where(
                [
                    'and',
                    ['=', $this->linkFieldsArray['status_from'], $fromStatusId],
                    ['=', $this->linkFieldsArray['status_to'], $toStatusId],
                ]
            )
            ->andWhere($linkClass::extraWhere());
    }

    /**
     * Получаем простые ссылки для статуса и определенных подстатусов
     *
     * @param integer $statusId   - id статуса
     * @param array   $tagToArray - массив подстатусов
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getSimpleLinksByTagFromIdWhereTagToArray($statusId, array $tagToArray)
    {
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $linkClass,
                [$this->linkFieldsArray['status_from'] => $this->linkFieldsArray['node_id']]
            )
            ->where(
                [
                    'and',
                    ['=', $this->linkFieldsArray['status_from'], $statusId],
                    ['in', $this->linkFieldsArray['status_to'], $tagToArray]
                ]
            )
            ->andWhere($linkClass::extraWhere());
    }
}
