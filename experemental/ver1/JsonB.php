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
     * Свойство, показывающее, фэйковый объект или нет.
     * Необходимо для фильтрации на этапе сохранения фэйковых объектов, созданных геттерами и не использованными сеттерами
     *
     * @var bool
     */
    public $isFake = false;

    /**
     * Ссылка на родительский объект
     *
     * @var null|JsonB
     */
    public $parentObject;

    /**
     * JsonB constructor.
     *
     * @param array      $config       - входная конфигурация
     * @param bool       $isFake       - объект фэйк или нет
     * @param null|JsonB $parentObject - ссылка на объект родителя
     */
    public function __construct(array $config, $isFake = false, &$parentObject = null)
    {
        /* Устанавливаем состояние объекта, фэйк или нет */
        $this->isFake = $isFake;

        /* Присваиваем свойству ссылку на родительский объект если он передан */
        $this->parentObject = $parentObject;

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
                return $this->_attributes[$name] = new JsonB([], true, $this);
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
                $this->setParamIsFaceToFalseByRecursive();
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
            $prepare = $this->prepareSaveJsonB();

            if ($prepare !== null) {
                $return = json_encode($prepare);
            }
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
        if ($this->isFake === false) {
            foreach ($this->_attributes as $key => $value) {
                if ($value instanceof JsonB) {
                    $value = call_user_func([$value, 'prepareSaveJsonB']);

                    if ($value !== null) {
                        $return[$key] = $value;
                    }
                } else {
                    $return[$key] = $value;
                }
            }

            return $return;
        } else {
            return null;
        }
    }

    /**
     * Проверяем, есть свойство или нет.
     *
     * @param string $name - имя свойства
     *
     * @return bool
     */
    public function __isset($name)
    {
        return parent::__isset($name);
    }

    /**
     * Удаляем свойства.
     *
     * @param string $name - имя свойства
     *
     * @return void
     */
    public function __unset($name)
    {
        parent::__unset($name);
    }

    /**
     * Устанавливаем свойство isFake объектов JsonB в false рекурсивно до корня
     *
     * @return void
     */
    protected function setParamIsFaceToFalseByRecursive()
    {
        $this->isFake = false;

        if ($this->parentObject !== null) {
            $this->parentObject->setParamIsFaceToFalseByRecursive();
        }
    }
}
