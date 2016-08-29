<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 05.08.16
 * Time: 13:02
 */

namespace docflow\models\base\operations;

use docflow\base\JsonB;
use docflow\behaviors\LinkSimpleBehavior;
use docflow\behaviors\LinkStructuredBehavior;
use docflow\behaviors\LogMultiple;
use docflow\behaviors\StatusBehavior;
use docflow\helpers\PostgresArrayTypeHelper;
use docflow\models\base\doc_type\DocTypes;
use docflow\models\base\Document;
use docflow\models\base\OperationBase;
use docflow\models\base\operations\flTree\links\OperationsLinksFlTreeNope;
use docflow\models\base\operations\flTree\links\OperationsLinksSimpleNope;
use docflow\models\statuses\Statuses;
use yii;
use yii\base\ErrorException;
use yii\base\ExitException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Class Operations
 *
 * @property integer $id
 * @property string  $operation_type
 * @property integer $status_id
 * @property integer $unit_real_id
 * @property integer $unit_resp_id
 * @property JsonB   $field
 * @property string  $comment
 *
 * @package Docflow\Models
 *
 * @mixin StatusBehavior
 * @mixin LinkStructuredBehavior
 *
 * TODO метод проверки что документ уже работает над подобной операцией
 * TODO метод удаления операций из документа
 */
abstract class Operations extends OperationBase
{
    /**
     * Статус "черновик"
     */
    const STATUS_DRAFT = 'draft';

    /**
     * Статус "создана"
     */
    const STATUS_CREATED = 'created';

    /**
     * Статус "выполняется"
     */
    const STATUS_PROCESSING = 'processing';

    /**
     * Статус завершено
     */
    const STATUS_FINISHED = 'finished';

    /**
     * Статус отменено
     */
    const STATUS_CANCELED = 'canceled';

    /**
     * Статус "провалено"
     */
    const STATUS_FAILED = 'failed';

    /**
     * Статус "в ожидании"
     */
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Лимит количества документов, над которыми можно провести операцию
     */
    const DOCUMENT_LIMIT = 50;

    /**
     * Объект статуса "finished"
     * Статика нужна для того, чтобы было доступно во всех наследниках
     *
     * @var Statuses
     */
    protected static $finishStatus;

    /**
     * Хранилище документов
     *
     * @var array
     */
    protected $documents = [];

    /**
     * Массив подопераций, содержащий имена классов подопераций
     *
     * @var array
     */
    public static $subOperations = [];

    /**
     * Тип операции
     *
     * @var string
     */
    public $operationType;

    /**
     * Содержит именна классов операций
     * ключ - operationType
     * значение - имя класса и пространством имени
     *
     * @var array
     */
    public static $allOperations = [];

    /**
     * Имя таблицы
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%operations}}';
    }

    /**
     * Return description of the type of current document
     *
     * @return DocTypes the object specifying the document type
     */
    public function getDoc()
    {
        return DocTypes::getDocType(static::docTag());
    }

    /**
     * {inheritdoc}
     *
     * @return array
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'log' => [
                    'class' => LogMultiple::className(),
                    'logAttributes' => [
                        'id',
                        'operation_type',
                        'status_id',
                        'unit_real_id',
                        'unit_resp_id',
                        'field',
                        'comment',
                        'atime'
                    ],
                    'timeField' => 'atime',
                    'logClass' => OperationsLog::className(),
                    'changedAttributesField' => 'changed_attributes',
                    'versionField' => 'version',
                ],
                'status' => [
                    'class' => StatusBehavior::className(),
                    'statusIdField' => 'status_id',
                    'statusRootTag' => 'operations'
                ],
                'simple' => [
                    'class' => LinkSimpleBehavior::className(),
                    'linkClass' => OperationsLinksSimpleNope::className(),
                    'documentQuery' => function (ActiveQuery $query) {
                        /* True - конечный результат будет All(); null, false - one() */
                        $query->multiple = true;

