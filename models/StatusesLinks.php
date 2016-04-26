<?php

namespace docflow\models;

use Yii;

use docflow\Docflow;
use docflow\models\Link;
use docflow\models\Statuses;

/**
 * This is the model class for table "statuses_links".
 *
 * @property int $status_from
 * @property int $status_to
 * @property string $right_tag
 * @property Statuses $statusFrom
 * @property Statuses $statusTo
 */
class StatusesLinks extends Link
{
    protected static $_baseClass = 'docflow\models\Statuses';
    protected static $_linkFrom = ['id' => 'status_from']; // ['id' => 'upper_id']
    protected static $_linkTo = ['id' => 'status_to'];   // ['id' => 'lower_id']
    protected static $_levelField = 'level';
    protected static $_typeField = 'link_type';

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
            [['status_from', 'status_to', 'right_tag'], 'required'],
            [['status_from', 'status_to'], 'integer'],
            [['right_tag'], 'string'],
            ['right_tag', 'match', 'pattern'=>'/^[a-zA-Z0-9-_\.]+$/'],
            [['status_from', 'status_to'], 'exist', 'targetClass' => Statuses::className(), 'targetAttribute' => 'id'],
        ];
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
        if(! isset($row['type'])) {
            throw new ErrorException('You need pass doc_statuses type in the $row parameter for instantiation of the StatusesLinks model');
        }
        switch($row['type']) {
        case(static::LINK_TYPE_SIMPLE):
            return new StatusesLinksTransitions($row);
        case(static::LINK_TYPE_FLTREE):
            return new StatusesLinksStructure($row);
        default:
            throw new ErrorException('Unknonw doc_statuses_links link type');
        }
    }
}
