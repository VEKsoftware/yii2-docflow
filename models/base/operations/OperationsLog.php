<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 08.08.16
 * Time: 15:30
 */

namespace docflow\models\base\operations;

use docflow\models\base\OperationBase;

class OperationsLog extends OperationBase
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%operations_log}}';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['changed_attributes'], 'string'],
                [['changed_attributes', 'doc_id'], 'required'],
                [['doc_id'], 'integer'],
                [
                    ['doc_id'],
                    'exist',
                    'targetClass' => Operations::className(),
                    'targetAttribute' => 'id'
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'changed_attributes' => 'Измененные аттрибуты',
                'doc_id' => 'Документ',
            ]
        );
    }

    /**
     * Перед массовым сохранением
     *
     * @param bool $insert - true - добавляем, false - обновляем записи
     *
     * @return bool
     *
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\ErrorException
     */
    public function beforeSaveMultiple($insert)
    {
        $this->beforeSave($insert);

        return parent::beforeSaveMultiple($insert);
    }
}
