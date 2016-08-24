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
use docflow\models\base\doc_type\DocTypes;
use docflow\models\base\OperationBase;
use docflow\models\base\operations\flTree\links\OperationsLinksFlTreeNope;
use docflow\models\base\operations\flTree\links\OperationsLinksSimpleNope;
use docflow\models\statuses\Statuses;
use yii;
use yii\base\ErrorException;
use yii\db\ActiveQuery;

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
 */
abstract class Operations extends OperationBase
{
    /**
     * Хранилище документов
     *
     * @var array
     */
    protected $documents = [];

    /**
     * Массив подопераций
     *
     * @var array
     */
    public static $subOperations = [];

    /**
     * Список операций, где ключ - имя операции, значение - имя класса выполняющего операцию
     *
     * @var array
     */
    public static $operationsList = [];

    /**
     * Тип операции
     *
     * @var string
     */
    public $operationType;

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
     * Получаем объект в зависимости от входящих аттрибутов
     * TODO не понимаю, зачем нужно в такой форме ? чтобы при вызове в activerecord сразу создавать необходимый класс
     *
     * @param array $operation - массив с данными по операции
     *
     * @return operations
     */
    public static function instantiate($operation)
    {
        $class = static::className();
        if (array_key_exists($operation['operation_type'], static::$operationsList)) {
            $class = static::$operationsList[$operation['operation_type']];
        }

        return new $class();
    }

    /**
     * Получаем номер документа
     *
     * @return mixed
     */
    public function getDocNumber()
    {
        return $this->id;
    }

    /**
     * Устанавуливаем номер документа
     *
     * @param integer $num - номер документа
     *
     * @return void
     */
    public function setDocNumber($num)
    {
        $this->id = $num;
    }

    /**
     * Добавляем item-ы
     *
     * @param array $items - итемы
     *
     * @return void
     *
     * @throws ErrorException Вызывается если массовое добавление в БД не произошло (ошибка)
     */
    public function addDocuments(array $items)
    {
        $this->documents = array_merge($this->documents, $items);

        if (count($this->documents) > 100) {
            $batch = $this->batchInsertItems($this->documents);

            if ($batch === false) {
                throw new ErrorException('Массовое добавление Items в БД не удалось');
            }

            $this->documents = [];
        }
    }

    /**
     * Массово добавляем итемы в таблицу БД
     *
     * @param array $items - итемы
     *
     * @return mixed
     */
    protected function batchInsertItems(array $items)
    {
        $columns = $this->getAttributes();
        unset($columns['id'], $columns['atime'], $columns['version']);

        /* TODO подумать над тем, как правильно указывать подключения в модулях, в данном случае подключение по компоненту db */

        return \Yii::$app->db
            ->createCommand()
            ->batchInsert(
                static::tableName(),
                $columns,
                $items
            );
    }

    /**
     * Удаляем item-ы
     *
     * @return void
     */
    public function deleteItems()
    {
        $this->documents = [];
        /* TODO Косяк, надо продумать */
        static::deleteAll(['invoice_id' => $this->id]);
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
     *
     * @return bool
     */
    protected function changeStatus(Statuses $statusObj)
    {
        /* Устанавливаем новый статус */
        try {
            $this->setStatus($statusObj);
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
     *
     * @return bool
     */
    public function setStatuses($statusTag)
    {
        $status = $this->getStatusObj($statusTag);

        $isSet = false;

        if ($status instanceof Statuses) {
            $isSet = $this->changeStatus($status);
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
     * @return bool
     */
    public function draft()
    {
        $draftStatus = $this->getStatusObj('draft');
        $this->status_id = $draftStatus->id;

        return $this->save();
    }

    /**
     * Присваиваем статус операции "создана"
     *
     * @return bool
     */
    public function created()
    {
        $this->setStatuses('created');

        return $this->save();
    }

    /**
     * Присваиваем статус операции "выполняется"
     *
     * @return bool
     */
    public function processing()
    {
        $this->setStatuses('processing');

        return $this->save();
    }

    /**
     * Присваиваем статус операции "завершена"
     *
     * @return bool
     */
    public function finish()
    {
        $this->setStatuses('finished');

        return $this->save();
    }

    /**
     * Присваиваем статус операции "отменена"
     *
     * @return bool
     */
    public function canceled()
    {
        $this->setStatuses('canceled');

        return $this->save();
    }

    /**
     * Присваиваем статус операции "провалена"
     *
     * @return bool
     */
    public function failed()
    {
        $this->setStatuses('failed');

        return $this->save();
    }

    /**
     * Присваиваем статус операции "в простое"
     *
     * @return bool
     */
    public function suspended()
    {
        $this->setStatuses('suspended');

        return $this->save();
    }
}
