<?php

namespace docflow\behaviors;

use docflow\messages\behaviors\BehaviorsMessages;
use yii;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\base\ErrorException;

use yii\helpers\ArrayHelper;

use docflow\models\DocTypes;
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
     * @var ActiveRecord the owner of this behavior
     */
    public $owner;

    /**
     * @var string The name of a field in the table for ID of the status
     */
    public $statusIdField = 'status_id';

    /**
     * Корневой статус
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
            throw new ErrorException(BehaviorsMessages::STAT_OWNER_NOT_INSTANCEOF_DOCUMENT);
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
            throw new ErrorException(BehaviorsMessages::STAT_CURRENT_STATUS_NOT_ONE_OF_CHILD_ROOT_STATUS);
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
            throw new ErrorException('Идектификатор статуса не определен');
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
            throw new ErrorException('Устанавливаемый статус не принадлежит Statuses');
        }

        if (($statusObj->id === null) || !is_int($statusObj->id)) {
            throw new ErrorException('Устанавливаемый статус пуст');
        }

        /* Проверяем, что устанавливаемый статус является дочерним корневого статуса */
        $hasChild = $this->hasWhatStatusTheChildRootStatus($statusObj->id);

        if ($hasChild === false) {
            throw new ErrorException('Устанавливаемый статус не является дочерним корневого статуса');
        }

        if ($this->owner->{$this->statusIdField} === $statusObj->id) {
            throw new ErrorException('Текущий статус равен устанавливаемому');
        }

        /* Получаем текущий статус */
        $status = $this->getStatus()->one();

        /* Получаем простые связи для текущего статуса */
        $simpleLink = $status->getSimpleLinkByDocument($statusObj)->one();

        if (empty($simpleLink->right_tag)) {
            throw new ErrorException('Отсутствует тэг доступа');
        }

        if (!($this->owner->isAllowed($simpleLink->right_tag))) {
            throw new ErrorException('Нет права доступа для установки статуса');
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
            throw new ErrorException('Устанавливаемый статус не принадлежит Statuses');
        }

        if (($statusObj->id === null) || !is_int($statusObj->id)) {
            throw new ErrorException('Устанавливаемый статус пуст');
        }

        /* Проверяем, что устанавливаемый статус является дочерним корневого статуса */
        $hasChild = $this->hasWhatStatusTheChildRootStatus($statusObj->id);

        if ($hasChild === false) {
            throw new ErrorException('Устанавливаемый статус не является дочерним корневого статуса');
        }

        if ($this->owner->{$this->statusIdField} === $statusObj->id) {
            throw new ErrorException('Текущий статус равен устанавливаемому');
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
