<?php

namespace app\models;
use app\base\VekActiveRecord;

/**
 * This is the model class for table "items_props_values_log".
 *
 * @property int $id
 * @property int $item_id
 * @property int $type_id
 * @property string $value
 * @property int $changed_by
 * @property string $changed_attributes
 */
class ItemsPropsValuesLog extends VekActiveRecord
{
    /* @inheritdoc */
    protected static $_models = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'items_props_values_log';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_id' => 'Item ID',
            'type_id' => 'Type ID',
            'value' => 'Value',
            'changed_by' => 'Changed By',
            'changed_attributes' => 'Changed Attributes',
        ];
    }
}
