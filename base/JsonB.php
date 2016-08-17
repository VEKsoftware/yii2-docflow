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
use yii\helpers\ArrayHelper;

/**
 * Class JsonB
 *
 * @package Docflow\Base
 *
 * @property array $attributes
 */
class JsonB extends Object
{
    /**
     * Текущие аттрибуты
     *
     * @var array
     */
    private $_attributes;

    /**
     * Родительский объект
     *
     * @var JsonB|null
     */
    public $parentObject;

    /**
     * Ключ у родителя
     *
     * @var null|string
     */
    public $parentKey;

    /**
     * JsonB constructor.
     *
     * @param array       $config       - входная конфигурация
     * @param JsonB|null  $parentObject - родительский объект
     * @param string|null $parentKey    - родительский ключ
     */
    public function __construct(array $config, &$parentObject = null, $parentKey = null)
    {
        /* Записываем родительский объект */
        $this->parentObject = $parentObject;

        /* Записываем ключ, которы в родителе указываем на текущий объект */
        $this->parentKey = $parentKey;

        $prepareConfig = $this->preparePopulateJsonB($config);

        foreach ($prepareConfig as $name => $value) {
            $this->_attributes[$name] = $value;
        }

        parent::__construct();
    }

    /**
     * Получаем значение свойства по имени
     * 1)Если свойства нет, то обдаем null
     * 2)Если есть, то его значение
     *
     * @param string $name - имя свойства
     *
     * @return mixed|null
     *
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function __get($name)
    {
        if (is_array($this->_attributes) && array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        } else {
            try {
                return parent::__get($name);
            } catch (UnknownPropertyException $e) {
                return new JsonB([], $this, $name);
            }
        }
    }

    /**
     * Устанавливаем значение свойству:
     * 1)Если свойства нет, то создается
     * 2)Если есть, то обновляется
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
        if (is_array($this->_attributes) && array_key_exists($name, $this->_attributes)) {
            $this->prepareSet($name, $value);
        } else {
            try {
                parent::__set($name, $value);
            } catch (UnknownPropertyException $e) {
                $this->prepareSet($name, $value);

                /* Создаем отсутствующий путь */
                $this->createPath();
            }
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
        if (is_array($value) && (count($value) > 0)) {
            $isAssociative = ArrayHelper::isAssociative($value);

            if ($isAssociative) {
                $this->_attributes[$name] = new JsonB($value);
            } else {
                $this->_attributes[$name] = $value;
            }
        } elseif (is_scalar($value) || ($value instanceof JsonB)) {
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
                $isAssociative = ArrayHelper::isAssociative($field);

                if ($isAssociative) {
                    $return[$key] = new JsonB($field);
                } else {
                    $return[$key] = $field;
                }
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

    /**
     * При вызове объекта как строки
     *
     * @return string
     */
    public function __toString()
    {
        $return = '';

        if (count($this->_attributes) > 0) {
            $return = json_encode($this->prepareSaveJsonB());
        }

        return $return;
    }

    /**
     * Подготавливаем скрытые аттрибуты для сохранения
     *
     * @return array
     */
    public function prepareSaveJsonB()
    {
        $return = [];

        foreach ($this->_attributes as $key => $value) {
            if ($value instanceof JsonB) {
                $return[$key] = call_user_func([$value, 'prepareSaveJsonB']);
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * Создаем путь, если он отсутствует
     *
     * @return void
     */
    protected function createPath()
    {
        if (($this->parentObject !== null) && ($this->parentKey !== null)) {
            $this->parentObject->{$this->parentKey} = $this;
        }
    }

    /**
     * Удаляем свойство
     *
     * @param string $name - имя свойства
     *
     * @return void
     *
     * @throws InvalidCallException
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            unset($this->_attributes[$name]);
        } else {
            parent::__unset($name);
        }
    }
}
