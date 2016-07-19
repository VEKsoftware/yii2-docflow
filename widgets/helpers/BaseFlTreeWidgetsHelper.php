<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.07.16
 * Time: 12:31
 */

namespace docflow\widgets\helpers;

use yii\base\ErrorException;

class BaseFlTreeWidgetsHelper
{
    /**
     * Проверяем параметр на пустоту и соответствие строке
     *
     * @param string $param - параметр конфигурации
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsNotEmptyAndString($param)
    {
        static::checkParamIsNotEmpty($param);
        static::checkParamIsString($param);
    }

    /**
     * Проверяем параметр на пустоту и соответствие массиву
     *
     * @param string $param - параметр конфигурации
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsNotEmptyAndArray($param)
    {
        static::checkParamIsNotEmpty($param);
        static::checkParamIsArray($param);
    }

    /**
     * Проверяем параметр на присутствие в массиве и является ли параметр строкой
     *
     * @param array  $array - массив конфигурации
     * @param string $param - параметр в массиве
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamInArrayExistAndNotEmptyAndString(array $array, $param)
    {
        static::checkParamInArrayExistAndNotEmpty($array, $param);
        static::checkParamIsString($array[$param]);
    }

    /**
     * Проверяем параметр на присутствие в массиве и является ли параметр массивом
     *
     * @param array  $array - массив конфигурации
     * @param string $param - параметр в массиве
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamInArrayExistAndNotEmptyAndArray(array $array, $param)
    {
        static::checkParamInArrayExistAndNotEmpty($array, $param);
        static::checkParamIsArray($array[$param]);
    }

    /**
     * Проверяем параметр на присутствие в массиве
     *
     * @param array  $array - массив конфигурации
     * @param string $param - параметр в массиве
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamInArrayExistAndNotEmpty(array $array, $param)
    {
        static::checkParamIsExistInArray($array, $param);
        static::checkParamIsNotEmpty($array[$param]);
    }

    /**
     * Проверяем, является ли передаваемый параметр массивом
     *
     * @param mixed $param - параметр
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsArray($param)
    {
        if (!is_array($param)) {
            throw new ErrorException('параметр ' . $param . ' не массив');
        }
    }

    /**
     * Проверяем, является ли передаваемый параметр строкой
     *
     * @param mixed $param - параметр
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsString($param)
    {
        if (!is_string($param)) {
            throw new ErrorException('параметр ' . $param . ' не строка');
        }
    }

    /**
     * Проверяем параметр на присутствие в массиве
     *
     * @param array $array - массив
     * @param mixed $param - параметр
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsExistInArray(array $array, $param)
    {
        if (!array_key_exists($param, $array)) {
            throw new ErrorException('параметр ' . $param . ' не найден в конфигурации');
        }
    }

    /**
     * Проверяем параметр на пустоту
     *
     * @param mixed $param - параметр
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsNotEmpty($param)
    {
        if (empty($param)) {
            throw new ErrorException('параметр пуст');
        }
    }
}