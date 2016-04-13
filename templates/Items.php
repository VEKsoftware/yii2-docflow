<?php

namespace app\models;

use app\base\VekActiveRecord;
use app\behaviors\Access;
use app\behaviors\Log;
use statuses\behaviors\Statuses;
use Yii;
use yii\base\ErrorException;
use yii\bootstrap\Html;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * The class for document {doc_type: name}:
 * {doc_type: description}
 *
 * @mixin \app\behaviors\Log
 * @mixin \app\behaviors\Access
 * @mixin \statuses\behaviors\Statuses
 *
 * @property Units $owner @see [[Items::getOwner()]].
 * @property ItemsPropsTypes[]|null $propsTypes @see [[Items::getPropsTypes()]].
 * @property ItemsPropsValues[]|null $propertiesValues @see [[Items::getPropertiesValues()]].
 * @property ItemsPropsValues|null $propertiesValue @see [[Items::getPropertiesValue()]].
 * @property array|null $propsTypesNames @see [[Items::getPropsTypesNames()]].
 * @property Categories|null $category @see [[Items::getCategory()]].
 * @property ItemsLog[] $itemLog Журнал изменений модели.
 *
 * **** Методы для категории sims ****
 * @property string docTypeSymbolicId
 * содержит дополнительные свойства, кроме тех которые содержатся в propertiesValues.
 */
class Items extends VekActiveRecord
{
    /* @inheritdoc */
    protected static $_models = [];

    public static $sysStatuses = [
        'withdrawn' => 1,   // Списан
        'lost' => 2,        // Утерян
        'moves' => 3,       // Перемещается
    ];

    // ID категории в которой хранятся сим-карты
    const SIMS_ITEMS_CATEGORY_ID = 1;

    // Идентификатор категории статусов для сим-карт (link)
    const SIMS_STATUSES_DOCTYPES_ID = 1;
    const SIMS_DOCTYPE_SYMBOLIC_ID = 'items_sim';

    /** @var array $catToDocLinks Ссылки [категория => тип документа] */
    private static $catToDocLinks = [
        Items::SIMS_ITEMS_CATEGORY_ID => Items::SIMS_DOCTYPE_SYMBOLIC_ID,
    ];

    /** @var ItemsPropsTypes[] $allPropsTypes Массив свойств для категорий, инициализируется в \app\components\Initializer */
    public static $allPropsTypes;

    /** @var ItemsPropsValues[] Массив свойств, куда сохраняются связанные модели ItemsPropsValues, @see setProperty() */
    protected $_updateProps = [];

    private static $_allScenarios;

    /**
     * Данный метод обновляет необходимо вызывать когда вы хотите обновить связанное с товаром свойство,
     * если оно не было задано, то оно автоматически будет создано.
     *
     * @param $propertyName string Символьный идентификатор свойства
     * @param $value string Значение свойства
     * @throws ErrorException
     */
    public function setProperty($propertyName, $value)
    {
        $value = (string)$value;
        /** @var ItemsPropsTypes $propType */
        $propType = ArrayHelper::getValue($this->propsTypes, $propertyName);
        if (!isset($propType)) {
            throw new ErrorException("Current Item has no property {$propertyName}");
        }

        /** @var ItemsPropsValues $oldValueObj */
        $oldValueObj = ArrayHelper::getValue($this->propertiesValues, $propType->id);

        // Если свойство имеется в таблице, то обновляем, иначе вставляем.
        if (isset($oldValueObj)) {
            // Проверка, нужно ли обновлять свойство
            if ($oldValueObj->value !== $value) {
                $oldValueObj->value = $value;
                $this->_updateProps[$propertyName] = $oldValueObj;
            }
        } else {
            $this->_updateProps[$propertyName] = new ItemsPropsValues(
                [
                    'type_id' => $propType->id,
                    'value' => $value,
                ]
            );
        }
    }

