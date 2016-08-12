<?php

namespace docflow\models\base\statuses;

use docflow\behaviors\LinkOrderedBehavior;
use docflow\behaviors\LinkSimpleBehavior;
use docflow\behaviors\LogMultiple;
use docflow\models\base\doc_type\DocTypes;
use docflow\models\base\Document;
use docflow\models\base\statuses\links\StatusesLinks;
use docflow\models\base\statuses\links\StatusesLinksStructure;
use docflow\models\base\statuses\links\StatusesLinksTransitions;
use yii;

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
 *
 * @mixin LinkSimpleBehavior
 * @mixin LinkOrderedBehavior
 */
class Statuses extends Document
{
    public $level;
    public $activeLinks;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%doc_statuses}}';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function docTag()
    {
        return 'vid';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'log' => [
                    'class' => LogMultiple::className(),
                    'logAttributes' => [
                        'id',
                        'doc_type_id',
                        'tag',
                        'name',
                        'description',
                        'order_idx',
                        'operations_ids',
                        'atime'
                    ],
                    'timeField' => 'atime',
                    'logClass' => StatusesLog::className(),
                    'changedAttributesField' => 'changed_attributes',
                    'versionField' => 'version',
                ],
                'structure' => [
                    'class' => LinkOrderedBehavior::className(),
                    'linkClass' => StatusesLinksStructure::className(),
                    'orderedFieldDb' => 'order_idx',
                    'orderedFieldValue' => 'order_idx',
                    'documentQuery' => function (ActiveQuery $query) {
                        /* True - конечный результат будет All(); null, false - one() */
                        $query->multiple = true;

                        return $query;
                    },
                    'indexBy' => 'tag'
                ],
                'transitions' => [
                    'class' => LinkSimpleBehavior::className(),
                    'linkClass' => StatusesLinksTransitions::className(),
                    'documentQuery' => function (ActiveQuery $query) {
                        /* True - конечный результат будет All(); null, false - one() */
                        $query->multiple = true;

                        return $query;
                    },
                    'indexBy' => 'tag'
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['doc_type_id', 'name', 'tag'], 'required'],
            [['doc_type_id', 'order_idx', 'version'], 'integer'],
            [['name', 'tag'], 'string', 'max' => 128],
            [['description'], 'string', 'max' => 512],
            ['tag', 'unique', 'targetAttribute' => ['doc_type_id', 'tag']],
            ['tag', 'match', 'pattern' => '/^[a-zA-Z0-9-_\.]+$/'],
            [['operations_ids'], 'string']
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
            'version' => 'Версия',
            'atime' => 'Штамп времени'
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
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
     * Связь с DocType
     *
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
    public function getDocName()
    {
        return $this->{'name'};
    }

    /**
     * Получаем статус по имени документа
     *
     * @param string $name - имя
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getStatusByDocName($name)
    {
        return static::find()
            ->where(['name' => $name])
            ->one();
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
        return static::find()
            ->where(['id' => $nodeId])
            ->with('docType');
    }
}
