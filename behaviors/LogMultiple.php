<?php

namespace docflow\behaviors;

use docflow\base\UnstructuredRecord;
use yii;
use yii\base\ErrorException;
use yii\base\Event;
use Yii\console\Application;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;

/**
 * Behavior class for logging all changes to a log table.
 * It requires a log model. By default it is owner class name + 'Log'.
 *
 * @property array  $logAttributes          A list of all attributes to be saved in the log table
 * @property string $logClass               Class name for the log model
 * @property string $changedAttributesField Field in the table to store changed attributes list. Default: changed_attributes
 * @property string $changedByField         Field in the table to store the author of the changes (Yii::$app->user->id). Default: changed_by
 * @property string $timeField              Field where the time of last change is stored. Default: atime
 */
class LogMultiple extends Log
{
    /**
     * @var array Список классов которые подписали на события VekActiveRecord::EVENT_TO_SAVE_MULTIPLE, VekActiveRecord::EVENT_SAVED_MULTIPLE
     */
    protected static $_attachedClasses = [];

    /**
     * @inherit
     */
    public function events()
    {
        return array_merge(
            parent::events(),
            [
                UnstructuredRecord::EVENT_TO_SAVE_MULTIPLE => 'logToSaveMultiple',
                UnstructuredRecord::EVENT_BEFORE_INSERT_MULTIPLE => 'logBeforeSaveMultiple',
                UnstructuredRecord::EVENT_BEFORE_UPDATE_MULTIPLE => 'logBeforeSaveMultiple',
                UnstructuredRecord::EVENT_AFTER_INSERT_MULTIPLE => 'logAfterSaveMultiple',
                UnstructuredRecord::EVENT_AFTER_UPDATE_MULTIPLE => 'logAfterSaveMultiple',
            ]
        );
    }

    /**
     * @inheritdoc
     *
     * @param ActiveRecord $owner
     *
     * @throws ErrorException
     */
    public function attach($owner)
    {
        if (!in_array($owner->className(), self::$_attachedClasses)) {
            Event::on($owner->className(), UnstructuredRecord::EVENT_TO_SAVE_MULTIPLE,
                [self::className(), 'logToSaveMultiple']);
            Event::on($owner->className(), UnstructuredRecord::EVENT_SAVED_MULTIPLE,
                [self::className(), 'logSavedMultiple']);
            self::$_attachedClasses[] = $owner->className();
        }
        parent::attach($owner);
    }

    /**
     * Check for versions for all records
     *
     * @param Event $event
     *
     * @return bool
     * @throws StaleObjectException
     */
    public static function logToSaveMultiple($event)
    {
        $senderClass = $event->sender;
        $models = $senderClass::getSaveMultiple(); // List of models to be saved;

        $updateModels = [];
        $primary_keys = [];
        $versionField = null;
        $tableFields = [];
        $types = [];
        foreach ($models as $model) {
            /** @var ActiveRecord|Log $model */
            // Игнорирование проверки версий
            if (($model->isNewRecord === false) || ($model->versionField)) {
                continue;
            }
            if (empty($versionField) || empty($primary_keys)) {
                $primary_keys = $model->primaryKey();
                $versionField = $model->versionField;
                $tableFields = array_merge($primary_keys, [$versionField]);
                $types = ArrayHelper::getColumn($model->getTableSchema()->columns, 'dbType');
            }
            $updateModels[] = $model;
            $updateStrs = [];
            foreach ($tableFields as $field) {
                if ($updateModels->$field === null) {
                    $updateStrs[] = 'NULL::' . $types[$field];
                } else {
                    $updateStrs[] = Yii::$app->db->quoteValue($updateModels->$field) . '::' . $types[$field];
                }
            }
            $updates[] = '(' . implode(', ', $updateStrs) . ')';
        }

        // Nothing to do
        if (count($updateModels) === 0) {
            return true;
        }

        $on_pk = [];
        foreach ($primary_keys as $pk) {
            $on_pk[] = '[[t.' . $pk . ']] = [[v.' . $pk . ']]';
        }

        /** @var array $updates */
        $sql = 'SELECT ' . implode(', ', $primary_keys) . ' FROM {{%' . $senderClass::tableName() . '}} AS t '
            . 'LEFT JOIN (VALUES ' . implode(', ', $updates) . ') AS v([[' . implode(']], [[', $tableFields) . ']]) '
            . 'ON ' . implode(' AND ', $on_pk)
            . 'WHERE [[' . $versionField . ']] != [[' . $versionField . ']]';

        // die($db->quoteSql($sql));
        $faultyCount = Yii::$app->db->createCommand(Yii::$app->db->quoteSql($sql))->execute();

        if ($faultyCount > 0) {
            throw new StaleObjectException('Some or all objects being updated are outdated.');
            /** PhpUnreachableStatementInspection */
//            $event->isValid = false;
//            return false;
        }

        return true;
    }

    /**
     * Sets the time of change.
     *
     */
    public function logBeforeSaveMultiple()
    {

        $logAttributes = $this->logAttributes;

        $this->_to_save_log = false;
        foreach ($logAttributes as $key => $val) {
            if (is_int($key)) {
                // Значения - это имена атрибутов
                $aName = $val;
                $aValue = $this->owner->getAttribute($aName);
            } elseif ($val instanceof \Closure) {
                // Ключ - имя атрибута, значение - вычисляемое
                $aName = $key;
                $aValue = call_user_func($val, $this->owner);
            } else {
                $aName = $key;
                $aValue = $val;
            }

            if ($this->owner->hasAttribute($aName)) {
                if ($aName === $this->timeField) {
                    continue;
                } elseif ($this->owner->getOldAttribute($aName) != $aValue) {
                    $this->_to_save_attributes[$aName] = $aValue;
                    $this->_to_save_log = true;
                    $this->_changed_attributes[] = $aName;
                } else {
                    $this->_to_save_attributes[$aName] = $aValue;
                }
            } else {
                $this->_to_save_attributes[$aName] = $aValue;
            }
        }

        if ($this->_to_save_log) {
            $time = static::returnTimeStamp();
            $this->owner->{$this->timeField} = $time;
            $this->_to_save_attributes[$this->timeField] = $time;
            $this->setNewVersion();
        } else {
            return true;
        }

        // Set new version of the record
        // assuming checking for old version is done in [[logToSaveMultiple()]]
        if (isset($this->versionField)) {
            $this->setNewVersion();
        }

        return true;
    }

    /**
     * Saves a record to the log table.
     *
     * @param \yii\db\AfterSaveEvent $event
     *
     * @throws ErrorException
     */
    public function logAfterSaveMultiple($event)
    {
        if (!$event instanceof AfterSaveEvent) {
            return;
        }
        if (is_null($event->changedAttributes)) {
            return;
        }
        if (!$this->_to_save_log) {
            return;
        }

        $this->_to_save_attributes[$this->docId] = $this->owner->id;
        unset($this->_to_save_attributes['id']);
        $this->_to_save_attributes[$this->changedAttributesField] = '{' . implode(',',
                array_values($this->_changed_attributes)) . '}';

        $logClass = $this->logClass;
        $log = new $logClass();
        $log->setAttributes(array_intersect_key($this->_to_save_attributes, $log->getAttributes()));

        $logClass::addSaveMultiple($log);

    }

    public static function logSavedMultiple($event)
    {
        $senderClass = $event->sender;
        $tmp_model = new $senderClass();

        $logClass = $tmp_model->logClass;
        unset($tmp_model);

        /** @var UnstructuredRecord $logClass */
        return $logClass::saveMultiple();
    }

}
