<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.07.16
 * Time: 18:02
 */

namespace docflow\examples\jsonb\models;

use docflow\base\UnstructuredRecord;
use yii;
use yii\db\ActiveRecord;

class JsonBTest extends UnstructuredRecord
{
    public static function tableName()
    {
        return '{{%jsonbtest}}';
    }

    public function rules()
    {
        return [
            [['contacts'], 'required'],
            [['contacts'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('docflow', 'ID'),
            'contacts' => Yii::t('docflow', 'Contacts'),
            'payment_cards' => Yii::t('docflow', 'Payment Cards'),
        ];
    }

    /**
     * Указываем поля, которые содержат данные json типа
     *
     * @return array
     */
    public static function jsonBFields()
    {
        return ['contacts', 'payment_cards'];
    }


    /**
     * Получаем все данные
     *
     * @return array|ActiveRecord[]
     */
    public static function getAll()
    {
        return static::find()->all();
    }
}
