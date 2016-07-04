<?php

/**
 * Поведение предназначено для работы со статусами документа.
 *
 * Обязательные параметры:
 * 1)statusRootTag - тэг корневого статуса
 *
 * Не обязательные параметры:
 * 1)statusIdField - поле, в котором указывается значение статуса
 *
 * Методы:
 * 1)getStatus() - получаем текущий статус документа, к которому прикреплено поведение
 * 2)getAllStatuses() - получаем статусы, которые являются дочерними корневого статуса
 * 3)setStatus(Obj) - устанавливаем новый статус документу, к которому прикреплено поведение
 * 4)setStatusSafe(Obj) -устанавливаем новый статус документу, к которому прикреплено поведение, без проверки на право установки
 * 5)getAvailableStatuses() - получаем статусы, на которые можно сменить текущий статус:
 *                            1)дочерние корневого статуса
 *                            2)имеют простую связь к статусу, к которому прикреплено поведение
 *                            3)имеют тэг доступа
 *                            4)разрешен доступ по тэгу доутупа
 *
 *
 * Behavior is designed to work with the status of the document.
 *
 * Required parameters:
 * 1)statusRootTag - root tag status
 *
 * Optional parameters:
 * 1)statusIdField - field, which indicates the status value
 *
 * Methods:
 * 1)getStatus() - get the current status of the document, which is attached behavior
 * 2)getAllStatuses() - acquires the status of that are children of the root status
 * 3)setStatus(Obj) - establish a new status document, which is attached behavior
 * 4)setStatusSafe(Obj) - establish a new status document, which is attached to the behavior, without checking for the right to set
 * 5)getAvailableStatuses() - receive the statuses, which can change the current status:
 *                            1)the child of the root status
 *                            2)have a simple links to the status to which the behavior is attached
 *                            3)have right tag
 *                            4) allow access for right tag
 */

namespace docflow\behaviors;

use docflow\messages\behaviors\BehaviorsMessages;
use yii;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\base\ErrorException;

use yii\helpers\ArrayHelper;

use docflow\models\Document;
use docflow\models\Statuses;

/**
 * Behavior class for checking access to set status field and setting it if accessed.
 *
 * @property integer  statusIdField Название поля со статусом в таблице
 * @property Statuses status
 */
class StatusBehavior extends Behavior
{
    /**
     * The owner of this behavior
     *
     * @var ActiveRecord
     */
    public $owner;

    /**
     * The name of a field in the table for ID of the status
     *
     * @var string
     */
    public $statusIdField = 'status_id';

    /**
     * Корневой статус - обязательный параметр
     *
     * @var string
     */
    public $statusRootTag;

    /**
     * Объект корневого статуса
     *
     * @var Statuses
     */
    protected $statusRootObj;

