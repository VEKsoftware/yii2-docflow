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
                [['changed_attributes'], 'required']
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
                'changed_attributes' => 'Измененные аттрибуты'
            ]
        );
    }
}