                        return $query;
                    },
                    'indexBy' => 'id'
                ],
                'structure' => [
                    'class' => LinkStructuredBehavior::className(),
                    'linkClass' => OperationsLinksFlTreeNope::className(),
                    'documentQuery' => function (ActiveQuery $query) {
                        /* True - конечный результат будет All(); null, false - one() */
                        $query->multiple = true;

                        return $query;
                    },
                    'indexBy' => 'id'
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['version'], 'safe']
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'version' => 'Версия'
            ]
        );
    }

    /**
     * This function returns the document tag. This tag is used to get
     * all information about the document type from the database.
     *
     * @return string Document tag
     */
    static public function docTag()
    {
        return 'operations';
    }

    /**
     * Return field name which use how Document `name`
     *
     * @return string Document name
     */
    public function getDocName()
    {
        return '';
    }

    /**
     * Получаем документы
     *
     * @return array
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Получаем значение скрытого свойства
     *
     * @return \docflow\models\statuses\Statuses|null
     */
    public function getFinishStatus()
    {
        if (self::$finishStatus === null) {
            self::$finishStatus = Statuses::find()->where(['tag' => 'finished'])->one();
        }

        return self::$finishStatus;
    }

    /**
     * Получаем документ по его идентификатору
     *
     * @param integer $nodeId - id документа
     *
     * @return mixed
     */
    public static function getDocumentByNodeId($nodeId)
    {
        return static::find()
            ->where(['id' => $nodeId])
            ->one();
    }

    /**
     * Имя операции
     *
     * @return mixed
     */
    abstract public function getOperationName();

    /**
     * Проверяем возможно выполнить операцию или нет.
     * Пример содержания:
     *      return Docflow::getInstance()->isAccessed($operation);
     *
     * @param string $operation - наименование операции
     *
     * @return mixed
     */
    /* abstract public function operationPermitted($operation); */

    /**
     * Добавляем item-ы
     *
     * @param array $items - итемы
     *
     * @return void
     *
     * @throws ErrorException Вызывается если при добавлении документов будет превышен лимит на максимальное количество документов
     */
    public function addDocuments(array $items)
    {
        if ((count($this->documents) + count($items)) > static::DOCUMENT_LIMIT) {
            throw new ErrorException('Превышен лимит максимального количества одновременно обрабатываемых документов');
        }

        $this->documents = array_merge($this->documents, $items);
    }

    /**
     * Перед массовым сохранением
     *
     * @param bool $insert - true - добавляем, false - обновляем записи
     *
     * @return bool
     *
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\ErrorException
     */
    public function beforeSaveMultiple($insert)
    {
        $this->unit_real_id = $this->getUnitRealId();
        $this->unit_resp_id = $this->getUnitRespId();

        if (!empty($this->operationType)) {
            $this->operation_type = $this->operationType;
        }

        $this->beforeSave($insert);

        return parent::beforeSaveMultiple($insert);
    }

    /**
     * Перед сохранением
     *
     * @param bool $insert - true - добавляем, false - обновляем записи
     *
     * @return bool
     *
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\ErrorException
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->unit_real_id = $this->getUnitRealId();
        $this->unit_resp_id = $this->getUnitRespId();
        $this->operation_type = $this->operationType;

        if (!empty($this->operationType)) {
            $this->operation_type = $this->operationType;
        }

        return true;
    }


    /**
     * Перед валидацией
     *
     * @return bool
     */
    public function beforeValidate()
    {
        $this->unit_real_id = $this->getUnitRealId();
        $this->unit_resp_id = $this->getUnitRespId();

        if (!empty($this->operationType)) {
            $this->operation_type = $this->operationType;
        }

        return parent::beforeValidate();
    }

    /**
     * Получаем id реального пользователя
     *
     * @return int|string
     */
    protected function getUnitRealId()
    {
        return Yii::$app->user->id;
    }

    /**
     * Получаем id пользователя, от лица которого действуем
     *
     * @return int|null|string
     */
    protected function getUnitRespId()
    {
        $unitRespId = null;

        if (Yii::$app->user->identity->hasProperty('respId')) {
            $unitRespId = Yii::$app->user->identity->respId;
        } else {
            $unitRespId = Yii::$app->user->id;
        }

        return $unitRespId;
    }

    /**
     * Метод для смены статуса
     *
     * @param Statuses $statusObj - объект статутса
     * @param bool     $safe      - устанавливаем статус безопасно,
     *                            false - проверяем на возможность установки статуса,
     *                            true - устанавливаем без проверок
     *
     * @return bool
     */
    protected function changeStatus(Statuses $statusObj, $safe = false)
    {
        /* Устанавливаем новый статус */
        try {
            if ($safe === false) {
                $this->setStatus($statusObj);
            } else {
                $this->setStatusSafe($statusObj);
            }

            $isChanged = true;
        } catch (ErrorException $e) {
            $isChanged = false;
        }

        return $isChanged;
    }

    /**
     * Устанавливаем стаус операции - черновик
     *
     * @param string $statusTag - тэг статуса
     * @param bool   $safe      - устанавливаем статус безопасно,
     *                          false - проверяем на возможность установки статуса,
     *                          true - устанавливаем без проверок
     *
     * @return bool
     */
    public function setStatuses($statusTag, $safe = false)
    {
        $status = $this->getStatusObj($statusTag);

        $isSet = false;

        if ($status instanceof Statuses) {
            $isSet = $this->changeStatus($status, $safe);
        }

        return $isSet;
    }

    /**
     * Получаем объект статуса
     *
     * @param string $statusTag - тэг статуса
     *
     * @return null|Statuses
     */
    protected function getStatusObj($statusTag)
    {
        return Statuses::find()
            ->where(['tag' => $statusTag])
            ->one();
    }


    /**
     * Создаем черновик
     *
     * @param bool $safe - устанавливаем статус безопасно,
     *                   false - проверяем на возможность установки статуса,
     *                   true - устанавливаем без проверок
     *
     * @return bool
     */
    public function draft($safe = false)
    {
        $this->setStatuses(static::STATUS_DRAFT, $safe);

        return $this->save();
    }

    /**
     * Присваиваем статус операции "создана"
     *
     * @param bool $safe - устанавливаем статус безопасно,
     *                   false - проверяем на возможность установки статуса,
     *                   true - устанавливаем без проверок
     *
     * @return bool
     */
    public function created($safe = false)
    {
        $this->setStatuses(static::STATUS_CREATED, $safe);

        return $this->save();
    }

    /**
     * Присваиваем статус операции "выполняется"
     *
     * @param bool $safe - устанавливаем статус безопасно,
     *                   false - проверяем на возможность установки статуса,
     *                   true - устанавливаем без проверок
     *
     * @return bool
     */
    public function processing($safe = false)
    {
        $this->setStatuses(static::STATUS_PROCESSING, $safe);

        return $this->save();
    }

    /**
     * Присваиваем статус операции "завершена"
     *
     * @param bool $safe - устанавливаем статус безопасно,
     *                   false - проверяем на возможность установки статуса,
     *                   true - устанавливаем без проверок
     *
     * @return bool
     */
    public function finish($safe = false)
    {
        $this->setStatuses(static::STATUS_FINISHED, $safe);

        return $this->save();
    }

    /**
     * Присваиваем статус операции "отменена"
     *
     * @param bool $safe - устанавливаем статус безопасно,
     *                   false - проверяем на возможность установки статуса,
     *                   true - устанавливаем без проверок
     *
     * @return bool
     */
    public function canceled($safe = false)
    {
        $this->setStatuses(static::STATUS_CANCELED, $safe);

        return $this->save();
    }

    /**
     * Присваиваем статус операции "провалена"
     *
     * @param bool $safe - устанавливаем статус безопасно,
     *                   false - проверяем на возможность установки статуса,
     *                   true - устанавливаем без проверок
     *
     * @return bool
     */
    public function failed($safe = false)
    {
        $this->setStatuses(static::STATUS_FAILED, $safe);

        return $this->save();
    }

    /**
     * Присваиваем статус операции "в простое"
     *
     * @param bool $safe - устанавливаем статус безопасно,
     *                   false - проверяем на возможность установки статуса,
     *                   true - устанавливаем без проверок
     *
     * @return bool
     */
    public function suspended($safe = false)
    {
        $this->setStatuses(static::STATUS_SUSPENDED, $safe);

        return $this->save();
    }

    /**
     * Создаем структуру
     *
     * @param null|Operations $parentOperation - родительская операция
     *
     * @return array
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\ErrorException
     */
    public static function createStructure(Operations $parentOperation = null)
    {
        $operations = [];

        /* Создаем операцию */
        $operation = static::createOperation(static::STATUS_CREATED);

        if ($operation === null) {
            throw new ErrorException('Операция ' . static::className() . ' не создалась');
        }

        /* Массив с операциями */
        $operations[$operation['operation_type']] = $operation;

        /* Создаем связь */
        if ($parentOperation !== null) {
            $parentOperation->setChild($operation);
        }

        /* @var Operations $operation */
        if (count(static::$subOperations) > 0) {
            foreach (static::$subOperations as $operationChild) {
                /* @noinspection SlowArrayOperationsInLoopInspection */
                $operations = array_merge($operations, $operationChild::createStructure($operation));
            }
        }

        return $operations;
    }

    /**
     * Создаем операцию (запись в базе)
     *
     * @param bool $status - статус, с которым будет создана операция
     *
     * @return null|Operations
     */
    public static function createOperation($status)
    {
        $operation = new static;

        /* Устанавливаем статус */
        $operation->setStatuses($status, true);

        /* Сохраняем в БД */
        $isCreated = $operation->save();

        $return = null;

        if ($isCreated === true) {
            $return = $operation;
        }

        return $return;
    }

    /**
     * Запускаем выполнение операции
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->documents as $document) {
            $this->work($document);
        }
    }

    /**
     * Работа с документом и статусом операции
     *
     * @param object $document - документ
     *
     * @return mixed
     */
    abstract protected function work($document);

    /**
     * Метод говорит родительскому статсу что операция закончена.
     * Родительская операция начинает проверять состояние дел у других подчиненных операций
     * и если все подчиненные операции завершены ставит себе статус "завершено" и говорит своему родителю и все повторяется
     *
     * @return void
     *
     * @throws \yii\base\ExitException
     */
    public function sayParentOperationThatIFinish()
    {
        try {
            /* @var Operations $parent */
            $parent = $this->getStatusParent()->one();

            /* Выходим из исполнения, т.к текущая операция является "высшей" т.е не имеет родителя */
            if ($parent === null) {
                throw new ExitException();
            }

            /* @var Statuses $finishStatusId */
            $finishStatus = $this->finishStatus;

            /* @var Operations[] $childes */
            $childes = $parent->getStatusChildren()->all();
            $childesStatusesId = ArrayHelper::getColumn($childes, 'status_id');

            $isChildesFinished = $this->checkWhatAllValuesIsFinished($childesStatusesId, $finishStatus->id);

            if ($isChildesFinished) {
                $parent->finish();
                $parent->sayParentOperationThatIFinish();
            }
        } catch (ExitException $e) {
            /* Исключение ради выхода из исполнения */
        }
    }

    /**
     * Проверяем все-ли подчиненные операции имеют статус finished
     *
     * @param integer[] $subOperationsId - массив, содержащий id статусов подопераций
     * @param integer   $finishedId      - id статуса finished
     *
     * @return bool
     */
    protected function checkWhatAllValuesIsFinished($subOperationsId, $finishedId)
    {
        $return = true;

        foreach ($subOperationsId as $subOperationId) {
            if ((int)$subOperationId !== (int)$finishedId) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Получаем класс, в зависимости operation_type строки
     *
     * @param array $row - строка из таблицы
     *
     * @return mixed
     */
    public static function instantiate($row)
    {
        $class = static::className();

        if (array_key_exists($row['operation_type'], static::$allOperations)) {
            $class = static::$allOperations[$row['operation_type']];
        }

        return new $class($row);
    }
}
