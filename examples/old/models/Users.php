<?php

namespace test\models;

use Yii;

/**
 * This is the model class for table "{{%users}}".
 *
 * @property integer $id
 * @property integer $status_id
 * @property string $name_short
 * @property string $name_long
 * @property integer $version
 *
 * @property UsersLinks[] $usersLinks
 * @property UsersLinks[] $usersLinks0
 * @property UsersLog[] $usersLogs
 */
class Users extends \docflow\models\Document
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * {@inheritdoc}
     */
    public static function docTag()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'log' => [
                'class' => \docflow\behaviors\Log::className(),
                'logAttributes' => ['id','status_id','name_short','name_long','atime'],
                'timeField' => 'atime',
                'logClass' => UsersLog::className(),
                'changedAttributesField' => 'changed_attributes',
                'versionField' => 'version',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            'default' => static::OP_ALL,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status_id'], 'required'],
            [['status_id', 'version'], 'integer'],
            [['name_short'], 'string', 'max' => 128],
            [['name_long'], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status_id' => 'yii-statuses module',
            'name_short' => 'Name Short',
            'name_long' => 'Name Long',
            'version' => 'Optimistic lock',
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
                'label' => Yii::t('docflow','View'),
                'conditions' => [
                    [
                        'condition' => 'own',
                        'label' => 'Only my',
                    ],
                    [
                        'condition' => 'all',
                        'label' => 'All',
                    ],
                ],
            ],
        ];
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLowerLinks()
    {
        return $this->hasMany(UsersLinks::className(), ['from_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpperLinks()
    {
        return $this->hasMany(UsersLinks::className(), ['to_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(UsersLog::className(), ['doc_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return UsersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UsersQuery(get_called_class());
    }
}
