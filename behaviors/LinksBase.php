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
class LinksBase extends Behavior
{
    /**
     * @var ActiveRecord the owner of this behavior
     */
    public $owner;

    /**
     * @var string The name of the class containing links for the owner model
     */
    public $classLinks;

    /**
     * @var string The field in the base table
     */
    public $linkBaseIdField;

    /**
     * @var array Link array ['base_id' => 'link_from_id']
     */
    public $linkFromField;

    /**
     * @var array Link array ['base_id' => 'link_from_id']
     */
    public $linkToField;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if(! $owner instanceOf Document) {
            throw new ErrorException('You can attach StatusesBehavior only to instances of docflow\models\Document');
        }

        if(empty($this->newStatusTag)) {
            throw new ErrorException('StatusBehavior: You have to set status tag for new instance of the model '.$owner->className());
        }

        // To avoid infinit loop
        if(! $owner instanceOf DocTypes) {
            if(! isset($owner->doc->statuses[$this->newStatusTag])) {
                throw new ErrorException('StatusBehavior: wrong initial status: '.$this->newStatusTag);
            }
        }

    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT          => 'statusInit',
        ];
    }

    /**
     * Sets initial status for the new document
     *
     * @param Event $event
     */
    public function statusInit($event)
    {
        $statusesObj = $this->owner->doc->statuses;
        $statusObj = $statusesObj[$this->newStatusTag];
        $this->owner->{$this->statusIdField} = $statusObj->id;
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
        var_dump($this->owner->status);die();
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