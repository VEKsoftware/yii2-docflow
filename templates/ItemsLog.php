<?php

namespace app\models;

use statuses\models\Statuses;
use yii\db\ActiveRecord;
use app\base\VekActiveRecord;

/**
 * This is the model class for table "items_log".
 *
 * @property int $id
 * @property int $category_id
 * @property int $owner_id
 * @property string $atime
 * @property int $status_id
 * @property int $model_id
 * @property int $doc_id
 * @property int $changed_by
 * @property string $changed_attributes
 *
 * @property Units|null $userWhoChangeAttributes Модель пользователя, который инициировал изменение атрибутов.
 *
 * // Специфичные для Items типа Sims свойства //
 * @property Invoices simDoc Документ(инвойс), на основе которого произошли изменения модели.
 */
class ItemsLog extends VekActiveRecord
{
    /* @inheritdoc */
    protected static $_models = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'items_log';
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'owner_id' => 'Owner ID',
            'atime' => 'Atime',
            'status_id' => 'Status ID',
            'model_id' => 'Model ID',
            'doc_id' => 'Item ID',
            'changed_by' => 'Changed By',
            'changed_attributes' => 'Changed Attributes',
            'document' => 'Document',
        ];
    }

    public function getUserWhoChangeAttributes()
    {
        return $this->hasOne(Units::className(), ['id' => 'changed_by']);
    }

    public function getStatus()
    {
        return $this->hasOne(Statuses::className(), ['id' => 'status_id']);
    }


    /////////////////////////////////////////////
    //        Методы для категории Sims        //
    /////////////////////////////////////////////

    public function getSimDoc()
    {
        return $this->hasOne(Invoices::className(), ['id' => 'document']);
    }
}
