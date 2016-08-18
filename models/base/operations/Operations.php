<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 05.08.16
 * Time: 13:02
 */

namespace docflow\models\base\operations;

use docflow\base\JsonB;
use docflow\behaviors\LogMultiple;
use docflow\models\base\OperationBase;
use yii;
use yii\base\ErrorException;

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
 */
abstract class Operations extends OperationBase
{
    /**
     * Хранилище итемов
     *
     * @var array
     */
    protected $documents = [];

    /**
     * Все сценарии.
     *
     * @var array
     */
    public static $allScenarios = [];

    /**
     * Список операций, где ключ - имя операции, значение - имя класса выполняющего операцию
     *
     * @var array
     */
    public static $operationsList = [];

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
        return '';
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
     *
     * @param array $attributes - аттрибуты
     *
     * @return operations
     */
    public static function instantiate(array $attributes)
    {
        $class = static::className();
        if (array_key_exists($attributes['operation_type'], static::$operationsList)) {
            $class = static::$operationsList[$attributes['operation_type']];
        }

        return new $class($attributes);
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
}
