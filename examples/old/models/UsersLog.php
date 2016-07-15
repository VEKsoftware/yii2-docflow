<?php

namespace test\models;

use Yii;

/**
 * This is the model class for table "{{%users_log}}".
 *
 * @property integer $id
 * @property integer $doc_id
 * @property string $atime
 * @property string $changed_attributes
 * @property integer $status_id
 * @property string $name_short
 * @property string $name_long
 * @property integer $changed_by
 *
 * @property Users $doc
 */
class UsersLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['doc_id', 'atime'], 'required'],
            [['doc_id', 'status_id', 'changed_by'], 'integer'],
            [['atime'], 'date', 'format' => 'php:Y-m-d H:i:sP'],
            [['changed_attributes'], 'string'],
            [['name_short'], 'string', 'max' => 128],
            [['name_long'], 'string', 'max' => 256],
            [['doc_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['doc_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'doc_id' => 'Doc ID',
            'atime' => 'Atime',
            'changed_attributes' => 'Changed Attributes',
            'status_id' => 'Status ID',
            'name_short' => 'Name Short',
            'name_long' => 'Name Long',
            'changed_by' => 'Changed By',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDoc()
    {
        return $this->hasOne(Users::className(), ['id' => 'doc_id']);
    }
}
