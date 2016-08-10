<?php

namespace docflow\models\base\docType;

use docflow\models\base\DocFlowBase;
use docflow\models\statuses\Statuses;
use yii;
use docflow\Docflow;
use yii\base\ErrorException;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "statuses_doctypes".
 *
 * @property int        $id
 * @property string     $name
 * @property string     $tag
 * @property Statuses[] $statuses
 */
class DocTypes extends DocFlowBase
{
    /**
     * Переменная хранит загруженные типы документов
     *
     * @var array
     */
    protected static $docTypes = [];

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%doc_types}}';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['name', 'tag'], 'required'],
            [['name', 'tag', 'class', 'table'], 'string', 'max' => 128],
            [['description'], 'string', 'max' => 512],
            [['tag'], 'unique'],
            ['tag', 'match', 'pattern' => '/^[a-zA-Z0-9-_\.]+$/'],
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
            'name' => Yii::t('docflow', 'Document Name'),
            'tag' => Yii::t('docflow', 'Document Tag'),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     *
     * @throws ErrorException
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

    /*
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
    */

    /**
     * Получаем тип документа по тэгу
     *
     * @param string $docTag - тэг документа
     *
     * @return static
     */
    public static function getDocType($docTag)
    {
        $docTypes = static::getDocTypes();

        $return = null;

        if (array_key_exists($docTag, $docTypes)) {
            $return = $docTypes[$docTag];
        }

        return $return;
    }

    /**
     * Получаем типы документов со статусами
     *
     * @return static[] array of doc types
     */
    public static function getDocTypes()
    {
        if (count(static::$docTypes) < 1) {
            static::$docTypes = static::findDocTypes()
                ->with(['statuses'])
                ->all();
        }

        return static::$docTypes;
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
     * Получаем типы документов
     *
     * @return ActiveQuery
     */
    public static function findDocTypes()
    {
        return static::find()->indexBy('tag');
    }

    /**
     * List of all statuses related to the doctype
     *
     * @return ActiveQuery
     */
    public function getStatuses()
    {
        $query = $this->hasMany(Statuses::className(), ['doc_type_id' => 'id'])
            ->with(['statusParent', 'statusChildren'])
            ->indexBy('tag')
            ->inverseOf('docType');

        return $query;
    }

    /**
     * List of all statuses related to the doctype
     *
     * @return ActiveQuery
     */
    public function getStatusesTop()
    {
        return $this->getStatuses()
            ->joinWith(['linksStructureFrom'])
            ->andWhere(['l_from.status_from' => null]);
    }

    /**
     * Получаем структуру статусов
     *
     * @return array
     */
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

    /**
     * Устанавливаем тэг статуса
     *
     * @param string $tag - тэг
     *
     * @return void
     */
    public function setStatusTag($tag)
    {
        $this->status = $tag;
    }

    /**
     * Получаем тэг статуса
     *
     * @return mixed
     */
    public function getStatusTag()
    {
        return $this->status->tag;
    }
}
