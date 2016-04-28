<?php

namespace docflow\models;

use Yii;
use yii\base\InvalidParamException;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;

use docflow\Docflow;
use docflow\models\Document;
use docflow\models\DocTypes;

/**
 * This is the model class for table "statuses". It is user through model DocTypes.
 * To get list of available statuses just type: ``$list_of_statuses = DocTypes::getDoc('document')->statuses;``
 *
 * @property int $id
 * @property int $doc_type
 * @property string $name
 * @property string $description
 * @property StatusesLinks[] $statusesLinks
 * @property StatusesLinks[] statusesLinksTo
 * @property string docTypeName
 * @property string tag
 * @property string fullName
 */
class Statuses extends Document
{
    public $level;

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
            'doc_type_id' => Yii::t('statuses', 'Document Type'),
            'name' => Yii::t('statuses', 'Status Name'),
            'description' => Yii::t('statuses', 'Status Description'),
            'tag' => Yii::t('statuses', 'Status Tag'),
        ];
    }

    /**
     * List of statuses available for transition to with the ceratin access right.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAvailableStatuses($rightIds = null)
    {
        return $this->hasMany(self::className(), ['id' => 'status_to'])
            ->via('linksTo', function ($q) use ($rightIds) {
                /** @var ActiveQueryInterface $q */
                $q->andFilterWhere(['right_tag' => $rightIds]);
            });
    }

    /**
     * The method returns a list of all links leading to the source statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksFrom()
    {
        return $this->hasMany(StatusesLinks::className(), ['status_to' => 'id'])->from('doc_statuses_links l_from');
    }

    /**
     * The method returns a list of all links leading to the target statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTo()
    {
        return $this->hasMany(StatusesLinks::className(), ['status_from' => 'id'])->from('doc_statuses_links l_to');
    }

    /**
     * The method returns a list of structure links leading to the source statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksStructureFrom()
    {
        return $this->getLinksFrom()->andOnCondition(['l_from.type' => StatusesLinks::LINK_TYPE_FLTREE]);
    }

    /**
     * The method returns a list of structure links leading to the target statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksStructureTo()
    {
        return $this->getLinksTo()->andOnCondition(['l_to.type' => StatusesLinks::LINK_TYPE_FLTREE]);
    }

    /**
     * The method returns a list of Transitions links leading to the source statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTransitionsFrom()
    {
        return $this->getLinksFrom()->andOnCondition(['l_from.type' => StatusesLinks::LINK_TYPE_SIMPLE]);
    }

    /**
     * The method returns a list of Transitions links leading to the target statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTransitionsTo()
    {
        return $this->getLinksTo()->andOnCondition(['l_to.type' => StatusesLinks::LINK_TYPE_SIMPLE]);
    }

    /**
     * The method returns a structure link with level=1 leading to the source statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksParent()
    {
        return $this->getLinksStructureFrom()->andOnCondition(['l_from.level' => 1]);
    }

    /**
     * The method returns a list of structure links with level=1 leading to the target statuses of the current one
     * @return \yii\db\ActiveQuery
     */
    public function getLinksChildren()
    {
        return $this->getLinksStructureTo()->andOnCondition(['l_to.level' => 1]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesTo()
    {
        return $this->hasMany(static::className(), ['id' => 'status_to'])
            ->via('linksTo');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesLower()
    {
        return $this->hasMany(static::className(), ['id' => 'status_to'])
            ->via('linksStructureTo')
        ;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesUpper()
    {
        return $this->hasMany(static::className(), ['id' => 'status_from'])
            ->via('linksStructureFrom')
        ;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocType()
    {
        return $this->hasOne(DocTypes::className(), ['id' => 'doc_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusParent()
    {
        return $this->hasOne(Statuses::className(), ['id' => 'status_from'])
            ->via('linksParent')
        ;
    }

    /**
     * @param \docflow\models\Statuses
     */
    public function setStatusParent($newParent)
    {
        $parentLink = $this->linksParent;
        if($newParent instanceOf self) {
            $parentLink->status_from = $newParent->id;
        } elseif(is_null($newParent)) {
            $parentLink->status_from = NULL;
        }
        $parentLink->save();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusChildren()
    {
        return $this->hasMany(Statuses::className(), ['id' => 'status_to'])
            ->via('linksChildren')
            ->inverseOf('statusParent')
        ;
    }

    /**
     * Check if the target status allowed
     * @return boolean is the target status allowed to be set?
     */
    public function rightsForStatusTo($statusTag)
    {
        $docType = $this->docType;

        /** @var \docflow\models\Statuses $statusTo */
        $statusTo = ArrayHelper::getValue($docType->statuses, $statusTag);
        if (!isset($statusTo)) {
            return [];
        }

        // Получаем ссылки где status_to равен нашему $statusTo
        $linksTo = $statusTo->linksTo;
        return array_unique(array_filter(ArrayHelper::getColumn($linksTo, 'right_tag')));
    }

}
