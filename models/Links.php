<?php

namespace docflow\models;

use docflow\base\CommonRecord;
use Yii;

use yii\base\InvalidParamException;
use yii\base\ErrorException;

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
    protected $from_old;
    protected $from_new;

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
    public function transactions()
    {
        return [
            'default' => static::OP_ALL,
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
        if(! $insert && $this->getOldAttribute($type) !== $this->$type) {
            throw new ErrorException('You cannot change the type of link. Only delete and create new.');
        }

        if($this->$type === self::LINK_TYPE_FLTREE) {
            if($insert) $this->$level = 1;

            if(! $insert && $this->$to !== $this->getOldAttribute($to)) {
                throw new ErrorException('Cannot change child. Only change parent is allowed.');
            }

            // $from may be NULL (the toppest level)
            $this->from_new = $this->$from;
            if($this->from_new) {
                $this->upperLinksNew = static::findUpperLinks($this->from_new, $this->extraWhere())->all();
            }
            $this->from_old = $this->getOldAttribute($from);
            if($this->from_old) {
                $this->upperLinksOld = static::findUpperLinks($this->from_old, $this->extraWhere())->all();
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
        $from = array_values(static::$_linkFrom)[0];
        $to = array_values(static::$_linkTo)[0];
        $level = static::$_levelField;
        $type = static::$_typeField;
        parent::afterSave($insert, $changed_attributes);

        if($this->$type === self::LINK_TYPE_FLTREE) {

            // If parent is unchagned, we just stop here. Change of child is not allowed as yet.
            if($this->from_new === $this->from_old) return;

            $lower = ArrayHelper::getColumn($this->lowerLinks,$to);
            array_push($lower, $this->getOldAttribute($to));
            $upperOld = $this->upperLinksOld ? ArrayHelper::getColumn($this->upperLinksOld,$from) : NULL;
            $upperNew = $this->upperLinksNew ? ArrayHelper::getColumn($this->upperLinksNew,$from) : NULL;

            // Here we delete old unnecessary links
            if(!empty($upperOld) && !empty($lower)) {
                static::deleteAll(
                    array_merge(
                        [
                            'and',
                            [$from => $upperOld],
                            [$to => $lower],
                            [$type => self::LINK_TYPE_FLTREE]
                        ],
                        [$this->extraWhere()]
                    )
                );
            }

            // And then we add new relations
            if(!empty($upperNew) && !empty($lower)) {
                $inserts = [];
                $attributes = $this->attributes;

                // if there is a serial field id, we remove it from the array
                unset($attributes['id']);

                foreach($this->upperLinksNew as $boss) {
                    foreach(array_merge($this->lowerLinks,[$this]) as $dept) {
                        if(! $boss) continue;
                        $attributes[$from] = $boss->$from;
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

        if(! parent::beforeDelete()) return false;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        parent::afterDelete();
        $from = array_values(static::$_linkFrom)[0];
        $to = array_values(static::$_linkTo)[0];
        $level = static::$_levelField;
        $type = static::$_typeField;

        if($this->$type === self::LINK_TYPE_FLTREE) {

            $lower = ArrayHelper::getColumn($this->lowerLinks,$to);
            array_push($lower, $this->$to);

            // Here we delete old unnecessary links
            if(!empty($this->upperLinksOld) && !empty($lower)) {
                $upper = ArrayHelper::getColumn($this->upperLinksOld, $to);
                static::deleteAll(
                    array_merge(
                        [
                            'and',
                            [$from => $upper],
                            [$to => $lower],
                            [$type => self::LINK_TYPE_FLTREE]
                        ],
                        [$this->extraWhere()] // Additional parameters from child classes
                    )
                );
            }
        }

    }
}
