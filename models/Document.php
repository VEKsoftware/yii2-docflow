<?php

namespace docflow\models;

use Yii;
use yii\helpers\ArrayHelper;

use docflow\Docflow;
use docflow\base\CommonRecord;
use docflow\models\DocTypes;

/**
 * This is the base model class for all documents.
 *
 */
abstract class Document extends CommonRecord
{
    protected static $statusIdField = 'status_id';
    protected static $newStatusTag = 'active';

    /**
     * This function returns the document tag. This tag is used to get
     * all information about the doument type from the database.
     *
     * @return string Document tag
     */
    abstract public static function docTag();

    /**
     * Return description of the type of current document
     * @return \docflow\models\DocTypes the object specifying the document type
     */
    public static function getDoc()
    {
        return DocTypes::getDocType(static::docTag());
    }

    /**
     * This function returns the structure containing access rights tags.
     *
     * @return mixed Structure is the following
     *  [
     *     [
     *        'operation' => 'view', // This is the name of operation. It will be refered in the access check methods like $user->can(operation)
     *        'label' => 'View document',
     *        'conditions' => [ // These conditions are handled in the document model and are set up in the access settings page
     *            [
     *                'condition' => 'own',
     *                'label' => 'Only my',
     *            ],
     *            [
     *                'condition' => 'all',
     *                'label' => 'All',
     *            ],
     *        ],
     *    ],
     *    [
     *      ...
     *    ],
     *    ...
     *  ],
     */
    abstract public static function accessData();

    /**
     * This method resolves the relation names between $this object and that is set as an argument.
     *
     * @param class $obj Some class instance which is used to resolve the relation to the current object.
     *    Normally, this is a \yii\web\User instance.
     * @return string[] List of condition names those listed in [[self::accessData()]]. Default is ['any'].
     */
    public function resolveRelationTo($obj)
    {
        return ['any'];
    }

}