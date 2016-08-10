<?php

namespace docflow\models\base;

use docflow\models\base\docType\DocTypes;
use yii;

/**
 * This is the base model class for all documents.
 */
abstract class Document extends DocFlowBase
{
    protected static $statusIdField = 'status_id';
    protected static $newStatusTag = 'active';

    /**
     * This function returns the document tag. This tag is used to get
     * all information about the document type from the database.
     *
     * @return string Document tag
     */
    abstract static public function docTag();

    /**
     * Return field name which use how Document `name`
     *
     * @return string Document name
     */
    abstract public function getDocName();

    /**
     * Получаем документ по его идентификатору
     *
     * @param integer $nodeId - id документа
     *
     * @return mixed
     */
    abstract public static function getDocumentByNodeId($nodeId);

    /**
     * Return description of the type of current document
     *
     * @return DocTypes the object specifying the document type
     */
    public function getDoc()
    {
        return DocTypes::getDocType(static::docTag());
    }

    /**
     * This method resolves the relation names between $this object and that is set as an argument.
     *
     * @param class $obj Some class instance which is used to resolve the relation to the current object.
     *                   Normally, this is a \yii\web\User instance.
     * @return string[] List of condition names those listed in [[self::accessData()]]. Default is ['any'].
     */
    public function resolveRelationTo($obj)
    {
        return ['any'];
    }

}
