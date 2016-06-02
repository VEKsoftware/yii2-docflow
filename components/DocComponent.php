<?php
namespace docflow\components;

use Yii;
use yii\base\Component;
use docflow\models\DocTypes;

class DocComponent extends Component
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        DocTypes::getDocTypes();
    }

    /**
     * Return a list of objects bearing informaton about the types of documents registered in the system.
     * So, you can use it as ``Yii::$app->doc->types['items'];``.
     *
     * @return docflow\models\DocTypes
     */
    public function getTypes()
    {
        return DocTypes::getDocTypes();
    }
}
