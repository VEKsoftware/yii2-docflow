<?php

namespace docflow\models;

use Yii;
use yii\helpers\ArrayHelper;

use docflow\base\CommonRecord;
use docflow\Docflow;
use docflow\models\Statuses;
/**
 * This is the model class for table "statuses_doctypes".
 *
 * @property int $id
 * @property string $name
 * @property string $symbolic_id
 * @property Statuses[] $statuses
 */
class DocTypes extends CommonRecord
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
            [['tag'], 'unique'],
            ['tag', 'match', 'pattern'=>'/^[a-zA-Z0-9-_\.]+$/'],
        ];
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
        return [
            'access' => [
                'class' => Docflow::getInstance()->accessClass,
            ],
        ];
    }

    /**
     * @param $doc_string
     * @return static
     */
    public static function getDoc($doc_string)
    {
        $doctypes = static::getDocs();
        if(isset($doctypes[$doc_string])) {
            return $doctypes[$doc_string];
        }
        return NULL;
    }

    /**
     * @return static[] array of doc types
     */
    public static function getDocs()
    {
        if(empty(static::$_doctypes)) {
            static::$_doctypes = static::findDocs()->all();
        }
        return static::$_doctypes;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function findDocs()
    {
        return static::find()->indexBy('tag');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(Statuses::className(), ['doc_type_id' => 'id'])->indexBy('tag')->inverseOf('docType');
    }
}
