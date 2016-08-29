<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.08.16
 * Time: 17:18
 */

namespace docflow\helpers;

class PostgresArrayTypeHelper
{
    /**
     * Создаем
     *
     * @param array $elements - элементы
     *
     * @return string|null
     */
    public static function create($elements)
    {
        $return = null;

        if (count($elements) > 0) {
            $implode = implode(',', $elements);

            $return = '{' . $implode . '}';
        }

        return $return;
    }

    /**
     * Разбираем на составляющие
     *
     * @param string $string - строка
     *
     * @return array
     */
    public static function parse($string)
    {
        $trim = trim($string, '{}');

        return explode(',', $trim);
    }

    /**
     * Добавляем элемент
     *
     * @param string $row      - сформированная строка
     * @param array  $elements - массив элементов, который необходимо добавить
     *
     * @return array
     */
    public static function add($row, array $elements)
    {
        $array = static::parse($row);

        /* Мержим, оставляем уникальные */
        $array = array_unique(array_merge($array, $elements));
        /* Cортируем по возрастанию без сохранения ключей */
        sort($array);

        return static::create($array);
    }

    /**
     * Удаляем элемент
     *
     * @param string $row      - сформированная строка
     * @param array  $elements - массив элементов элемент, которыей необходимо удалить
     *
     * @return string
     */
    public static function del($row, array $elements)
    {
        $array = static::parse($row);

        $array = array_filter($array, function ($value) use ($elements) {
            $key = array_search($value, $elements, false);

            if ($key === false) {
                return $value;
            }
        });

        return static::create($array);
    }

    /**
     * Проверяем, присутствует-ли элемент в строке
     *
     * @param string $row     - сформированная строка
     * @param string $element - элемент, который ищем
     *
     * @return bool
     */
    public static function isFound($row, $element)
    {
        $array = static::parse($row);
        $key = array_search($element, $array, false);

        return ($key !== false) ?? true;
    }
}
