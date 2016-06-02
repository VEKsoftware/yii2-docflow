<?php

namespace docflow\models;

use Yii;
use yii\helpers\ArrayHelper;

use docflow\Docflow;
use docflow\base\CommonRecord;
use docflow\models\Statuses;
use docflow\behaviors\StatusBehavior;

/**
 * This is the model class for table "statuses_doctypes".
 *
 * @property int        $id
 * @property string     $name
 * @property string     $tag
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
//            ['statusTag', 'string', 'max' => 128],
//            ['statusTag', 'exist', 'targetClass' => Statuses::className(), 'targetAttribute' => 'tag', 'filter' => ['doc_type_id' => $this->id]],
            [['tag'], 'unique'],
            ['tag', 'match', 'pattern' => '/^[a-zA-Z0-9-_\.]+$/'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('docflow', 'ID'),
            'name' => Yii::t('docflow', 'Document Name'),
            'tag' => Yii::t('docflow', 'Document Tag'),
        ];
    }

    /**
     * @inherit
     */
    public function behaviors()
    {
        $module = Docflow::getInstance();
        if (!$module) {
            throw new ErrorException('Load docflow module');
        }

        return [
            'access' => [
                'class' => $module->accessClass,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function accessData()
    {
        return [
            [
                'operation' => 'view',
                'label' => Yii::t('docflow', 'View'),
                'conditions' => [
                    [
                        'condition' => 'all',
                        'label' => 'All',
                    ],
                ],
            ],
            [
                'operation' => 'statuses_links_edit',
                'label' => Yii::t('docflow', 'Change statuses links'),
                'conditions' => [
                    [
                        'condition' => 'all',
                        'label' => 'All',
                    ],
                ],
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
        if (isset($doctypes[$doc_string])) {
            return $doctypes[$doc_string];
        }

        return null;
    }

    /**
     * @return static[] array of doc types
     */
    public static function getDocTypes()
    {
        if (empty(static::$_doctypes)) {
            static::$_doctypes = static::findDocTypes()->with('statuses')->all();
        }

        return static::$_doctypes;
    }

    /**
     * [[static::statusAccessTags]] returns a list of tags which are used for access right check.
     *
     * @return string[] List of tags which are used for access check
     */
    public static function statusAccessTags()
    {
        $statuses = static::getDoc()->statuses;

        return Statuses::statusesAccessTags($statuses);
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
        return $this->hasMany(Statuses::className(), ['doc_type_id' => 'id'])->with([
            'statusParent',
            'statusChildren'
        ])->indexBy('tag')->inverseOf('docType');
    }

    /**
     * List of all statuses related to the doctype
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesTop()
    {
        return $this->getStatuses()->joinWith(['linksStructureFrom'])->andWhere(['l_from.status_from' => null]);
    }

    public function getStatusesStructure()
    {
        $statuses = $this->statuses;
        $tree = [];
        foreach ($statuses as $status) {
            if ($status->statusParent === null) {
                $tree[] = $status;
            }
        }

        return $tree;
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