    /**
     * @inheritdoc
     * @throws ErrorException
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if (!$owner instanceof Document) {
            throw new ErrorException(BehaviorsMessages::STAT_STATUS_OWNER_NOT_INSTANCEOF_DOCUMENT);
        }

        if (empty($this->statusRootTag)) {
            throw new ErrorException(BehaviorsMessages::STAT_PROPERTY_STATUS_ROOT_TAG_IS_EMPTY . $owner::className());
        }

        /* Получаем объект корневого статуса */
        $this->statusRootObj = $this->findAndSetStatusRootObj();
    }

    public function events()
    {
        return [
            Statuses::EVENT_AFTER_FIND => 'checkWhatStatusFromRootStatus'
        ];
    }

    /**
     * Находим корневой статус по тэгу
     *
     * @return integer
     *
     * @throws ErrorException
     */
    protected function findAndSetStatusRootObj()
    {
        $statusesObj = $this->owner->doc->statuses;

        if (!array_key_exists($this->statusRootTag, $statusesObj)) {
            throw new ErrorException(BehaviorsMessages::STAT_STATUS_ROOT_NOT_FOUND);
        }

        return $statusesObj[$this->statusRootTag];
    }

    /**
     * Проверяем, является-ли текущий статус дочерним статусом корневого
     *
     * @return void
     *
     * @throws ErrorException
     */
    public function checkWhatStatusFromRootStatus()
    {
        $key = array_search(
            $this->owner->{$this->statusIdField},
            array_column($this->getAllStatuses()->all(), 'id')
        );

        if ($key === false) {
            throw new ErrorException(BehaviorsMessages::STAT_STATUS_OWNER_NOT_ONE_OF_CHILD_ROOT_STATUS);
        }
    }

    /**
     * Проверяем, является-ли передаваемый id статуса дочерним корневого статуса
     *
     * @param integer $statusId - id статуса
     *
     * @return bool : true - статус дочерний, false - статус не дочерний
     */
    protected function hasWhatStatusTheChildRootStatus($statusId)
    {
        $key = array_search(
            $statusId,
            array_column($this->getAllStatuses()->all(), 'id')
        );

        $return = true;

        if ($key === false) {
            $return = false;
        }

        return $return;
    }

    /**
     * Метод возвращает текущий устанвленный статус
     *
     * @return ActiveQuery
     *
     * @throws ErrorException
     */
    public function getStatus()
    {
        if (($this->owner->{$this->statusIdField} === null) || (!is_int($this->owner->{$this->statusIdField}))) {
            throw new ErrorException(BehaviorsMessages::STAT_STATUS_OWNER_IS_EMPTY);
        }

        return Statuses::getStatusById($this->owner->{$this->statusIdField});
    }

    /**
     * Получаем массив всех статусов потомков корневого статуса
     *
     * @return ActiveQuery
     *
     * @throws ErrorException
     */
    public function getAllStatuses()
    {
        /* Проверка на наличие корневого статуса происходит при инициализации поведения */
        return $this->statusRootObj->getChildes();
    }

    /**
     * Сеттер для установки статуса, проверяет доступен ли назначаемый статус и устанавливает его.
     * Используйте setStatusSafe() если статус нужно установить без проверки прав доступа.
     *
     * @param Statuses $statusObj - Объект статуса
     *
     * @return void
     *
     * @throws ErrorException
     */
    public function setStatus($statusObj)
    {
        if (!($statusObj instanceof Statuses)) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_TO_NOT_INSTANCEOF_STATUSES);
        }

        if (($statusObj->id === null) || !is_int($statusObj->id)) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_ID_EMPTY_OR_NOT_INT);
        }

        /* Проверяем, что устанавливаемый статус является дочерним корневого статуса */
        $hasChild = $this->hasWhatStatusTheChildRootStatus($statusObj->id);

        if ($hasChild === false) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_NOT_CHILD_BY_ROOT_STATUS);
        }

        if ($this->owner->{$this->statusIdField} === $statusObj->id) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_EQUAL_OLD_STATUS);
        }

        /* Получаем текущий статус */
        $status = $this->getStatus()->one();

        /* Получаем простые связи для текущего статуса */
        $simpleLink = $status->getSimpleLinkByDocument($statusObj)->one();

        if (empty($simpleLink->right_tag)) {
            throw new ErrorException(BehaviorsMessages::STAT_SIMPLE_LINK_RIGHT_TAG_IS_EMPTY);
        }

        if (!($this->owner->isAllowed($simpleLink->right_tag))) {
            throw new ErrorException(BehaviorsMessages::STAT_SIMPLE_LINK_NOT_ALLOWED);
        }

        /* Присваиваем документу новый статус, сохранеие необходимо производить отдельно */
        $this->owner->{$this->statusIdField} = $statusObj->id;
    }

    /**
     * Setter for setting the status_id for the document without check of the access rights.
     * Use [[static::setStatus()]] for setting new status with check of the rights.
     *
     * @param Statuses $statusObj - объект статуса
     *
     * @return void
     *
     * @throws \yii\base\ErrorException
     */
    public function setStatusSafe($statusObj)
    {
        if (!($statusObj instanceof Statuses)) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_TO_NOT_INSTANCEOF_STATUSES);
        }

        if (($statusObj->id === null) || !is_int($statusObj->id)) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_ID_EMPTY_OR_NOT_INT);
        }

        /* Проверяем, что устанавливаемый статус является дочерним корневого статуса */
        $hasChild = $this->hasWhatStatusTheChildRootStatus($statusObj->id);

        if ($hasChild === false) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_NOT_CHILD_BY_ROOT_STATUS);
        }

        if ($this->owner->{$this->statusIdField} === $statusObj->id) {
            throw new ErrorException(BehaviorsMessages::STAT_NEW_STATUS_EQUAL_OLD_STATUS);
        }

        /* Присваиваем документу новый статус, сохранеие необходимо производить отдельно */
        $this->owner->{$this->statusIdField} = $statusObj->id;
    }

    /**
     * Получаем статусы, на которые можно сменить текущий статус:
     * 1)Находятся в корневом статусе.
     * 2)Имеется право доступа.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        /* Получаем дочерние статусы корневого статуса */
        $childStatuses = $this->getAllStatuses()->all();

        /* Получаем простые связи для текущего статуса */
        $simpleLinks = $this->getStatus()->one()->getLinksTransitionsTo()->all();

        /* Формируем массив содержащий: ключ - тэг статуса, значение - id статуса, необходим для поиска по id */
        $childStatusesIdArray = ArrayHelper::getColumn($childStatuses, 'id');

        /* Инициализируем массив, который будет содержать статусы, на которые возможен переход с текущего статуса */
        $statuses = [];

        // Проверяем на доступ простую связь и если проверка прошла,
        // то добавляем к массиву статус, на который может измениться текущий статус
        foreach ($simpleLinks as $value) {
            if ($this->owner->isAllowed($value->right_tag)) {
                $key = array_search($value->status_to, $childStatusesIdArray);

                if ($key !== false) {
                    $statuses[$key] = $childStatuses[$key];
                }
            }
        }

        return $statuses;
    }
}
