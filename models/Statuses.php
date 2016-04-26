<?php

namespace docflow\models;

use Yii;
use yii\base\InvalidParamException;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;

use docflow\Docflow;
use docflow\base\CommonRecord;

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
 * @property string symbolic_id
 * @property string fullName
 */
class Statuses extends CommonRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%doc_statuses}}';
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
     * Return relation to DocType
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocType()
    {
        return $this->hasOne(DocTypes::className(), ['id' => 'doc_type_id']);
    }

    /**
     * List of statuses available for transition to with the ceratin access right.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAvailableStatuses($rightIds = null)
    {
        return $this->hasMany(self::className(), ['id' => 'status_to'])
            ->via('linksFrom', function ($q) use ($rightIds) {
                /** @var ActiveQueryInterface $q */
                $q->andFilterWhere(['right_tag' => $rightIds]);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinksFrom()
    {
        return $this->hasMany(StatusesLinks::className(), ['status_from' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTo()
    {
        return $this->hasMany(StatusesLinks::className(), ['status_to' => 'id']);
    }

}
