<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 05.08.16
 * Time: 9:08
 */

namespace docflow\helpers;

use docflow\base\UnstructuredRecord;
use yii\helpers\ArrayHelper;

class DocFlowArrayHelper extends ArrayHelper
{
    /**
     * Устанавливаем ключи по значению типа x->y->z
     * Обязательно должен быть объектный доступ на протяжении всей цепочик - получение и установка
     *
     * @param UnstructuredRecord $object - объект
     * @param string             $key    - ключ
     * @param mixed              $value  - устанавливаемое значение
     *
     * @return void
     */
    public static function setValues(&$object, $key, $value)
    {
        $explode = explode('.', $key);

        /* Распаковка */
        $endField = $object;
        $previous = [];
        foreach ($explode as $field) {
            $endField = $endField->{$field};
            $previous[] = $endField;
        }

        /* Установление нового значения */
        $lastNum = (count($previous) - 1);
        $previous[$lastNum] = $value;

        /* Упаковка */
        for ($current = (count($previous) - 1); $current >= 1; $current--) {
            $next = ($current - 1);
            $previous[$next]->{$explode[$current]} = $previous[$current];
        }

        /* Финальное присваивание */
        $object->{$explode[0]} = $previous[0];
    }
}
