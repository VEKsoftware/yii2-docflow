<?php

namespace docflow\models;

use Yii;
use yii\helpers\ArrayHelper;

use docflow\Docflow;
use docflow\models\Document;
use docflow\models\Statuses;
/**
 * This is the model class for table "statuses_doctypes".
 *
 * @property int $id
 * @property string $name
 * @property string $tag
 * @property Statuses[] $statuses
 */
class DocTypes extends Document
{
    protected static $_doctypes;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%doc_types}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'tag'], 'required'],
            [['name', 'tag'], 'string', 'max' => 128],
            ['statusTag', 'string', 'max' => 128],
            ['statusTag', 'exist', 'targetClass' => Statuses::className(), 'targetAttribute' => 'tag', 'filter' => ['doc_type_id' => $this->id]],
            [['tag'], 'unique'],
            ['tag', 'match', 'pattern'=>'/^[a-zA-Z0-9-_\.]+$/'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function docTag()
    {
        return 'doc_type';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('statuses', 'ID'),
            'name' => Yii::t('statuses', 'Document Name'),
            'tag' => Yii::t('statuses', 'Document Tag'),
        ];
    }

    /**
     * @inherit
     */
    public function behaviors()
    {
        $module = Docflow::getInstance();
        if(! $module) {
            throw new ErrorException('Load docflow module');
        }
        return [
            'access' => [
                'class' => $module->accessClass,
            ],
            [
                'class' => '\docflow\behaviors\StatusBehavior',
                'statusIdField' => 'status_id',
            ],
        ];
    }

    /**
     * @param $doc_string
     * @return static
     */
    public static function getDocType($doc_string)
    {
        $doctypes = static::getDoctypes();
        if(isset($doctypes[$doc_string])) {
            return $doctypes[$doc_string];
        }
        return NULL;
    }

    /**
     * @return static[] array of doc types
     */
    public static function getDocTypes()
    {
        if(empty(static::$_doctypes)) {
            static::$_doctypes = static::findDocTypes()->with('statuses')->all();
        }
        return static::$_doctypes;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function findDocTypes()
    {
        return static::find()->indexBy('tag');
    }

    /**
     * List of all statuses related to the doctype
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(Statuses::className(), ['doc_type_id' => 'id'])->indexBy('tag')->inverseOf('docType');
    }

    public function setStatusTag($tag)
    {
        $this->status = $tag;
    }

    public function getStatusTag()
    {
        return $this->status->tag;
    }
}
