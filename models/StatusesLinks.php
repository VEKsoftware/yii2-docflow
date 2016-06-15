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
     * @param integer     $statusId     - id статуса
     * @param null|string $relationType - тип связи
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getFlTreeLinkForStatusForLevel1($statusId, $relationType = null)
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

        if (!empty($relationType) && is_string($relationType)) {
            $query->andWhere(['=', 'relation_type', $relationType]);
        }

        return $query->one();
    }

    /**
     * Получаем ccылку на родителя родителя (2 уровень)
     *
     * @param integer     $statusId     - id статуса
     * @param null|string $relationType - тип связи
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getFlTreeLinkForStatusForLevel1And2($statusId, $relationType = null)
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

        if (!empty($relationType) && is_string($relationType)) {
            $query->andWhere(['=', 'relation_type', $relationType]);
        }

        return $query->all();
    }

    /**
     * Получаем массив с данными о наличии "детей" - вложенных статусов
     *
     * @param integer $statusId - id Статуса1, у которого имем вложенные статусы
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getChildStatusesForStatus($statusId)
    {
        return static::find()
            ->where([
                'and',
                ['=', 'type', 'fltree'],
                ['=', 'status_from', $statusId],
            ])
            ->asArray(true)
            ->all();
    }

    /**
     * Получаем SimpleLink по id статусов From и To
     *
     * @param integer     $fromStatusId - тэг статуса From
     * @param integer     $toStatusId   - тэг статуса To
     * @param null|string $relationType - тип связи
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getSimpleLinkForStatusFromIdAndStatusToId($fromStatusId, $toStatusId, $relationType = null)
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

        if (!empty($relationType) && is_string($relationType)) {
            $query->andWhere(['=', 'relation_type', $relationType]);
        }

        return $query->one();
    }
}