    public static function allScenarios()
    {
        if (!isset(static::$_allScenarios)) {
            static::$_allScenarios['label'] = 'Товары';

            // Список отношений
            $relationsNames = [
                'self' => 'Свои',
                'dept' => 'Подчиненных',
                'lower' => 'Нижестоящих',
                'boss' => 'Руководителя',
                'upper' => 'Вышестоящих',
                'any' => 'Других',
                'new' => 'Новые',
            ];

            // Список прав доступа
            $accessesNames = [
                'transfer' => 'Перемещать',
                'cancel' => 'Отменять перемещения',
                'return' => 'Возвращать из ТТ',
                'recieve' => 'Получать от',
                'reject' => 'Отклонять',
                'introduce' => 'Оприходовать',
                'withdraw' => 'Списывать',
                'view' => 'Просматривать',
                // Sim-карты
                'changePlan' => 'Менять тарифный план',
                'changeStatus' => 'Менять статус',
            ];

            // Специфичные права доступа для какой-то конкретной категории,
            // ключ является уникальным символьным идентификатором категории
            $specificAccesses = [
                'items_sim' => ['changePlan', 'changeStatus',],
            ];

            // Список прав доступа и отношений для всех категорий товаров
            $accessAndRelations = [
                'transfer' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
                'cancel' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
                'return' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
                'recieve' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
                'reject' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
                'introduce' => ['new',],
                'withdraw' => ['self',],
                'view' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
                // Sim-карты
                'changePlan' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
                'changeStatus' => ['self', 'dept', 'lower', 'boss', 'upper', 'any',],
            ];

            $categories = Categories::find()->all();
            foreach ($categories as $category) {
                /** @var Categories $category */
                foreach ($accessAndRelations as $access => $relations) {
                    $relationValues = [];
                    foreach ($accessAndRelations[$access] as $relation) {
                        $relationValues[] = [
                            'value' => $relation,
                            'label' => "{$relationsNames[$relation]}",
                        ];
                    }
                    static::$_allScenarios['items']["{$category->symbolic_id}_{$access}"] = [
                        'value' => "{$category->symbolic_id}_{$access}",
                        'label' => "{$accessesNames[$access]} ({$category->name})",
                        'items' => $relationValues,
                    ];
                }
                // Спец права для категории
                if (in_array($category->symbolic_id, array_keys($specificAccesses))) {
                    foreach ($specificAccesses[$category->symbolic_id] as $specificAccess) {
                        $relationValues = [];
                        foreach ($accessAndRelations[$specificAccess] as $relation) {
                            $relationValues[] = [
                                'value' => $relation,
                                'label' => "{$relationsNames[$relation]}",
                            ];
                        }
                        static::$_allScenarios['items']["{$category->symbolic_id}_{$specificAccess}"] = [
                            'value' => "{$category->symbolic_id}_{$specificAccess}",
                            'label' => "{$accessesNames[$specificAccess]} ({$category->name})",
                            'items' => $relationValues,
                        ];
                    }
                }
            }
        }
        return static::$_allScenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => '\app\behaviors\Access',
                'relation_name' => [$this, 'getItemRelation'],
                'class_check' => [$this, 'andAccessCheck'],
            ],
            'log' => [
                'class' => Log::className(),
                'logClass' => ItemsLog::className(),
                'timeField' => 'atime',
                'changedAttributesField' => 'changed_attributes',
                'versionField' => 'version',
                'logAttributes' => [
                    'id',
                    'category_id',
                    'owner_id',
                    'atime',
                    'status_id',
                    'model_id',
                    'document',
                    'system_status_id',
                ],
            ],
            'statuses' => [
                'class' => Statuses::className(),
                'statusIdField' => 'status_id',
            ],
        ];
    }

    public function transactions()
    {
        return [
            'default' => static::OP_ALL,
        ];
    }


    public function getSystemStatus()
    {
        return $this->system_status_id;
    }

    public function setSystemStatus($value)
    {
        $this->system_status_id = $value;
    }

    public function getDocTypeSymbolicId()
    {
        return ArrayHelper::getValue(static::$catToDocLinks, $this->category_id);
    }

    public function andAccessCheck($user, $operation)
    {
        if ($this->status_id === null && $this->systemStatus === null) {
            return true;
        }

        $prefix = $this->getItemCatPrefix();

        $operationToSysStatus = [
            "{$prefix}_transfer" => [null],
            "{$prefix}_return" => [null],
            "{$prefix}_cancel" => [Items::$sysStatuses['moves']],
            "{$prefix}_recieve" => [Items::$sysStatuses['moves']],
            "{$prefix}_reject" => [Items::$sysStatuses['moves']],
            "{$prefix}_introduce" => [null],
            "{$prefix}_withdraw" => [null, Items::$sysStatuses['moves'], Items::$sysStatuses['lost']],
            "{$prefix}_changePlan" => [null, Items::$sysStatuses['moves']],
            "{$prefix}_view" => [null, Items::$sysStatuses['moves'], Items::$sysStatuses['lost']],
            "{$prefix}_changeStatus" => [null],
            "{$prefix}_changeStatus" => [null],
            "{$prefix}_return" => [null],
        ];

        $additionalCheck = true;

        if ($operation === "{$prefix}_return") {
            $additionalCheck = isset($this->owner) && $this->owner->type === 'Торговая точка';
        }

        return $additionalCheck
        && array_key_exists($operation, $operationToSysStatus)
        && in_array($this->systemStatus, $operationToSysStatus[$operation]);
    }

    public function getItemRelation()
    {
        if ($this->owner_id) {
            return $this->owner->getUserRelationName();
        } else {
            return 'new';
        }
    }

    /**
     * Данный метод выполняет перехват вызова метода isAccessed поведения Access
     * и подставляет необходимый префикс для проверки прав доступа в конкретной категории
     *
     * @param $operation
     * @param null $relation
     * @param $categoryId
     * @return bool
     */
    public function isAccessed($operation, $relation = null, $categoryId = null)
    {
        $prefix = $this->getItemCatPrefix($categoryId);
        if (isset($prefix)) {
            /** @var Access $access */
            $access = $this->behaviors['access'];
            return $access->isAccessed("{$prefix}_{$operation}", $relation, Items::className());
        }
        return false;
    }

    private function getItemCatPrefix($categoryId = null)
    {
        return ArrayHelper::getValue(static::$catToDocLinks, ($categoryId) ? $categoryId : $this->category_id);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'status_id'], 'required'],
            [['category_id', 'owner_id', 'status_id', 'model_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'owner_id' => 'Owner ID',
            'atime' => 'Atime',
            'status_id' => 'Status ID',
            'model_id' => 'Model ID',
            'document' => 'Document',
        ];
    }

    /**
     * Метод возвращает отношение которое представляет из себя,
     * набор свойств для категории в которой находится текущий товар.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPropsTypes()
    {
        return $this->hasMany(ItemsPropsTypes::className(), ['category_id' => 'category_id'])
            ->groupBy(ItemsPropsTypes::tableName() . '.id')
            ->indexBy('key');
    }

    /**
     * Метод возвращает значение свойства товара, например артикул, название и т.д.
     * В качестве аргумента принимает уникальный ключ свойства из таблицы items_props_types.
     *
     * @param string $key Ключ свойства товара(ItemsPropsTypes->key) значение которого необходимо получить.
     * @return $this|mixed
     */
