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
 * @property string fullName
 */
abstract class Links extends CommonRecord
{
    const LINK_TYPE_SIMPLE = 'simple'; // Simple link one-to-one
    const LINK_TYPE_FLTREE = 'fltree'; // Extended tree link where each model contains likns with each upper level model

    private static $_statuses;
    protected static $_baseClass;
    protected static $_linkFrom; // ['id' => 'upper_id']
    protected static $_linkTo;   // ['id' => 'lower_id']
    protected static $_levelField;
    protected static $_typeField;

    protected $upperLinksOld;
    protected $upperLinksNew;
    protected $lowerLinks;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%links}}';
    }

    /**
     * {@inheritdoc}
     */
    public static function transactions()
    {
        return [
            'default' => OP_ALL,
        ];
    }

    /**
     * Return relation to source model this link refers to
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBaseFrom()
    {
        return $this->hasOne(static::$_baseClass, static::$_linkFrom);
    }

    /**
     * Return relation to destination model this link refers to
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBaseTo()
    {
        $config = static::configLink();
        return $this->hasOne($config['baseClass'], $config['linkTo']);
    }

    /**
     * This method is used by child classes to enstrict the where clause of the queries like select and delete.
     *
     * @param mixed $param Whatever you want to send to this method
     * @return minxed Structure as in [[yii\db\QueryInterface::where()]]
     */
    public function extraWhere()
    {
        return [];
    }

    /**
     * For Flat Tree links return all lower level links
     *
     * @param mixed $id base model id for which we look for the lower level links
     * @param mixed $param Extra where parameters in format of [[yii\db\QueryInterface::where()]]
     * @return \yii\db\ActiveQuery
     */
    public static function findLowerLinks($id, $andWhere = NULL)
    {
        $from = array_values(static::$_linkFrom)[0];
        $to = array_values(static::$_linkTo)[0];
        $level = static::$_levelField;
        $type = static::$_typeField;

        $query = static::find()->where([$from => $id, $type => self::LINK_TYPE_FLTREE]);
        if(! empty($andWhere)) {
            $query->andWhere($andWhere);
        }
        return $query;
    }

    /**
     * For Flat Tree links return all upper level links
     *
     * @param mixed $id base model id for which we look for the upper level links
     * @param mixed $param Extra where parameters in format of [[yii\db\QueryInterface::where()]]
     * @return \yii\db\ActiveQuery
     */
    public static function findUpperLinks($id, $andWhere = NULL)
    {
        $from = array_values(static::$_linkFrom)[0];
        $to = array_values(static::$_linkTo)[0];
        $level = static::$_levelField;
        $type = static::$_typeField;

        $query = static::find()->where([$to => $id, $type => self::LINK_TYPE_FLTREE]);
        if(! empty($andWhere)) {
            $query->andWhere($andWhere);
        }
        return $query;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $from = array_values(static::$_linkFrom)[0];
        $to = array_values(static::$_linkTo)[0];
        $level = static::$_levelField;
        $type = static::$_typeField;

        // Prevent changing the type of link
        if($this->oldAttributes[$type] !== $this->$type) {
            throw new ErrorException('You cannot change the type of link. Only delete and create new.');
        }

        if($this->$type === self::LINK_TYPE_FLTREE) {

            if($this->$to !== $this->getOldAttribute($to)) {
                throw new ErrorException('Cannot change child. Only change parent is allowed.');
            }

            // $from may be NULL (the toppest level)
            if($this->$from) {
                $this->upperLinksNew = static::findUpperLinks($this->$from, $this->extraWhere())->all();
            }
            $from_old = $this->getOldAttribute($from);
            if($from_old) {
                $this->upperLinksOld = static::findUpperLinks($from_old, $this->extraWhere())->all();
            }
            $this->lowerLinks = static::findLowerLinks($this->$to, $this->extraWhere())->all();
        }

        if(! parent::beforeSave($insert)) return false;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changed_attributes)
    {
        $config = static::configLink();
        $from = array_values($config['linkFrom'])[0];
        $to = array_values($config['linkTo'])[0];
        $level = $config['level_field'];
        $link_type = $config['type_field'];
        parent::afterSave($insert, $changed_attributes);

        if($this->$type === self::LINK_TYPE_FLTREE) {

            // If parent is unchagned, we just stop here. Change of child is not allowed as yet.
            if($this->$from === $this->getOldAttribute($from)) return;

            $lower = array_push($this->lowerLinks, $this->getOldAttribute($from));

            // Here we delete old unnecessary links
            if(!empty($this->upperLinksOld) && !empty($lower)) {
                static::deleteAll(
                    array_merge(
                        ['and', [$from => $this->upperLinksOld], [$to => $this->lowerLinks], [$type => self::LINK_TYPE_FLTREE]],
                        $this->extraWhere()
                    )
                );
            }

            // And then we add new relations
            if(!empty($this->upperLinksNew) && !empty($lower)) {
                $inserts = [];
                $attributes = $this->attributes;

                // if there is a serial field id, we remove it from the array
                unset($attributes['id']);

                foreach($this->upperLinksNew as $boss) {
                    foreach($lower as $dept) {
                        if(! $boss) continue;
                        $attributes[$from] = $boss->$to;
                        $attributes[$to] = $dept->$to;
                        $attributes[$level] = $boss->$level + $dept->$level; // +1;?
                        array_push($inserts, array_values($attributes));
                    }
                }
                static::getDb()->createCommand()->batchInsert(static::tableName(), array_keys($attributes), $inserts)->execute();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        $from = array_values(static::$_linkFrom)[0];
        $to = array_values(static::$_linkTo)[0];
        $level = static::$_levelField;
        $type = static::$_typeField;

        if($this->$type === self::LINK_TYPE_FLTREE) {

            // $from may be NULL (the toppest level)
            $from_old = $this->getOldAttribute($from);
            if($from_old) {
                $this->upperLinksOld = static::findUpperLinks($from_old, $this->extraWhere())->all();
            }
            $this->lowerLinks = static::findLowerLinks($this->$to, $this->extraWhere())->all();
        }

        if(! parent::beforeSave($insert)) return false;

        return true;
        return parent::beforeDelete();
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        $from = array_values(static::$_linkFrom)[0];
        $to = array_values(static::$_linkTo)[0];
        $level = static::$_levelField;
        $type = static::$_typeField;

        if($this->$type === self::LINK_TYPE_FLTREE) {

            $lower = array_push($this->lowerLinks, $this->getOldAttribute($from));

            // Here we delete old unnecessary links
            if(!empty($this->upperLinksOld) && !empty($lower)) {
                static::deleteAll(
                    array_merge(
                        ['and', [$from => $this->upperLinksOld], [$to => $this->lowerLinks], [$type => self::LINK_TYPE_FLTREE]],
                        $this->extraWhere()
                    )
                );
            }
        }

        return parent::afterSave();
    }
}
