<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 08.08.16
 * Time: 15:28
 */

namespace docflow\models\base;

use docflow\base\JsonB;
use docflow\models\statuses\Statuses;

abstract class OperationBase extends DocFlowBase
{
    /**
     * Получаем массив, содержащий наименования полей, которые содержат данные в формате json
     *
     * @return array
     */
    public static function jsonBFields()
    {
        return ['field'];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['atime'], 'safe'],
            [['operation_type', 'status_id', 'unit_real_id', 'unit_resp_id'], 'required'],
            [['operation_type', 'comment'], 'string'],
            [['status_id', 'unit_real_id', 'unit_resp_id'], 'integer'],
            [
                ['status_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Statuses::className(),
                'targetAttribute' => ['status_id' => 'id']
            ],
            [
                ['field'],
                'safe',
                'when' => function ($model, $attribute) {
                    if ($model->{$attribute} instanceof JsonB) {
                        return true;
                    } else {
                        return false;
                    }
                }
            ]
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'operation_type' => 'Тип операции',
            'status_id' => 'Статус',
            'unit_real_id' => 'Контрагент реальный',
            'unit_resp_id' => 'Контрагент от чьего лица взаимодействуют',
            'field' => 'Поле',
            'comment' => 'Комментарий',
            'atime' => 'Временная метка'
        ];
    }

    /**
     * Описание связи со статусами
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatusLink()
    {
        return $this->hasOne(Statuses::className(), ['id' => 'status_id']);
    }
}