//    public function getPropertyValueByKey($key)
//    {
//        return ArrayHelper::getValue(
//            ArrayHelper::getValue(
//                ArrayHelper::index(
//                    $this->propertiesValues,
//                    'type_id'
//                ),
//                ItemsPropsTypes::getTypeIdByKey($key)
//            ),
//            'value'
//        );
//    }


    /**
     * Метод возвращает отношение со значением свойств объекта.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPropertiesValues()
    {
        return $this->hasMany(ItemsPropsValues::className(), ['item_id' => 'id'])->indexBy('type_id');
    }

    /**
     * Метод возвращает отношение к свойству объекта.
     *
     * @param int $type_id Идентификатор типа свойства.
     * @return \yii\db\ActiveQuery
     */
    public function getPropertyValue($type_id)
    {
        return $this->hasOne(ItemsPropsValues::className(), ['item_id' => 'id', 'type_id' => $type_id]);
    }

    /**
     * Метод возвращает отношение связанной категории товара.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Categories::className(), ['id' => 'category_id']);
    }

    /**
     * Метод возвращает отношение связанного владельца товара.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(Units::className(), ['id' => 'owner_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItemLog()
    {
        return $this->hasMany(ItemsLog::className(), ['doc_id' => 'id'])
            ->orderBy(['atime' => SORT_ASC]);
    }

    public static function accessList($articlesList, $operation)
    {
        return array_reduce($articlesList, function ($carry, $item) use ($operation) {
            /** @var Items $item */
            return $carry && $item->isAccessed($operation);
        }, true);
    }

    /////////////////////////////////////////////
    //        Методы для категории Sims        //
    /////////////////////////////////////////////

    /**
     * Метод возвращает отношение связанного объекта плана.
     * Отличие от simPlanRelation состоит в том, что данный метод не порождает новый запрос к ItemsPropsValues
     * вызванный использованием метода viaTable().
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSimPlan()
    {
        /** @var ItemsPropsValues $planIdProp */
        $planIdProp = ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['plan_id']->id);
        return !empty($planIdProp) && !empty($planIdProp->value) ? Plans::find()->where(['id' => $planIdProp->value]) : null;
    }

    public function getSimPlanId()
    {
        if (isset($this->_updateProps['plan_id'])) {
            return $this->_updateProps['plan_id']->value;
        }
        $planIdProp = ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['plan_id']->id);
        return isset($planIdProp) ? $planIdProp->value : null;
    }

    /**
     * Метод возвращает отношение связанного объекта плана.
     * является неким костылем для использования в конструкциях with()
     * @return \yii\db\ActiveQuery
     */
    public function getSimPlanRelation()
    {
        return $this->hasOne(Plans::className(), ['id' => 'value'])
            ->viaTable(ItemsPropsValues::tableName(), ['item_id' => 'id'], function ($query) {
                /** @var ActiveQuery $query */
                $query->where(['type_id' => static::$allPropsTypes['plan_id']->id]);
            });
    }

