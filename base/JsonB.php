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
        $prepareConfig = $this->preparePopulateJsonB($config);

        foreach ($prepareConfig as $name => $value) {
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
            $this->_attributes[$name] = new JsonB($value);
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
            if (is_array($field) && (count($field) > 0)) {
                $return[$key] = new JsonB($field);
            } elseif (is_scalar($field)) {
                $return[$key] = $field;
            }
        }

        return $return;
    }

    /**
     * Добавляем значения в аттрибуты, если есть, то переписывается
     *
     * @param array $attributes - аттрибуты
     *
     * @return void
     */
    public function addAttributes(array $attributes)
    {
        foreach ($attributes as $key => $attribute) {
            $this->prepareSet($key, $attribute);
        }
    }

    /**
     * Удалем аттрибуты
     *
     * @param array $attributes - аттрибуты для удаления
     *
     * @return void
     */
    public function delAttributes(array $attributes)
    {
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $this->_attributes)) {
                unset($this->_attributes[$attribute]);
            }
        }
    }
}
