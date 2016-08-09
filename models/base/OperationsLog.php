<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 08.08.16
 * Time: 15:30
 */

namespace docflow\models\base;

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
                [['changed_attributes', 'doc_id', 'changed_by'], 'required'],
                [['doc_id', 'changed_by'], 'integer'],
                [
                    ['doc_id'],
                    'exist',
                    'targetClass' => Operations::className(),
                    'targetAttribute' => 'id'
                ],
                [
                    ['changed_by'],
                    'exist',
                    'targetAttribute' => '"user".id'
                ]
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
                'changed_by' => 'Изменено'
            ]
        );
    }
}
