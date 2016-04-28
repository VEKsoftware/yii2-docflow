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
     *        'value' => 'view',
     *        'label' => 'Просматривать шаблоны',
     *        'items' => [
     *            [
     *                'value' => 'all',
     *                'label' => 'Все шаблоны',
     *            ],
     *        ],
     *  ],
     */
    abstract public static function accessData();

}
