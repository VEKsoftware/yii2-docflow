<?php

namespace docflow\behaviors;

use yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\base\ErrorException;

use yii\helpers\ArrayHelper;

use docflow\models\DocTypes;
use docflow\models\Document;
use docflow\models\Statuses;
use docflow\models\StatusesLinks;

/**
 * Behavior class for checking access to set status field and setting it if accessed.
 *
 * @property integer                  statusIdField Название поля со статусом в таблице
 * @property \docflow\models\Statuses status
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
     * @var string Корневой статус
     */
    public $statusRootTag;

    /**
     * @var string id корневого статуса
     */
    protected $statusRootId;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if (!$owner instanceOf Document) {
            throw new ErrorException('You can attach StatusesBehavior only to instances of docflow\models\Document');
        }

        if (empty($this->statusRootTag)) {
            throw new ErrorException('StatusBehavior: You have to set status tag for new instance of the model ' . $owner->className());
        }

        // To avoid infinit loop
        if (!$owner instanceOf DocTypes) {
            if (!isset($owner->doc->statuses[$this->statusRootTag])) {
                throw new ErrorException('StatusBehavior: wrong root status: ' . $this->statusRootTag);
            }
        }

        $this->statusRootId = $this->findAndSetStatusRootId();
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'checkWhatStatusTheChildRootStatusWhenEventInitOrAfterFind',
            ActiveRecord::EVENT_INIT => 'checkWhatStatusTheChildRootStatusWhenEventInitOrAfterFind',
        ];
    }

    /**
     * Находим id корневого статуса по его тэгу
     *
     * @return integer
     */
    protected function findAndSetStatusRootId()
    {
        $statusesObj = $this->owner->doc->statuses;
        $statusObj = $statusesObj[$this->statusRootTag];

        return $statusObj->id;
    }

    /**
     * Проверяем, является-ли текущий статус дочерним статусом корневого
     *
     * @return void
     *
     * @throws \yii\base\ErrorException
     */
    public function checkWhatStatusTheChildRootStatusWhenEventInitOrAfterFind()
    {
        $key = array_search($this->owner->{$this->statusIdField}, array_column($this->getAllStatuses(), 'status_to'));
        if (($key === false) && ($this->owner->{$this->statusIdField} !== null)) {
            throw new ErrorException('Текущий статус не принадлежит корневому статусу');
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
        $key = array_search($statusId, array_column($this->getAllStatuses(), 'status_to'));

        if ($key === false) {
            $return = false;
        } else {
            $return = true;
        }

        return $return;
    }

    /**
     * Метод возвращает текущий устанвленный статус
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return Statuses::getStatusById($this->owner->{$this->statusIdField});
    }

    /**
     * Получаем массив всех статусов потомков корневого статуса
     *
     * @return array
     */
    public function getAllStatuses()
    {
        return StatusesLinks::getChildStatusesForStatus($this->statusRootId, false);
    }

    /**
     * Сеттер для установки статуса, проверяет доступен ли назначаемый статус и устанавливает его.
     * Используйте setStatusSafe() если статус нужно установить без проверки прав доступа.
     *
     * @param object $statusObj - Объект статуса
     *
     * @return array
     */
    public function setStatus($statusObj)
    {
        try {
            $hasChild = $this->hasWhatStatusTheChildRootStatus($statusObj->id);

            if ($hasChild === false) {
                throw new ErrorException('Устанавливаемый статус не является дочерним корневого статуса');
            }

            $simpleLink = StatusesLinks::getSimpleLinkForStatusFromIdAndStatusToId(
                $this->owner->{$this->statusIdField},
                $statusObj->id
            );

            if (empty($simpleLink->right_tag)) {
                throw new ErrorException('Отсутствует тэг доступа');
            }

            if (!($this->owner->isAllowed($simpleLink->right_tag))) {
                throw new ErrorException('Нет права доступа для установки статуса');
            }

            $this->owner->{$this->statusIdField} = $statusObj->id;

            $return = ['success' => 'Статус установлен'];

        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Setter for setting the status_id for the document without check of the access rights.
     * Use [[static::setStatus()]] for setting new status with check of the rights.
     *
     * @param object $statusObj - объект статуса
     *
     * @return array
     */
    public function setStatusSafe($statusObj)
    {
        try {
            $hasChild = $this->hasWhatStatusTheChildRootStatus($statusObj->id);

            if ($hasChild === false) {
                throw new ErrorException('Устанавливаемый статус не является дочерним корневого статуса');
            }

            $this->owner->{$this->statusIdField} = $statusObj->id;

            $return = ['success' => 'Статус установлен'];

        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Получаем массив содержащий статусы, на которые можно сменить текущий статус:
     * 1)Находятся в корневом статусе.
     * 2)Имеется право доступа.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        $statusesTo = $this->allStatuses;
        $simpleLinksArray = StatusesLinks::getSimpleLinksByTagFromIdWhereTagToArray(
            $this->owner->{$this->statusIdField},
            ArrayHelper::getColumn($statusesTo, 'status_to')
        );

        $result = [];
        foreach ($simpleLinksArray as $status) {
            if ($this->owner->isAllowed($status->right_tag)) {
                $result[] = $status;
            }
        }

        return $result;
    }
}
