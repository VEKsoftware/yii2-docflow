<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 09.08.16
 * Time: 12:14
 */

namespace docflow\models;

use docflow\models\base\Operations;
use yii;

class StatusesLinksLog extends StatusesLinks
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%doc_statuses_links_log}}';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['status_from', 'status_to', 'level', 'operation_log_id', 'changed_by', 'doc_id'], 'integer'],
            [['right_tag', 'type', 'changed_attributes'], 'string'],
            ['right_tag', 'match', 'pattern' => '/^[a-zA-Z0-9-_\.]+$/'],
            [
                ['status_from', 'status_to'],
                'exist',
                'targetClass' => Statuses::className(),
                'targetAttribute' => 'id'
            ],
            [
                ['doc_id'],
                'exist',
                'targetClass' => StatusesLinks::className(),
                'targetAttribute' => 'id'
            ],
            [
                ['changed_by'],
                'exist',
                'targetAttribute' => '"user".id'
            ],
            [
                ['operation_log_id'],
                'exist',
                'targetClass' => Operations::className(),
                'targetAttribute' => 'id'
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
            'status_from' => Yii::t('docflow', 'Source Status'),
            'status_to' => Yii::t('docflow', 'Destination Status'),
            'right_tag' => Yii::t('docflow', 'Access Right Tag'),
            'level' => Yii::t('docflow', 'Link Level'),
            'type' => Yii::t('docflow', 'Link Type'),
            'atime' => 'Штамп времени',
            'doc_id' => 'Документ',
            'changed_by' => 'Изменено',
            'operation_log_id' => 'Операция в логе'
        ];
    }
}
