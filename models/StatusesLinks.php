<?php

namespace docflow\models;

use docflow\behaviors\LogMultiple;
use docflow\models\base\Link;
use yii;

use docflow\Docflow;
use yii\base\ErrorException;

/**
 * This is the model class for table "statuses_links".
 *
 * @property int      $status_from
 * @property int      $status_to
 * @property string   $right_tag
 * @property Statuses $statusFrom
 * @property Statuses $statusTo
 */
class StatusesLinks extends Link
{
    public static $_baseClass = 'docflow\models\Statuses';
    public static $_fieldNodeId = 'id';
    public static $_fieldLinkFrom = 'status_from';
    public static $_fieldLinkTo = 'status_to';
    public static $_levelField = 'level';
    public static $_typeField = 'type';
    public static $_rightTagField = 'right_tag';
    public static $_relationTypeField = '';
    public static $_fieldNodeTag = 'tag';
    public static $_removedAttributes = ['id', 'atime', 'version'];

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%doc_statuses_links}}';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['status_from', 'status_to', 'type', 'level'], 'required', 'on' => static::LINK_TYPE_FLTREE],
            [['status_from', 'status_to', 'right_tag', 'type'], 'required', 'on' => static::LINK_TYPE_SIMPLE],
            [['status_from', 'status_to', 'version'], 'integer'],
            [['right_tag'], 'string'],
            [['level'], 'integer'],
            [['type'], 'string'],
            ['right_tag', 'match', 'pattern' => '/^[a-zA-Z0-9-_\.]+$/'],
            [['status_from', 'status_to'], 'exist', 'targetClass' => Statuses::className(), 'targetAttribute' => 'id'],
        ];
    }

    /**
     * Сценарии
     *
     * @return array
     */
    public function scenarios()
    {
        return array_merge(
            parent::scenarios(),
            [
                static::LINK_TYPE_FLTREE => ['status_from', 'status_to', 'type', 'level'],
                static::LINK_TYPE_SIMPLE => ['status_from', 'status_to', 'right_tag', 'type'],
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
        return [
            'status_from' => Yii::t('docflow', 'Source Status'),
            'status_to' => Yii::t('docflow', 'Destination Status'),
            'right_tag' => Yii::t('docflow', 'Access Right Tag'),
            'level' => Yii::t('docflow', 'Link Level'),
            'type' => Yii::t('docflow', 'Link Type'),
            'version' => 'Версия',
            'atime' => 'Штамп времени'
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => Docflow::getInstance()->accessClass,
            ],
            'log' => [
                'class' => LogMultiple::className(),
                'logAttributes' => [
                    'id',
                    'status_from',
                    'status_to',
                    'right_tag',
                    'level',
                    'type',
                    'atime'
                ],
                'timeField' => 'atime',
                'logClass' => StatusesLinksLog::className(),
                'changedAttributesField' => 'changed_attributes',
                'versionField' => 'version',
            ],
        ];
    }

    /**
     * Подтверждение
     *
     * @param array $row - строка
     *
     * @return StatusesLinksStructure|StatusesLinksTransitions
     *
     * @throws \yii\base\ErrorException
     */
    public static function instantiate($row)
    {
        if (!isset($row['type'])) {
            throw new ErrorException('You need pass doc_statuses type in the $row parameter for instantiation of the StatusesLinks model');
        }

        switch ($row['type']) {
            case(static::LINK_TYPE_SIMPLE):
                return new StatusesLinksTransitions($row);
            case(static::LINK_TYPE_FLTREE):
                return new StatusesLinksStructure($row);
            default:
                throw new ErrorException('Unknonw doc_statuses_links link type');
        }
    }
}
