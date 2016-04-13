<?php

namespace app\models;

use app\base\VekActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "items_props_types".
 *
 * @property int $id
 * @property int $category_id
 * @property string $key
 * @property string $description
 */
class ItemsPropsTypes extends VekActiveRecord
{
    /* @inheritdoc */
    protected static $_models = [];

    private static $_typesCache;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'items_props_types';
    }

    public static function getTypeIdByKey($key)
    {
        if (!isset(static::$_typesCache)) {
            static::$_typesCache = ArrayHelper::map(static::find()->all(), 'key', 'id');
        }
        return ArrayHelper::getValue(static::$_typesCache, $key);
    }

    public static function getAllCategoriesPropsTypes()
    {
        return static::find()->indexBy('key')->all();
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'key', 'description'], 'required'],
            [['category_id'], 'integer'],
            [['key'], 'string', 'max' => 32],
            [['description'], 'string', 'max' => 128],
        ];
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
            'description' => 'Свойство',
        ];
    }
}
