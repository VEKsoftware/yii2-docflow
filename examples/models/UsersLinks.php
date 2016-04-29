<?php

namespace test\models;

use Yii;

/**
 * This is the model class for table "{{%users_links}}".
 *
 * @property integer $id
 * @property integer $from_id
 * @property integer $to_id
 * @property string $link_type
 * @property string $relation_type
 *
 * @property Users $from
 * @property Users $to
 */
class UsersLinks extends \docflow\models\Links
{
    protected static $_baseClass = 'test\models\Users';
    protected static $_linkFrom = ['id' => 'from_id']; // ['id' => 'upper_id']
    protected static $_linkTo = ['id' => 'to_id'];   // ['id' => 'lower_id']
    protected static $_levelField = 'level';
    protected static $_typeField = 'link_type';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users_links}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['to_id', 'link_type'], 'required'],
            [['from_id', 'to_id'], 'integer'],
            [['link_type', 'relation_type'], 'string'],
            [['from_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['from_id' => 'id']],
            [['to_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['to_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'from_id' => 'From ID',
            'to_id' => 'To ID',
            'link_type' => 'Link Type',
            'relation_type' => 'Relation Type',
        ];
    }

    public static function instantiate($row)
    {
        if(isset($row['relation_type'])) {
            switch($row['relation_type']) {
            case('subordination'):
                return new UsersLinksSubordination($row);
                break;
            case('responsibility'):
                return new UsersLinksResponsibility($row);
                break;
            }
        }
        throw new ErrorException('Unknown type of UsersLinks link');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFrom()
    {
        return $this->hasOne(Users::className(), ['id' => 'from_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTo()
    {
        return $this->hasOne(Users::className(), ['id' => 'to_id']);
    }
}
