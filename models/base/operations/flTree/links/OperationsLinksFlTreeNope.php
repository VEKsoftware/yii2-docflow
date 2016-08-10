<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 09.08.16
 * Time: 16:34
 */

namespace docflow\models\base\operations\flTree\links;

class OperationsLinksFlTreeNope extends OperationsLinks
{
    /**
     * Фильтрация по дополнительным параметрам
     *
     * @return array
     */
    public function extraWhere()
    {
        return [
            static::$_typeField => static::LINK_TYPE_FLTREE,
            static::$_relationTypeField => static::OPERATIONS_RELATION_TYPES_NOPE
        ];
    }
}
