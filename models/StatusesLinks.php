<?php

namespace docflow\models;

use Yii;

use docflow\Docflow;
use docflow\models\Link;
use docflow\models\Statuses;

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
    protected static $_baseClass = 'docflow\models\Statuses';
    protected static $_linkFrom = ['id' => 'status_from']; // ['id' => 'upper_id']
    protected static $_linkTo = ['id' => 'status_to'];   // ['id' => 'lower_id']
    protected static $_levelField = 'level';
    protected static $_typeField = 'type';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%doc_statuses_links}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status_from', 'status_to', 'type', 'level'], 'required', 'on' => static::LINK_TYPE_FLTREE],
            [['status_from', 'status_to', 'right_tag', 'type'], 'required', 'on' => static::LINK_TYPE_SIMPLE],
            [['status_from', 'status_to'], 'integer'],
            [['right_tag'], 'string'],
            [['level'], 'integer'],
            [['type'], 'string'],
            ['right_tag', 'match', 'pattern' => '/^[a-zA-Z0-9-_\.]+$/'],
            [['status_from', 'status_to'], 'exist', 'targetClass' => Statuses::className(), 'targetAttribute' => 'id'],
        ];
    }

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
     */
    public function attributeLabels()
    {
        return [
            'status_from' => Yii::t('docflow', 'Source Status'),
            'status_to' => Yii::t('docflow', 'Destination Status'),
            'right_tag' => Yii::t('docflow', 'Access Right Tag'),
            'level' => Yii::t('docflow', 'Link Level'),
            'type' => Yii::t('docflow', 'Link Type'),
        ] + parent::attributeLabels();
    }

    /**
     * @inherit
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => Docflow::getInstance()->accessClass,
            ],
        ];
    }

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

    /**
     * Получаем ccылку на ближайшего родителя (1 уровень)
     *
     * @param integer $statusId - id статуса
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getFlTreeLinkForStatusForLevel1($statusId)
    {
        $query = static::find()
            ->where(
                [
                    'and',
                    ['=', 'status_to', $statusId],
                    ['=', 'type', 'fltree'],
                    ['=', 'level', 1]
                ]
            );

        $query->andWhere((new static)->extraWhere());

        return $query->one();
    }

    /**
     * Получаем ccылку на родителя родителя (2 уровень)
     *
     * @param integer $statusId - id статуса
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getFlTreeLinkForStatusForLevel1And2($statusId)
    {
        $query = static::find()
            ->where(
                [
                    'and',
                    ['=', 'status_to', $statusId],
                    ['=', 'type', 'fltree'],
                    ['in', 'level', [1, 2]]
                ]
            )
            ->indexBy('level');

        $query->andWhere((new static)->extraWhere());

        return $query->all();
    }

    /**
     * Получаем массив с данными о наличии "детей" - вложенных статусов
     *
     * @param integer $statusId - id Статуса1, у которого имем вложенные статусы
     * @param bool    $asArray  - true - выдавать массив, false - объекты
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getChildStatusesForStatus($statusId, $asArray = true)
    {
        $query = static::find()
            ->where([
                'and',
                ['=', 'type', 'fltree'],
                ['=', 'status_from', $statusId],
            ]);

        if ($asArray === true) {
            $query->asArray(true);
        }

        $query->andWhere((new static)->extraWhere());

        return $query->all();
    }

    /**
     * Получаем SimpleLink по id статусов From и To
     *
     * @param integer $fromStatusId - тэг статуса From
     * @param integer $toStatusId   - тэг статуса To
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getSimpleLinkForStatusFromIdAndStatusToId($fromStatusId, $toStatusId)
    {
        $query = static::find()
            ->where(
                [
                    'and',
                    ['=', 'type', static::LINK_TYPE_SIMPLE],
                    ['=', 'status_from', $fromStatusId],
                    ['=', 'status_to', $toStatusId],
                ]
            );

        $query->andWhere((new static)->extraWhere());

        return $query->one();
    }

    /**
     * Получаем список всех простых связей для данного документа
     *
     * @param integer $statusId - Id статуса, у которого смотрим простые связи
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAllSimpleLinksForTagFromId($statusId)
    {
        $query = static::find()
            ->where(
                [
                    'and',
                    ['=', 'status_from', $statusId],
                    ['=', 'type', self::LINK_TYPE_SIMPLE]
                ]
            );

        $query->andWhere((new static)->extraWhere());

        return $query->all();
    }

    /**
     * Получаем простые ссылки для статуса и определенных подстатусов
     *
     * @param integer $statusId   - id статуса
     * @param array   $tagToArray - массив подстатусов
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getSimpleLinksByTagFromIdWhereTagToArray($statusId, array $tagToArray)
    {
        $query = static::find()
            ->where(
                [
                    'and',
                    ['=', 'status_from', $statusId],
                    ['=', 'type', self::LINK_TYPE_SIMPLE],
                    ['in', 'status_to', $tagToArray]
                ]
            );

        $query->andWhere((new static)->extraWhere());

        return $query->all();
    }
}
