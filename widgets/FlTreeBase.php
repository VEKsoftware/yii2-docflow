<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 16:35
 */

namespace docflow\widgets;

use docflow\behaviors\LinkSimpleBehavior;
use docflow\behaviors\LinkStructuredBehavior;
use docflow\models\Document;
use yii\base\ErrorException;
use yii\base\Widget;

class FlTreeBase extends Widget
{
    /**
     * Массив, содержащий объекты документов
     *
     * @var array
     */
    public $documents;

    /**
     * Получаем структуру документов
     *
     * @return array
     *
     * @throws ErrorException
     */
    protected function getDocumentsStructure()
    {
        $tree = [];

        foreach ($this->documents as $document) {
            if (!($document instanceof Document)) {
                throw new ErrorException('Один из передаваемых документов не является наследником Document');
            }

            $haveStructured = $this->checkForStructuredBehavior($document);

            if ($haveStructured === false) {
                throw new ErrorException('К документу не подключено поведение LinkStructuredBehavior');
            }

            if (empty($document->{$document->linkFieldsArray['node_id']}) || !is_int($document->{$document->linkFieldsArray['node_id']})) {
                throw new ErrorException('Один из передаваемых документов пуст');
            }

            if ($document->statusParent === null) {
                $tree[] = $document;
            }
        }

        return $tree;
    }

    /**
     * Проверяем, подключено ли кк докумену поведение класса LinkSimpleBehavior
     *
     * @param Document $document - Объект документа
     *
     * @return bool
     */
    protected function checkForSimpleBehavior($document)
    {
        $haveSimple = false;

        $behaviors = $document->getBehaviors();

        foreach ($behaviors as $behavior) {
            if ($behavior instanceof LinkSimpleBehavior) {
                $haveSimple = true;
            }
        }

        return $haveSimple;
    }

    /**
     * Проверяем, подключено ли к докумену поведение класса LinkStructuredBehavior
     *
     * @param Document $document - Объект документа
     *
     * @return bool
     */
    protected function checkForStructuredBehavior($document)
    {
        $haveStructured = false;

        $behaviors = $document->getBehaviors();

        foreach ($behaviors as $behavior) {
            if ($behavior instanceof LinkStructuredBehavior) {
                $haveStructured = true;
            }
        }

        return $haveStructured;
    }
}
