<?php

namespace docflow\models\base;

use docflow\base\UnstructuredRecord;
use docflow\Docflow;
use yii;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;

/**
 * This is an abstract class for handling relations between documents.
 *
 * @property int    $id
 * @property string fullName
 */
abstract class Link extends UnstructuredRecord
{
    const LINK_TYPE_SIMPLE = 'simple'; // Simple link one-to-one
    const LINK_TYPE_FLTREE = 'fltree'; // Extended tree link where each model contains likns with each upper level model

    private static $_statuses;
    protected static $_baseClass;
    /* @var string имя столбца в таблице ноде с уникальным идентификатором */
    protected static $_fieldNodeId;
    /* @var string имя столбца в таблице ноде с "тэгом" */
    protected static $_fieldNodeTag;
    /* @var string имя столбца в таблице со связями с идентификатором родителя */
    protected static $_fieldLinkFrom;
    /* @var string имя столбца в таблице со связями с идентификатором ребенка */
    protected static $_fieldLinkTo;
    /* @var string имя столбца в таблице со связями с уровнем вложенности */
    protected static $_levelField;
    /* @var string имя столбца в таблице со связями с типом связи (simple, fltree) */
    protected static $_typeField;
    /* @var string имя столбца в таблице со связями с тэгом доступа */
    protected static $_rightTagField;
    /* @var string имя столбца в таблице со связями с типом relation-а */
    protected static $_relationTypeField;
    /* @var array массив содержащий аттрибуты, которые не нужно формировать при новых записях */
    protected static $_removedAttributes;

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
     * Return relation to source model this link refers to
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBaseFrom()
    {
        return $this->hasOne(static::$_baseClass,
            [static::$_fieldNodeId, static::$_fieldLinkFrom])->andFilterWhere($this->extraWhere());
    }

    /**
     * Return relation to destination model this link refers to
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBaseTo()
    {
        return $this->hasOne(static::$_baseClass,
            [static::$_fieldNodeId, static::$_fieldLinkTo])->andFilterWhere($this->extraWhere());
    }

    /**
     * For Flat Tree links return all lower level links
     *
     * @param mixed $id    base model id for which we look for the lower level links
     * @param mixed $param Extra where parameters in format of [[yii\db\QueryInterface::where()]]
     * @return \yii\db\ActiveQuery
     */
    public static function findLowerLinks($id, $andWhere = null)
    {
        $from = static::$_fieldLinkFrom;
        $to = static::$_fieldLinkTo;
        $level = static::$_levelField;
        $type = static::$_typeField;

        $query = static::find()->where([$from => $id, $type => self::LINK_TYPE_FLTREE]);
        if (!empty($andWhere)) {
            $query->andWhere($andWhere);
        }

        return $query;
    }

