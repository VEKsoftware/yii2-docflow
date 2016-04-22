<?php
namespace docflow\components;

use Yii;
use yii\base\Component;

class DocComponent extends Component
{
    protected $_docs;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->_docs = DocTypes::listDocs();
    }

    /**
     * Return a list of objects bearing informaton about the types of documents registered in the system.
     * So, you can use it as ``Yii::$app->doc->types['items'];``.
     *
     * @return docflow\models\DocTypes
     */
    public function getTypes()
    {
        return $this->_docs;
    }
}