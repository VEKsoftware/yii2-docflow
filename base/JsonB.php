<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.07.16
 * Time: 16:50
 */

namespace docflow\base;

use yii\base\InvalidCallException;
use yii\base\Object;
use yii\base\UnknownPropertyException;

class JsonB extends Object
{
    /**
     * Текущие аттрибуты
     *
     * @var array
     */
    private $_attributes;

    /**
     * JsonB constructor.
     *
     * @param array $config - входная конфигурация
     */
    public function __construct(array $config)
    {
        foreach ($config as $name => $value) {
            $this->_attributes[$name] = $value;
        }

        parent::__construct();
    }

    /**
     * Получаем значение свойства по имени
     *
     * @param string $name - имя свойства
     *
     * @return mixed
     *
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Устанавливаем значение свойству
     *
     * @param string $name  - имя свойства
     * @param mixed  $value - значение свойства
     *
     * @return void
     *
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_attributes)) {
            $this->prepareSet($name, $value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Массово устанавливаем значения аттрибутов
     *
     * @param array $attributes - аттрибуты
     *
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            if (array_key_exists($name, $this->_attributes)) {
                $this->prepareSet($name, $value);
            }
        }
    }

    /**
     * Подготавливаем присвоение значений
     *
     * @param string|integer             $name  - ключ
     * @param array|string|integer|float $value - значение
     *
     * @return void
     */
    protected function prepareSet($name, $value)
    {
        if (is_array($value)) {
            $this->_attributes[$name] = new JsonB($this->preparePopulateJsonB($value));
        } elseif (is_scalar($value)) {
            $this->_attributes[$name] = $value;
        }
    }

    /**
     * Получаем все значения аттрибутов
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Обрабатываем поступающие параметры
     *
     * @param array $fields - массив параметров ключ->значение
     *
     * @return array
     */
    protected function preparePopulateJsonB(array $fields)
    {
        $return = [];

        foreach ($fields as $key => $field) {
            if (is_array($field)) {
                $return[$key] = new JsonB($this->preparePopulateJsonB($field));
            } else {
                $return[$key] = $field;
            }
        }

        return $return;
    }
}
