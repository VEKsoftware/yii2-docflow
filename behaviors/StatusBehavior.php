<?php

namespace docflow\behaviors;

use Yii;
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
 * @property integer statusIdField Название поля со статусом в таблице
 * @property \statuses\models\Statuses status
 */
class StatusBehavior extends Behavior
{
    /**
     * @var ActiveRecord the owner of this behavior
     */
    public $owner;
    public $statusIdField = 'status_id';

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        if(! $owner instanceOf Document) {
            throw new ErrorException('You can attach StatusesBehavior only to instances of docflow\models\Document');
        }
        parent::attach($owner);
    }

    /**
     * Метод возвращает отношение связанного объекта статуса.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        // TODO странная бага, если изменить идентификатор статуса в самой модели и сохранить, то при повторном вызове геттера будет возвращен прошлый результат
        return $this->owner->hasOne(Statuses::className(), ['id' => $this->statusIdField]);
    }

    /**
     * Сеттер для установки статуса, проверяет доступен ли назначаемый статус и устанавливает его.
     * Используйте setStatusSafe() если статус нужно установить без проверки прав доступа.
     *
     * @param string $statusSymbolicId
     */
    public function setStatus($statusTag)
    {
        $right_tags = $this->owner->status->rightsForStatusTo($statusTag);
        if($this->owner->isAllowed($right_tags)) {
            $this->owner->{$this->statusIdField} = $statusTo->id;
        }
    }

    /**
     * Setter for setting the status_id for the document without check of the access rights.
     * Use [[static::setStatus()]] for setting new status with check of the rights.
     *
     * @param string $statusTag
     */
    public function setStatusSafe($statusTag)
    {
        $statusesTo = $this->owner->doc->statuses;

        if (isset($statusesTo[$statusTag])) {
            $this->owner->{$this->statusIdField} = $statusesTo[$statusTag]->id;
        }
    }

    /**
     * [[static::getAllowedStatusses]] returns a list of statuses which are allowed to be set for the document.
     *
     * @return \docflow\models\Statuses[]
     */
    public function getAllowedStatuses()
    {
        $statusesTo = $this->owner->status->statusesTo;
        $result = [];
        foreach($statusesTo as $status) {
            if($this->owner->isAllowed($status->right_tag)) {
                $result[] = $status;
            }
        }
        return $result;
    }

}