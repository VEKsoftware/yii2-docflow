<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 09.08.16
 * Time: 10:39
 */

namespace docflow\models;

use docflow\models\base\Document;
use docflow\models\base\Operations;
use yii;

class StatusesLog extends Document
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%doc_statuses_log}}';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['doc_type_id', 'name', 'tag', 'changed_attributes', 'doc_id', 'changed_by'], 'required'],
            [['doc_type_id', 'order_idx', 'doc_id', 'changed_by', 'operation_log_id'], 'integer'],
            [['name', 'tag'], 'string', 'max' => 128],
            [['description'], 'string', 'max' => 512],
            ['tag', 'unique', 'targetAttribute' => ['doc_type_id', 'tag']],
            ['tag', 'match', 'pattern' => '/^[a-zA-Z0-9-_\.]+$/'],
            [['operations_ids', 'changed_attributes'], 'string'],
            [
                ['doc_id'],
                'exist',
                'targetClass' => Statuses::className(),
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
            'id' => Yii::t('docflow', 'ID'),
            'doc_type_id' => Yii::t('docflow', 'Document Type'),
            'name' => Yii::t('docflow', 'Status Name'),
            'description' => Yii::t('docflow', 'Status Description'),
            'tag' => Yii::t('docflow', 'Status Tag'),
            'order_idx' => 'Сортировка',
            'operations_ids' => 'Текущие операции',
            'changed_attributes' => 'Измененные аттрибуты',
            'atime' => 'Штамп времени',
            'changed_by' => 'Изменено',
            'operation_log_id' => 'Операция в логе',
            'doc_id' => 'Документ'
        ];
    }

    /**
     * This function returns the document tag. This tag is used to get
     * all information about the document type from the database.
     *
     * @return string Document tag
     */
    static public function docTag()
    {
        return '';
    }

    /**
     * Return field name which use how Document `name`
     *
     * @return string Document name
     */
    public function getDocName()
    {
        return '';
    }

    /**
     * Получаем документ по его идентификатору
     *
     * @param integer $nodeId - id документа
     *
     * @return mixed
     */
    public static function getDocumentByNodeId($nodeId)
    {
        return '';
    }
}
