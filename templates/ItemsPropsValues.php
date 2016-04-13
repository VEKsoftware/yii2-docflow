<?php

namespace app\models;
use app\base\VekActiveRecord;
use app\behaviors\Log;

/**
 * This is the model class for table "items_props_values".
 *
 * @mixin \app\behaviors\Log
 *
 * @property int $id
 * @property int $item_id
 * @property int $type_id
 * @property string $value
 * @property ItemsPropsTypes propertyValueType
 */
class ItemsPropsValues extends VekActiveRecord
{
    /* @inheritdoc */
    protected static $_models = [];

    public function behaviors()
    {
        return [
            'log' => [
                'class' => Log::className(),
                'logClass' => ItemsPropsValuesLog::className(),
                'timeField' => 'atime',
                'changedAttributesField' => 'changed_attributes',
                'versionField' => 'version',
                'logAttributes' => [
                    'id',
                    'item_id',
                    'type_id',
                    'value',
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'items_props_values';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['item_id', 'type_id'], 'required'],
            [['item_id', 'type_id'], 'integer'],
            [['value'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_id' => 'Товар',
            'type_id' => 'Тип',
            'value' => 'Значение',
        ];
    }

    public function getPropertyValueType()
    {
        return $this->hasOne(ItemsPropsTypes::className(), ['id' => 'type_id']);
    }

}
