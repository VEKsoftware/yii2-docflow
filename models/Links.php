<?php

namespace docflow\models;

use docflow\components\CommonRecord;
use Yii;
use yii\base\InvalidParamException;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;

/**
 * This is an abstract class for handling relations between documents.
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
abstract class Links extends CommonRecord
{
    private static $_statuses;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%links}}';
    }

    /**
     * BaseClass is the class Links class handles relations for
     */
    public static function baseClass();

    /**
     * @inheritdoc
     */
    public static function instantiate($row)
    {
        if(isset($row['link_type']))
    }

    /**
     * Find all statuses for the specific doc type.
     *
     * @param string $docType The symbolic tag of the document type
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findStatuses($docType)
    {
        return static::find()
            ->joinWith('docType')
            ->where(['[[statuses_doctypes.symbolic_id]]' => $docType]);
    }

    /**
     * Find all statuses allowed by access rights.
     *
     * @param string $docType The symbolic tag of the document type
     * @param string|array $rightTag Right tag
     * @return \yii\db\ActiveQuery
     * @internal param string|string[] $symbolicId The symbolic tags of the rights
     *
     */
    public static function findAvailableStatuses($docType, $rightTag)
    {
        return static::findStatuses($docType)
            ->joinWith('linksTo')
            ->andWhere(['[[linksTo.right_tag]]' => $rightTag]);
    }

    /**
     * Return an array of all statuses for the specific doc type.
     *
     * @param string $docTypeId The symbolic id of the document type
     * @return Statuses[]
     */
    public static function listStatuses($docTypeId)
    {
        if (!isset(static::$_statuses[$docTypeId])) {
            static::$_statuses[$docTypeId] = static::findStatuses($docTypeId)->all();
        }

        return static::$_statuses[$docTypeId];
    }

    /**
     * Return relation to DocType
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocType()
    {
        return $this->hasOne(StatusesDoctypes::className(), ['id' => 'doc_type']);
    }

    /**
     * @return string|integer
     */
    public function getDocTypeName()
    {
        $list = $this->docTypeLabels();

        if (!empty($list) && isset($list[$this->doc_type])) {
            return $list[$this->doc_type];
        }

        return $this->doc_type;
    }

    /**
     * Doc types labels.
     *
     * @return array
     */
    public function docTypeLabels()
    {
        return StatusesDoctypes::createDropdown();
    }

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

    /**
     * {@inheritdoc}
     */
    public function getFullName()
    {
        return $this->docTypeName . ' - ' . $this->symbolic_id . ' - ' . $this->name;
    }
}