    /**
     * For Flat Tree links return all upper level links
     *
     * @param mixed $id    base model id for which we look for the upper level links
     * @param mixed $param Extra where parameters in format of [[yii\db\QueryInterface::where()]]
     * @return \yii\db\ActiveQuery
     */
    public static function findUpperLinks($id, $andWhere = null)
    {
        $from = static::$_fieldLinkFrom;
        $to = static::$_fieldLinkTo;
        $level = static::$_levelField;
        $type = static::$_typeField;

        $query = static::find()->where([$to => $id, $type => self::LINK_TYPE_FLTREE]);
        if (!empty($andWhere)) {
            $query->andWhere($andWhere);
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $from = static::$_fieldLinkFrom;
        $to = static::$_fieldLinkTo;
        $level = static::$_levelField;
        $type = static::$_typeField;

        // Prevent changing the type of link
        if (!$insert && $this->getOldAttribute($type) !== $this->$type) {
            throw new ErrorException('You cannot change the type of link. Only delete and create new.');
        }

        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->$type === self::LINK_TYPE_FLTREE) {
            if ($insert) {
                $this->$level = 1;
            }

            if ($this->$level !== 1) {
                throw new ErrorException('You cannot change supplementary link (whith level > 1)');
            }

            if (!$insert && $this->$to != $this->getOldAttribute($to)) {
                throw new ErrorException('Cannot change child. Only change parent is allowed.');
            }

            /* Получаем новые родительские стаутусы куда перемещается статус */
            $this->from_new = $this->$from;
            if ($this->from_new) {
                $this->upperLinksNew = static::findUpperLinks($this->from_new, $this->extraWhere())->all();
                $this->upperLinksNew = array_merge(
                    $this->upperLinksNew,
                    [
                        (object)[
                            $from => $this->from_new,
                            $level => 0
                        ]
                    ]
                );
            }

            /* Получаем старые родительские статусы перемещаемого статуса, включая самый ближайший(непосредственный) родительский статус*/
            $this->from_old = ($this->isNewRecord) ? [] : $this->getOldAttribute($to);
            if ($this->from_old) {
                $this->upperLinksOld = static::findUpperLinks($this->from_old, $this->extraWhere())->all();
            }

            /* Получаем детские(вложенные) статусы перемещаемого статуса */
            $this->lowerLinks = static::findLowerLinks($this->$to, $this->extraWhere())->all();

            $lower = ArrayHelper::getColumn($this->lowerLinks, $to);
            array_push($lower, $this->getOldAttribute($to));
            $upperOld = $this->upperLinksOld ? ArrayHelper::getColumn($this->upperLinksOld, $from) : null;

            /*
            Если есть старые родительские статусы и детские(вложенные) статусы (включая перемещаемый статус),
            то удаляем между ними все связи, кроме связи 1 уровня между перемещаемым статусом и
            самым ближним(непосредственным) старым родителем (эта связь изменит значение при $this->save()
            со старого непосредственного родителя на нового непосредственного родителя)
            */
            if (!empty($upperOld) && !empty($lower)) {
                static::deleteAll(
                    array_merge(
                        [
                            'and',
                            [$from => $upperOld],
                            [$to => $lower],
                            [$type => self::LINK_TYPE_FLTREE],
                            [
                                'not',
                                [
                                    'and',
                                    ['=', $level, 1],
                                    ['=', $to, $this->getOldAttribute($to)]
                                ]
                            ],
                        ],
                        [$this->extraWhere()]
                    )
                );
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changed_attributes)
    {
        $from = static::$_fieldLinkFrom;
        $to = static::$_fieldLinkTo;
        $level = static::$_levelField;
        $type = static::$_typeField;
        parent::afterSave($insert, $changed_attributes);

        if ($this->$type === self::LINK_TYPE_FLTREE) {

            // If parent is unchagned, we just stop here. Change of child is not allowed as yet.
            if ($this->from_new === $this->from_old) {
                return;
            }

            $lower = ArrayHelper::getColumn($this->lowerLinks, $to);
            array_push($lower, $this->getOldAttribute($to));
            $upperNew = $this->upperLinksNew ? ArrayHelper::getColumn($this->upperLinksNew, $from) : null;

            // And then we add new relations
            if (!empty($upperNew) && !empty($lower)) {
                $inserts = [];
                $attributes = $this->attributes;

                /* Вырезаем аттрибуты, которые не должны формироваться */
                foreach (static::$_removedAttributes as $removedAttribute) {
                    unset($attributes[$removedAttribute]);
                }

                /* Формируем новые связи между новыми родительскими статусами и детскими статусами (включая перемещаемый статус) */
                foreach ($this->upperLinksNew as $boss) {
                    foreach ($this->lowerLinks as $dept) {
                        if (!$boss) {
                            continue;
                        }

                        $attributes[$from] = $boss->$from;
                        $attributes[$to] = $dept->$to;
                        $attributes[$level] = ($boss->$level + $dept->$level + 1);
                        array_push($inserts, array_values($attributes));
                    }

                    /* Не позволяем дублировать связь 1 уровня между перемещаемым статусом и новым непосредственным родителем */
                    if ($boss->$from !== $this->$from) {
                        foreach ([$this] as $dept) {
                            if (!$boss) {
                                continue;
                            }

                            $attributes[$from] = $boss->$from;
                            $attributes[$to] = $dept->$to;
                            $attributes[$level] = ($boss->$level + $dept->$level);
                            array_push($inserts, array_values($attributes));
                        }
                    }
                }

                /*
                Условие добавлено для того, чтобы не выкидывало ошибку при отсутствии новых связей
                (прим. статус без родителей и детей перемещается в другой статус)
                */
                if (!empty($inserts)) {
                    static::getDb()
                        ->createCommand()
                        ->batchInsert(static::tableName(), array_keys($attributes), $inserts)
                        ->execute();
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        $from = static::$_fieldLinkFrom;
        $to = static::$_fieldLinkTo;
        $level = static::$_levelField;
        $type = static::$_typeField;

        if ($this->$type === self::LINK_TYPE_FLTREE) {
            if ($this->$level !== 1) {
                throw new ErrorException('You cannot delete supplementary link (whith level > 1)');
            }

            // $from may be NULL (the toppest level)
            $from_old = $this->getOldAttribute($to);
            if ($from_old) {
                $this->upperLinksOld = static::findUpperLinks($from_old, $this->extraWhere())->all();
            }

            $this->lowerLinks = static::findLowerLinks($this->$to, $this->extraWhere())->all();
        }

        if (!parent::beforeDelete()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        parent::afterDelete();
        $from = static::$_fieldLinkFrom;
        $to = static::$_fieldLinkTo;
        $level = static::$_levelField;
        $type = static::$_typeField;

        if ($this->$type === self::LINK_TYPE_FLTREE) {
            $lower = ArrayHelper::getColumn($this->lowerLinks, $to);
            array_push($lower, $this->$to);

            // Here we delete old unnecessary links
            if (!empty($this->upperLinksOld) && !empty($lower)) {
                $upper = ArrayHelper::getColumn($this->upperLinksOld, $from);
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

    /**
     * Массовое удаление всех простых связей у текущего статуса
     *
     * @param integer $statusFromId - идентификатор документа, у которого удаляем все простые связи
     *
     * @return bool
     */
    public static function batchDeleteSimpleLinks($statusFromId)
    {
        $delCondition = [
            static::$_fieldLinkFrom => $statusFromId,
            static::$_typeField => static::LINK_TYPE_SIMPLE
        ];

        $relationType = static::getRelationType();

        if (!empty($relationType)) {
            $delCondition = array_merge($delCondition, [static::$_relationTypeField => $relationType]);
        }

        /* Удаляем все текущие простые связи */

        return (bool)static::deleteAll($delCondition);
    }


    /**
     * Массово добавляем простые связи
     *
     * @param Statuses|Document $owner          - Документ
     * @param array             $documentsArray - массив, содержащий объекты документов, к которым устанавливается простая связь
     *
     * @return bool
     *
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     */
    public static function batchAddSimpleLinks($owner, $documentsArray)
    {
        $relationType = static::getRelationType();

        /* Подготавливаем столбцы для массового добавления */
        $cols = static::getColsForSimpleLink($relationType);

        /* Подготавливаем содержимое для массового добавления */
        $rows = static::getRowsForSimpleLink($owner, $documentsArray, $relationType);

        /* Массово добавляем */

        return (bool)Yii::$app->{Docflow::getInstance()->db}
            ->createCommand()
            ->batchInsert(static::tableName(), $cols, $rows)
            ->execute();
    }

    /**
     * Формируем столбцы для добавления
     *
     * @param string $relationType - тип
     *
     * @return array
     */
    protected static function getColsForSimpleLink($relationType)
    {
        $cols = [
            static::$_fieldLinkFrom,
            static::$_fieldLinkTo,
            static::$_rightTagField,
            static::$_typeField
        ];

        if (!empty($relationType)) {
            $cols[] = static::$_relationTypeField;
        }

        return $cols;
    }

    /**
     * Формируем данные для добавления
     *
     * @param Statuses|Document $owner          - документ
     * @param array             $documentsArray - массив, содержащий объекты документов, к которым устанавливается простая связь
     * @param string            $relationType   - тип
     *
     * @return array
     *
     * @throws \yii\base\ErrorException
     */
    protected static function getRowsForSimpleLink($owner, $documentsArray, $relationType)
    {
        $rows = [];
        $rightTagFormat = '%s.%s.%s';

        foreach ($documentsArray as $value) {
            $attr = [
                $owner->{static::$_fieldNodeId},
                $value->{static::$_fieldNodeId},
            ];

            if (!empty(static::$_fieldNodeId) and is_string(static::$_fieldNodeId)) {
                $attr[] = sprintf(
                    $rightTagFormat,
                    $owner->docType->tag,
                    $owner->{static::$_fieldNodeTag},
                    $value->{static::$_fieldNodeTag}
                );
            }

            $attr[] = static::LINK_TYPE_SIMPLE;

            if (!empty($relationType)) {
                $attr[] = $relationType;
            }

            $rows[] = $attr;
        }

        return $rows;
    }

    /**
     * Получаем relation_type из extraWhere
     *
     * @return mixed
     */
    public static function getRelationType()
    {
        /* @noinspection DynamicInvocationViaScopeResolutionInspection */
        /* @var $extraWhere array */
        $extraWhere = static::extraWhere();

        return (!empty($extraWhere[static::$_relationTypeField]))
            ? $extraWhere[static::$_relationTypeField]
            : '';
    }

    /**
     * Получаем тип из extraWhere
     *
     * @return mixed
     */
    public static function getType()
    {
        /* @noinspection DynamicInvocationViaScopeResolutionInspection */
        /* @var $extraWhere array */
        $extraWhere = static::extraWhere();

        return (!empty($extraWhere[static::$_typeField]))
            ? $extraWhere[static::$_typeField]
            : '';
    }
}
