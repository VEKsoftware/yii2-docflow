<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.07.16
 * Time: 18:02
 */

namespace docflow\examples\jsonb\models;

use docflow\base\JsonB;
use docflow\base\UnstructuredRecord;
use yii;

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
            [
                ['contacts', 'payment_cards'],
                'safe',
                'when' => function ($model, $attribute) {
                    if ($model->{$attribute} instanceof JsonB) {
                        return true;
                    } else {
                        return false;
                    }
                }
            ],
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
     * @return array|UnstructuredRecord[]
     */
    public static function getAll()
    {
        return static::find()->orderBy(['id' => SORT_ASC])->all();
    }
}
