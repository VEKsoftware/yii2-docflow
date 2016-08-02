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
     * @param string $param      - параметр конфигурации
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsNotEmptyAndString($param, $paramRoute)
    {
        static::checkParamIsNotEmpty($param, $paramRoute);
        static::checkParamIsString($param, $paramRoute);
    }

    /**
     * Проверяем параметр на пустоту и соответствие массиву
     *
     * @param string $param      - параметр конфигурации
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsNotEmptyAndArray($param, $paramRoute)
    {
        static::checkParamIsNotEmpty($param, $paramRoute);
        static::checkParamIsArray($param, $paramRoute);
    }

    /**
     * Проверяем параметр на присутствие в массиве и является ли параметр строкой
     *
     * @param array  $array      - массив конфигурации
     * @param string $param      - параметр в массиве
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamInArrayExistAndNotEmptyAndString(array $array, $param, $paramRoute)
    {
        static::checkParamInArrayExistAndNotEmpty($array, $param, $paramRoute);
        static::checkParamIsString($array[$param], $paramRoute);
    }

    /**
     * Проверяем параметр на присутствие в массиве и является ли параметр массивом
     *
     * @param array  $array      - массив конфигурации
     * @param string $param      - параметр в массиве
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamInArrayExistAndNotEmptyAndArray(array $array, $param, $paramRoute)
    {
        static::checkParamInArrayExistAndNotEmpty($array, $param, $paramRoute);
        static::checkParamIsArray($array[$param], $paramRoute);
    }

    /**
     * Проверяем параметр на присутствие в массиве и является ли параметр булевым
     *
     * @param array  $array      - массив конфигурации
     * @param string $param      - параметр в массиве
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamInArrayExistAndNotEmptyAndBool(array $array, $param, $paramRoute)
    {
        static::checkParamInArrayExistAndNotEmpty($array, $param, $paramRoute);
        static::checkParamIsBool($array[$param], $paramRoute);
    }

    /**
     * Проверяем параметр на присутствие в массиве
     *
     * @param array  $array      - массив конфигурации
     * @param string $param      - параметр в массиве
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamInArrayExistAndNotEmpty(array $array, $param, $paramRoute)
    {
        static::checkParamIsExistInArray($array, $param, $paramRoute);
        static::checkParamIsNotEmpty($array[$param], $paramRoute);
    }

    /**
     * Проверяем, является ли передаваемый параметр булевым типом
     *
     * @param mixed  $param      - параметр
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsBool($param, $paramRoute)
    {
        if (!is_bool($param)) {
            throw new ErrorException('параметр ' . $paramRoute . ' не булев');
        }
    }

    /**
     * Проверяем, является ли передаваемый параметр массивом
     *
     * @param mixed  $param      - параметр
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsArray($param, $paramRoute)
    {
        if (!is_array($param)) {
            throw new ErrorException('параметр ' . $paramRoute . ' не массив');
        }
    }

    /**
     * Проверяем, является ли передаваемый параметр строкой
     *
     * @param mixed  $param      - параметр
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsString($param, $paramRoute)
    {
        if (!is_string($param)) {
            throw new ErrorException('параметр ' . $paramRoute . ' не строка');
        }
    }

    /**
     * Проверяем параметр на присутствие в массиве
     *
     * @param array  $array      - массив
     * @param mixed  $param      - параметр
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsExistInArray(array $array, $param, $paramRoute)
    {
        if (!array_key_exists($param, $array)) {
            throw new ErrorException('параметр ' . $paramRoute . ' не найден в конфигурации');
        }
    }

    /**
     * Проверяем параметр на пустоту
     *
     * @param mixed  $param      - параметр
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkParamIsNotEmpty($param, $paramRoute)
    {
        $isNumeric = is_numeric($param);
        $isEmptyString = (is_string($param) && (strlen($param) < 1));
        $isBool = is_bool($param);

        if (!$isBool && !$isNumeric && $isEmptyString && empty($param)) {
            throw new ErrorException('параметр ' . $paramRoute . ' пуст');
        }
    }
}