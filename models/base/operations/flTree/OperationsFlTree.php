<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 10.08.16
 * Time: 9:33
 */

namespace docflow\models\base\operations\flTree;

use docflow\base\JsonB;
use docflow\behaviors\LinkSimpleBehavior;
use docflow\behaviors\LinkStructuredBehavior;
use docflow\models\base\operations\flTree\links\OperationsLinksSimpleNope;
use docflow\models\base\operations\Operations;
use docflow\models\base\operations\flTree\links\OperationsLinksFlTreeNope;
use yii\db\ActiveQuery;
use yii\db\Connection;

/**
 * Class OperationsFlTree
 * Данный класс является исключением(не является операцией) и
 * необходим только для постройки неструктурированного плоского дерева
 *
 * @property integer $id
 * @property string  $operation_type
 * @property integer $status_id
 * @property integer $unit_real_id
 * @property integer $unit_resp_id
 * @property JsonB   $field
 * @property string  $comment
 *
 * @package Docflow\Models\Base
 */
class OperationsFlTree extends Operations
{
    /**
     * Имя операции
     *
     * @return mixed
     */
    public function getOperationName()
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
        return $this->id;
    }

    /**
     * Получаем статус по имени документа
     *
     * @param string $name - имя
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getOperationsByName($name)
    {
        return static::find()
            ->where(['id' => $name])
            ->one();
    }

    /**
     * Работа с документом и статусом операции
     *
     * @param object $document - документ
     *
     * @return mixed
     */
    protected function work($document)
    {
        // Метод в данном классе не нужный
    }
}
