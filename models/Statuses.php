<?php

namespace docflow\models;

use docflow\behaviors\LinkOrderedBehavior;
use docflow\behaviors\LinkSimpleBehavior;
use yii;

use docflow\Docflow;

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
        return 'status';
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
            ],
            'transitions' => [
                'class' => LinkSimpleBehavior::className(),
                'linkClass' => StatusesLinksTransitions::className(),
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
     * Получаем Статус по тэгу
     *
     * @param string $tag                  - тэг статуса
     * @param bool   $needFromIdAndLevel   - false - возвращает объект Statuses,
     *                                     true - возвращаем массив с данными поg\ перемещаемому Статусу
     *                                     и в каком статусе непосредственно (1 уровень) находится перемещаемый статус
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getStatusForTag($tag, $needFromIdAndLevel = false)
    {
        $query = static::find()
            ->select([
                'id' => 'doc_statuses.id',
                'doc_type_id' => 'doc_statuses.doc_type_id',
                'tag' => 'doc_statuses.tag',
                'name' => 'doc_statuses.name',
                'description' => 'doc_statuses.description',
                'order_idx' => 'doc_statuses.order_idx'
            ])
            ->where(['=', 'doc_statuses.tag', $tag])
            ->limit(1);

        if ($needFromIdAndLevel === true) {
            $query->addSelect([
                'fromId' => 'd_s_l.status_from',
                'level' => 'd_s_l.level',
            ]);
            $query->leftJoin(
                'doc_statuses_links d_s_l',
                'doc_statuses.id = d_s_l.status_to and d_s_l.type = \'fltree\' and d_s_l.level = 1'
            );
            $query->asArray(true);

            $relationType = Link::getRelationType();

            if (!empty($relationType)) {
                $query->andOnCondition(['d_s_l.relation_type' => $relationType]);
            }
        }

        return $query->one();
    }

    /**
     * Получаем массив со статусами в уровне, где находится перемещаемый статус
     *
     * @param integer $fromId    - id статуса, в котором находится перемещаемый статус
     * @param integer $level     - уровень, в котором находится перемещаемый статус
     * @param integer $docTypeId - id документа, которому принадлежит перемещаемый статус
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getStatusesForLevel($fromId, $level, $docTypeId)
    {
        $query = static::find()
            ->select(['orderIdx' => 'doc_statuses.order_idx', 'tag' => 'doc_statuses.tag', 'id' => 'doc_statuses.id'])
            ->orderBy(['order_idx' => SORT_ASC])
            ->asArray(true);

        if (!empty($level)) {
            $query->innerJoin(
                'doc_statuses_links d_s_l',
                'doc_statuses.id = d_s_l.status_to and d_s_l.type = \'fltree\' and d_s_l.status_from = :from',
                [':from' => $fromId]
            );
            $query->where([
                'and',
                ['=', 'd_s_l.level', $level],
                ['=', 'doc_statuses.doc_type_id', $docTypeId]
            ]);
        } else {
            $query->leftJoin(
                'doc_statuses_links d_s_l',
                'doc_statuses.id = d_s_l.status_to and d_s_l.type = \'fltree\''
            );
            $query->where([
                'and',
                ['is', 'd_s_l.status_to', null],
                ['=', 'doc_statuses.doc_type_id', $docTypeId]
            ]);
        }

        $relationType = Link::getRelationType();

        if (!empty($relationType)) {
            $query->andOnCondition(['d_s_l.relation_type' => $relationType]);
        }

        return $query->all();
    }

    /**
     * Получаем Статусы по массиву, содержащему Тэги.
     *
     * @param array   $tagsArray - массив, содержащий тэги
     * @param integer $docTypeId - id типа документа
     *
     * @return array|\yii\db\ActiveRecord[]
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
     * @return array|\yii\db\ActiveQuery[]
     */
    public function getStatusesByIdArray(array $idArray)
    {
        return static::find()
            ->where(['in', 'id', $idArray])
            ->indexBy('tag');
    }

    /**
     * Получаем статус по его id
     *
     * @param integer $statusId - id статуса
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getStatusById($statusId)
    {
        return static::find()
            ->where(['=', 'id', $statusId]);
    }

    /**
     * Получаем имя класса
     *
     * @return string
     */
    public function getCurrentName()
    {
        return static::className();
    }

    /**
     * Получаем статус по тегу и id типа документа
     *
     * @param string  $tag       - тэг статуса
     * @param integer $docTypeId - id документа
     *
     * @return $this
     */
    /*
    public function getStatusForTag($tag, $docTypeId)
    {
        return static::find()
            ->where([
                'and',
                ['tag' => $tag],
                ['doc_type_id' => $docTypeId]
            ]);
    }*/
}
