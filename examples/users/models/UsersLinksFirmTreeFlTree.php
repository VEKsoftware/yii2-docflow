<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 28.06.16
 * Time: 16:39
 */

namespace docflow\examples\users\models;

class UsersLinksFirmTreeFlTree extends UsersLinks
{
    public function extraWhere()
    {
        return [
            static::$_typeField => static::LINK_TYPE_FLTREE,
            static::$_relationTypeField => static::RELATION_TYPE_FIRM_TREE,
        ];
    }
}
