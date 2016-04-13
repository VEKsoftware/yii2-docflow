<?php

namespace app\models;
use app\base\VekActiveRecord;

/**
 * This is the model class for table "items_props_types_log".
 *
 * @property int $id
 * @property int $category_id
 * @property string $key
 * @property string $description
 * @property int $changed_by
 * @property string $changed_attributes
 */
class ItemsPropsTypesLog extends VekActiveRecord
{
    /* @inheritdoc */
    protected static $_models = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'items_props_types_log';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'key' => 'Key',
            'description' => 'Description',
            'changed_by' => 'Changed By',
            'changed_attributes' => 'Changed Attributes',
        ];
    }
}