//    public function getSimPlanRelation()
//    {
//        return $this->hasOne(Plans::className(), ['id' => 'value'])
//            ->via('propertiesValues', function ($query) {
//                /** @var ActiveQuery $query */
//                $query->where(['type_id' => static::$allPropsTypes['plan_id']->id]);
//            });
//    }

    public function getSimSn()
    {
        if (isset($this->_updateProps['sn'])) {
            return $this->_updateProps['sn']->value;
        }
        $simSnProp = ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['sn']->id);
        return isset($simSnProp) ? $simSnProp->value : null;
    }

    public function getSimAn()
    {
        if (isset($this->_updateProps['an'])) {
            return $this->_updateProps['an']->value;
        }
        $simAnProp = ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['an']->id);
        return isset($simAnProp) ? $simAnProp->value : null;
    }

    public function getSimIsRfa()
    {
        return empty($this->simSelldate) ? false : true;
    }

    public function getSimTradePointName()
    {
        return ArrayHelper::getValue(
            ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['trade_point']->id),
            'value'
        );
    }

    /**
     * Возвращает номер симкарты, по умолчанию это серийный номер, иначе абонентский,
     * так же учитывает, что сим-карта могла быть создана в методе findSimsArticlesByString,
     * в таком случае номер берется из свойства _updateProps
     *
     * @return string|null
     */
    public function getSimNumber()
    {
        if (isset($this->_updateProps['sn'])) {
            return $this->_updateProps['sn']->value;
        }
        if (isset($this->_updateProps['sn'])) {
            return $this->_updateProps['sn']->value;
        }
        if (isset($this->simSn)) {
            return $this->simSn;
        } else {
            return $this->simAn;
        }
    }

    public function getSimSelldate()
    {
        $act = $this->simDateActivation;
        $reg = $this->simDateRegistration;
        if (isset($reg) && isset($act)) {
            return strtotime($reg) < strtotime($act) ? $reg : $act;
        } elseif (isset($reg) || isset($act)) {
            return isset($reg) ? $reg : $act;
        }
        return null;
    }

    public function getSimDateActivation()
    {
        $activationDate = ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['date_activation']->id);
        if (isset($activationDate) && isset($activationDate->value)) {
            return $activationDate->value;
            //return \DateTime::createFromFormat(MyDateTime::fromDb(), $activationDate->value)->format('d.m.Y');
        } else {
            return null;
        }
    }

    public function getSimDateRegistration()
    {
        $registrationDate = ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['date_registration']->id);
        if (isset($registrationDate) && isset($registrationDate->value)) {
            return $registrationDate->value;
            //return \DateTime::createFromFormat(MyDateTime::fromDb(), $registrationDate->value)->format('d.m.Y');
        } else {
            return null;
        }
    }

    public function getSimRewardType()
    {
        return ArrayHelper::getValue(ArrayHelper::getValue($this->propertiesValues, static::$allPropsTypes['reward_type']->id), 'value');
    }

    public function getSimDoc()
    {
        return $this->hasOne(Invoices::className(), ['id' => 'document']);
    }

    /**
     * Возвращает связь на оператора сим карты.
     * @return ActiveQuery
     */
    public function getSimOperator()
    {
        return ArrayHelper::getValue($this->simPlan, 'operator');
    }

    /**
     * Метод возвращает кастомизированные свойства сим-карты в виде массива.
     *
     * @return array
     */
    public function getSimPropsArray()
    {
        $allPropsArray = [
            array(
                'propName' => 'Категория',
                'propValue' => Html::a(
                    $this->category->name,
                    ['categories/view', 'id' => $this->category->id]
                ),
            ),
            [
                'propName' => 'Владелец',
                'propValue' => Html::a(
                    $this->owner->shortname,
                    [$this->owner->viewRef . '/view', 'id' => $this->owner->id]
                ),
            ],
            [
                'propName' => 'Статус',
                'propValue' => $this->status->name,
            ],

        ];
        foreach ($this->propertiesValues as $prop) {
            $propName = $prop->propertyValueType->description;
            $propValue = null;
            // Кастомизация отображения некоторых свойств
            switch ($prop->type_id) {
                case Items::$allPropsTypes['plan_id']->id:
                    $propValue = $this->simPlan->planName;
                    break;
                case Items::$allPropsTypes['reward_type']->id:
                    $propValue = $this->simRewardType;
                    break;
                default:
                    $propValue = $prop->value;
            }
            $allPropsArray[] = [
                'propName' => $propName,
                'propValue' => $propValue,
            ];
        }
        return $allPropsArray;
    }

    /**
     * Метод поиска сим-карт, в качестве параметра передается строка,
     * в которой содержится список идентификаторов
     * (серийный номер с/без контрольной цифры, абонентский номер с/без контрольной цифры)
     *
     * @param $list_string
     * @return Items[]
     * @throws ErrorException
     */
    public static function findSimsArticlesByString($list_string)
    {
        // Парсинг строки которая пришла от пользователя
        preg_match_all('/'
            . '\b'
            . '(?:(?<snumc>\d{16,20})(?:[ -]\d)|(?<snum>\d{16,20})|(?<anum>\d{8,11}))'     // Main number
            . '(?|\s*\((?<amount>\d+)\)|\s*\+\s*(?<amount>\d+)\b|)'                        // amount by either + or ()
            . '(?:\s*\:\s*(?<snumc1>\d{16,20})(?:[ -]\d)\b|\s*\:\s*(?<snum1>\d{16,20})\b|\s*\:\s*(?<anum1>\d{8,11})\b|)' // Second number of a range by :
            . '/', $list_string, $matches, PREG_SET_ORDER);

        // Создание массивов для поиска
        $num = [];     // Серийный номер с контрольной цифрой
        $numc = [];    // Серийный номер без контрольной цифры
        $anum = [];    // Абонентский номер без 7
        $anum7 = [];   // Абонентский номер с 7

        foreach ($matches as $k => $v) {
            if (@$v['snum'] && @$v['snumc1']) {
                for ($sn = $v['snum']; strcmp($sn, $v['snumc1']) <= 0; $sn = bcadd($sn, 1, 0)) {
                    $num[] = $sn;
                    $numc[] = null;
                    $anum[] = null;
                    $anum7[] = null;
                }
            } elseif (@$v['snumc'] && @$v['snum1']) {
                for ($sn = $v['snumc']; strcmp($sn, $v['snum1']) <= 0; $sn = bcadd($sn, 1, 0)) {
                    $num[] = $sn;
                    $numc[] = null;
                    $anum[] = null;
                    $anum7[] = null;
                }
            } elseif (@$v['snumc'] && @$v['snumc1']) {
                for ($sn = $v['snumc']; strcmp($sn, $v['snumc1']) <= 0; $sn = bcadd($sn, 1, 0)) {
                    $num[] = $sn;
                    $numc[] = null;
                    $anum[] = null;
                    $anum7[] = null;
                }
            } elseif (@$v['snum'] && @$v['snum1']) {
                for ($sn = $v['snum']; strcmp($sn, $v['snum1']) <= 0; $sn = bcadd($sn, 1, 0)) {
                    $num[] = $sn;
                    $numc[] = substr($sn, 0, -1);
                    $anum[] = null;
                    $anum7[] = null;
                }
            } elseif (@$v['anum'] && @$v['anum1']) {
                for ($an = $v['anum']; strcmp($an, $v['anum1']) <= 0; $an = bcadd($an, 1, 0)) {
                    $num[] = null;
                    $numc[] = null;
                    $anum[] = $an;
                    $anum7[] = '7' . $an;
                }
            } elseif (@$v['snum'] && @$v['amount']) {
                for ($i = 0; $i < $v['amount']; ++$i) {
                    $sn = bcadd($v['snum'], $i, 0);
                    $num[] = $sn;
                    $numc[] = substr($sn, 0, -1);
                    $anum[] = null;
                    $anum7[] = null;
                }
            } elseif (@$v['snumc'] && @$v['amount']) {
                for ($i = 0; $i < $v['amount']; ++$i) {
                    $sn = bcadd($v['snumc'], $i, 0);
                    $num[] = $sn;
                    $numc[] = null;
                    $anum[] = null;
                    $anum7[] = null;
                }
            } elseif (@$v['anum'] && @$v['amount']) {
                for ($i = 0; $i < $v['amount']; ++$i) {
                    $an = bcadd($v['anum'], $i, 0);
                    $num[] = null;
                    $numc[] = null;
                    $anum[] = $an;
                    $anum7[] = '7' . $an;
                }
            } elseif (@$v['snum']) {
                $num[] = $v['snum'];
                $numc[] = substr($v['snum'], 0, -1);
                $anum[] = null;
                $anum7[] = null;
            } elseif (@$v['snumc']) {
                $num[] = $v['snumc'];
                $numc[] = null;
                $anum[] = null;
                $anum7[] = null;
            } elseif (@$v['anum']) {
                $num[] = null;
                $numc[] = null;
                $anum[] = $v['anum'];
                $anum7[] = '7' . $v['anum'];
            }
        }

        if (count($num) <= 0) {
            return [];
        }

        // Базовый запрос на поиск записей.
        $q = static::find()
            ->select(
                [
                    'items.*',
                ]
            )
            ->joinWith(
                [
                    'propertiesValues AS snPropValue' => function ($query) {
                        /** @var ActiveQuery $query */
                        return $query->andOnCondition(['snPropValue.type_id' => static::$allPropsTypes['sn']->id]);
                    }
                ]
            )
            ->joinWith(
                [
                    'propertiesValues AS anPropValue' => function ($query) {
                        /** @var ActiveQuery $query */
                        return $query->andOnCondition(['anPropValue.type_id' => static::$allPropsTypes['an']->id]);
                    }
                ]
            )->joinWith(
                ['propertiesValues AS propertiesValues']
            )
            ->where(['category_id' => static::SIMS_ITEMS_CATEGORY_ID])
            ->andWhere(['not', ['COALESCE([[system_status_id]], 0)' => static::$sysStatuses['withdrawn']]])
            ->groupBy('{{items}}.[[id]]');
        // Очищаем массивы от пустых значений и уникализуем их.
        $arr_num = array_unique(array_filter($num));
        $arr_numc = array_unique(array_filter($numc));
        $arr_anum = array_unique(array_filter($anum));
        $arr_anum7 = array_unique(array_filter($anum7));

        $q->andFilterWhere(
            [
                'or', ['or', ['snPropValue.value' => $arr_num + $arr_numc]], ['or', ['anPropValue.value' => $arr_anum + $arr_anum7]]
            ]
        );

        $result = [];
        $to_remove = [];
        foreach ($q->each() as $found_sim) {
            /** @var Items $found_sim */
            if (($idx = array_search(trim($found_sim->simSn), $num)) !== false
                || ($idx = array_search(trim($found_sim->simSn), $numc)) !== false
                || ($idx = array_search(trim($found_sim->simAn), $anum7)) !== false
                || ($idx = array_search(trim($found_sim->simAn), $anum)) !== false
            ) {
                $result[] = $found_sim;
                if ($numc[$idx]) {
                    $to_remove[] = $numc[$idx];
                }
                unset($num[$idx], $numc[$idx], $anum[$idx], $anum7[$idx]);
            } else {
                throw new ErrorException('Phone found, but by unknown property. SN: ' . $found_sim->simSn . ' AN: ' . $found_sim->simAn . '.');
            }
        }

        foreach ($numc as $k => $v) {
            if (!array_key_exists($v, $to_remove)) {
                if ($num[$k]) {
                    $item = new static([
                        'category_id' => static::SIMS_ITEMS_CATEGORY_ID,
                    ]);
                    $item->setProperty('sn', $num[$k]);
                    $result[$num[$k]] = $item;
                } elseif ($anum[$k]) {
                    $item = new static([
                        'category_id' => static::SIMS_ITEMS_CATEGORY_ID,
                    ]);
                    $item->setProperty('sn', $num[$k]);
                    $result[$anum[$k]] = $item;
                }
            }
            unset($num[$k]);
            unset($numc[$k]);
            unset($anum[$k]);
            unset($anum7[$k]);
        }
        return array_values($result);
    }

    /**
     * Фолдинг сим-карт, т.е. разбитие на диапазоны,
     * где в каждом диапазоне у сим-карт одинаковые значения всех свойств.
     *
     * @param Items[] $list
     * @return array
     */
    public static function foldSimsList($list)
    {
        $folds = [];

        if (!is_array($list) || count($list) <= 0) {
            return [];
        }

        /** @var Items $s */
        /** @var Items $e */
        $s = null;    // Starting Number of current interval
        $e = null;    // End Number of current interval

        usort($list, function ($a, $b) {
            if (is_object($a) && is_object($b)) {
                /** @var Items $a */
                /** @var Items $b */
                return bccomp($a->simNumber, $b->simNumber);
            }
            return false;
        });

        $amount = 0;
        $inner_list = [];
        foreach ($list as $sim) {
            if ($e &&
                bccomp($sim->simNumber, bcadd($e->simNumber, 1, 0)) === 0 &&
                ArrayHelper::getValue($sim->simPlan, 'id') == ArrayHelper::getValue($e->simPlan, 'id') &&
                $sim->owner_id == $e->owner_id &&
                $sim->status_id === $e->status_id
            ) {
                // if inside the range we just shift e to the current position
                $e = $sim;
                $inner_list[] = $sim;
            } else {
                // this is the edge of the range
                if ($s && $e) {
                    $folds[] = ['s' => $s, 'e' => $e, 'a' => $amount, 'il' => $inner_list];
                    $amount = 0;
                }
                $inner_list = [$sim];
                $s = $sim;
                $e = $sim;
            }
            $amount++;
        }
        if ($s && $e) {
            $folds[] = ['s' => $s, 'e' => $e, 'a' => $amount, 'il' => $inner_list];
        }

        return $folds;
    }

    /**
     * Метод для поиска сим-карт в базе данных, ищет по серийным и абонентским номерам,
     * принимает в качестве параметра массив объектов сим-карт.
     *
     * @param Items[] $listArray Массив сим-карт
     * @return ActiveQuery
     */
    public static function findSimsArticleList($listArray)
    {
        $sns = array_filter(ArrayHelper::getColumn($listArray, 'simSn'));
        $ans = array_filter(ArrayHelper::getColumn($listArray, 'simAn'));
        return static::find()
            ->select(
                [
                    'items.*',
                ]
            )
            ->joinWith(
                [
                    'propertiesValues AS snPropValue' => function ($query) {
                        /** @var ActiveQuery $query */
                        return $query->andOnCondition(['snPropValue.type_id' => static::$allPropsTypes['sn']->id]);
                    }
                ]
            )
            ->joinWith(
                [
                    'propertiesValues AS anPropValue' => function ($query) {
                        /** @var ActiveQuery $query */
                        return $query->andOnCondition(['anPropValue.type_id' => static::$allPropsTypes['an']->id]);
                    }
                ]
            )->joinWith(
                ['propertiesValues AS propertiesValues']
            )
            ->where(['category_id' => static::SIMS_ITEMS_CATEGORY_ID])
            ->andWhere(['not', ['COALESCE([[system_status_id]], 0)' => static::$sysStatuses['withdrawn']]])
            ->andFilterWhere(
                [
                    'or', ['or', ['snPropValue.value' => $sns]], ['or', ['anPropValue.value' => $ans]]
                ]
            )
            ->groupBy('{{items}}.[[id]]');
    }

    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////
    public function afterSave($insert, $changedAttributes)
    {
        if (count($this->_updateProps) > 0) {
            foreach ($this->_updateProps as $prop) {
                $prop->item_id = $this->id;
            }
            $res = ItemsPropsValues::saveMultiple($this->_updateProps);
            if (!$res) {
                throw new ErrorException('Error occurred on save Item properties.');
            }
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     *
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSaveMultiple($insert, $changedAttributes)
    {
        foreach ($this->_updateProps as $prop) {
            /** @var ItemsPropsValues $prop */
            $prop->item_id = $this->id;
            ItemsPropsValues::addSaveMultiple($prop);
        }
        parent::afterSaveMultiple($insert, $changedAttributes);
    }

    /**
     * Сохранение связанных моделей в базу данных
     */
    public static function savedMultiple()
    {
        $res = ItemsPropsValues::saveMultiple();
        if (!$res) {
            throw new ErrorException('Error occurred on save Item properties.');
        }
        parent::savedMultiple();
    }
}
