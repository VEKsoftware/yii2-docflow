<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 05.07.16
 * Time: 10:56
 *
 * Данное расширение поведения позволяет записывать результат запроса в свойство и вызывать его кешируемый результат
 */

namespace docflow\base;

use yii\base\Behavior;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQueryInterface;

class ActivePropertiesBehavior extends Behavior
{
    /**
     * @var array
     */
    private $_cache = [];

    /**
     * Кешируем запросы
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws UnknownPropertyException
     */
    public function __get($name)
    {
        if (isset($this->_cache[$name]) || array_key_exists($name, $this->_cache)) {
            return $this->_cache[$name];
        }

        $value = parent::__get($name);
        if ($value instanceof ActiveQueryInterface) {
            return $this->_cache[$name] = $value->findFor($name, $this);
        } else {
            return $value;
        }
    }
}
