<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 09.08.16
 * Time: 16:05
 */

namespace docflow\models\base\operations\flTree\links;

use docflow\behaviors\LogMultiple;
use docflow\Docflow;
use docflow\models\base\Link;
use docflow\models\base\operations\Operations;
use yii;

class OperationsLinks extends Link
{
    const OPERATIONS_RELATION_TYPES_NOPE = 'Nope';

    public static $_baseClass = 'docflow\models\base\Operations';
    public static $_fieldNodeId = 'id';
    public static $_fieldLinkFrom = 'from';
    public static $_fieldLinkTo = 'to';
    public static $_levelField = 'lvl';
    public static $_typeField = 'tp';
    public static $_rightTagField = '';
    public static $_relationTypeField = 'rtp';
    public static $_fieldNodeTag = 'id';
    public static $_removedAttributes = ['id', 'atime', 'version'];

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%operations_links}}';
    }

    /**
     * Получаем БД с которой работаем
     *
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->{Docflow::getInstance()->db};
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['from', 'to', 'tp', 'lvl', 'rtp'], 'required', 'on' => static::LINK_TYPE_FLTREE],
            [['from', 'to', 'tp', 'rtp'], 'required', 'on' => static::LINK_TYPE_SIMPLE],
            [['from', 'to', 'lvl', 'version'], 'integer'],
            [['tp', 'rtp'], 'string'],
            [['atime'], 'safe'],
            [
                ['from', 'to', 'tp', 'rtp', 'lvl'],
                'unique',
                'targetAttribute' => ['from', 'to', 'tp', 'rtp', 'lvl'],
                'message' => 'The combination of From, To, Tp, Rtp and Lvl has already been taken.'
            ],
            [
                ['from'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Operations::className(),
                'targetAttribute' => ['from' => 'id']
            ],
            [
                ['to'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Operations::className(),
                'targetAttribute' => ['to' => 'id']
            ],
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
            'id' => 'ID',
            'from' => 'From',
            'to' => 'To',
            'tp' => 'Tp',
            'rtp' => 'Rtp',
            'lvl' => 'Lvl',
            'atime' => 'At Data',
            'version' => 'Версия'
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
            'log' => [
                'class' => LogMultiple::className(),
                'logAttributes' => [
                    'id',
                    'from',
                    'to',
                    'tp',
                    'lvl',
                    'rtp',
                    'atime'
                ],
                'timeField' => 'atime',
                'logClass' => OperationsLinksLog::className(),
                'changedAttributesField' => 'changed_attributes',
                'versionField' => 'version',
            ],
        ];
    }

    /**
     * Связь с таблицей операций
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFrom()
    {
        return $this->hasOne(Operations::className(), ['id' => 'from']);
    }

    /**
     * Связь с таблицей операций
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTo()
    {
        return $this->hasOne(Operations::className(), ['id' => 'to']);
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
                static::LINK_TYPE_FLTREE => ['from', 'to', 'tp', 'lvl', 'rtp'],
                static::LINK_TYPE_SIMPLE => ['from', 'to', 'tp', 'rtp'],
            ]
        );
    }
}
