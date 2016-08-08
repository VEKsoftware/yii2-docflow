<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 05.08.16
 * Time: 13:02
 */

namespace docflow\models;

use yii\base\ErrorException;
use yii\db\Connection;

/**
 * Class Operations
 *
 * @property integer $id
 *
 * @package Docflow\Models
 */
abstract class Operations extends Document
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
     * This function returns the structure containing access rights tags.
     *
     * @return mixed Structure is the following
     *  [
     *     [
     *        'operation' => 'view', // This is the name of operation. It will be refered in the access check methods like $user->can(operation)
     *        'label' => 'View document',
     *        'conditions' => [ // These conditions are handled in the document model and are set up in the access settings page
     *            [
     *                'condition' => 'own',
     *                'label' => 'Only my',
     *            ],
     *            [
     *                'condition' => 'all',
     *                'label' => 'All',
     *            ],
     *        ],
     *    ],
     *    [
     *      ...
     *    ],
     *    ...
     *  ],
     */
    /*
    public static function accessData()
    {
        return [];
    }*/

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
     * Получае БД используемую в модуле
     * Пример содержания:
     *      return Docflow::getInstance->db
     *
     * @return Connection
     */
    abstract public static function getModuleDb();

    /**
     * Получаем объект в зависимости от входящих аттрибутов
     *
     * @param array $attributes - аттрибуты
     *
     * @return operations
     */
    public static function instantiate($attributes)
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
    public function addItems($items)
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
    protected function batchInsertItems($items)
    {
        $columns = static::getAttributes();
        unset($columns['id'], $columns['atime'], $columns['version']);

        return \Yii::$app->{static::getModuleDb()}
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
}
