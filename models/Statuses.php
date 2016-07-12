<?php

namespace docflow\models;

use docflow\behaviors\LinkOrderedBehavior;
use docflow\behaviors\LinkSimpleBehavior;
use docflow\behaviors\LinkStructuredBehavior;
use yii;

use docflow\Docflow;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "statuses". It is user through model DocTypes.
 * To get list of available statuses just type: ``$list_of_statuses = DocTypes::getDoc('document')->statuses;``
 *
 * @property int             $id
 * @property int             $doc_type
 * @property string          $name
 * @property string          $description
 * @property StatusesLinks[] $statusesLinks
 * @property StatusesLinks[] statusesLinksTo
 * @property string          docTypeName
 * @property string          tag
 * @property string          fullName
 */
class Statuses extends Document
{
    public $level;
    public $activeLinks;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%doc_statuses}}';
    }

    /**
     * {@inheritdoc}
     */
    public static function docTag()
    {
        return 'vid';
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
            'structure' => [
                'class' => LinkOrderedBehavior::className(),
                'linkClass' => StatusesLinksStructure::className(),
                'orderedField' => 'order_idx',
                'documentQuery' => function () {
                    $query = static::find();
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                }
            ],
            'transitions' => [
                'class' => LinkSimpleBehavior::className(),
                'linkClass' => StatusesLinksTransitions::className(),
                'documentQuery' => function () {
                    $query = static::find();
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['doc_type_id', 'name', 'tag'], 'required'],
            [['doc_type_id'], 'integer'],
            [['name', 'tag'], 'string', 'max' => 128],
            [['description'], 'string', 'max' => 512],
            ['tag', 'unique', 'targetAttribute' => ['doc_type_id', 'tag']],
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
            'doc_type_id' => Yii::t('docflow', 'Document Type'),
            'name' => Yii::t('docflow', 'Status Name'),
            'description' => Yii::t('docflow', 'Status Description'),
            'tag' => Yii::t('docflow', 'Status Tag'),
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
                        'condition' => 'any',
                        'label' => Yii::t('docflow', 'Any'),
                    ],
                ],
            ],
            [
                'operation' => 'create',
                'label' => Yii::t('docflow', 'Create'),
                'conditions' => [
                    [
                        'condition' => 'any',
                        'label' => Yii::t('docflow', 'Any'),
                    ],
                ],
            ],
            [
                'operation' => 'update',
                'label' => Yii::t('docflow', 'Update'),
                'conditions' => [
                    [
                        'condition' => 'any',
                        'label' => Yii::t('docflow', 'Any'),
                    ],
                ],
            ],
        ];
    }

    /**
     * [[static::statusAccessTags]] returns a list of tags which are used for access right check.
     *
     * @return string[] List of tags which are used for access check
     */
    public static function statusAccessTags($statuses)
    {
        $result = [];
        foreach ($statuses as $status_from) {
            foreach ($statuses as $status_to) {
                $result[] = $status_from->tag . '-' . $status_to->tag;
            }
        }

        return $result;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocType()
    {
        return $this->hasOne(DocTypes::className(), ['id' => 'doc_type_id']);
    }

    /**
     * Получаем Статусы по массиву, содержащему Тэги.
     *
     * @param array   $tagsArray - массив, содержащий тэги
     * @param integer $docTypeId - id типа документа
     *
     * @return yii\db\ActiveQuery
     */
    public static function getStatusesForTagsArray(array $tagsArray, $docTypeId)
    {
        return static::find()
            ->where([
                'and',
                ['in', 'tag', $tagsArray],
                ['=', 'doc_type_id', $docTypeId]
            ])
            ->indexBy('tag');
    }

    /**
     * Получаем массив статусов по их id
     *
     * @param array $idArray - массив, содержащий список id-ек
     *
     * @return yii\db\ActiveQuery
     */
    public static function getStatusesByIdArray(array $idArray)
    {
        return static::find()
            ->where(['in', 'id', $idArray])
            ->indexBy('tag');
    }

    /**
     * Return field name which use how Document `name`
     *
     * @return string Document name
     */
    public static function docNameField()
    {
        return 'name';
    }

    /**
     * Получаем документ по его дентификатору
     *
     * @param int $nodeId - id ноды
     *
     * @return ActiveQuery
     */
    public static function getDocumentByNodeId($nodeId)
    {
        return static::find()->where(['id' => $nodeId]);
    }
}
