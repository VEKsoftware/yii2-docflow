<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 13:03
 */

namespace docflow\widgets;

use docflow\models\Document;
use yii\base\ErrorException;
use yii\base\InvalidParamException;

class FlTreeWithSimpleLinksWidget extends FlTreeBase
{
    /**
     * Текущий документ
     *
     * @var Document
     */
    public $document;

    /**
     * Инициируем виджет
     *
     * @return void
     *
     * @throws ErrorException
     */
    public function init()
    {
        parent::init();
        if (!($this->document instanceof Document)) {
            throw new ErrorException('Один из передаваемых документов не является наследником Document');
        }

        $hasSimple = $this->checkForSimpleBehavior($this->document);

        if ($hasSimple === false) {
            throw new ErrorException('К документу не подключено поведение LinkSimpleBehavior');
        }

        if (empty($this->document->{$this->document->linkFieldsArray['node_id']}) || !is_int($this->document->{$this->document->linkFieldsArray['node_id']})) {
            throw new ErrorException('Один из передаваемых документов пуст');
        }
    }

    /**
     * Получаем структуру дерева статусов, для simple links
     *
     * @return array
     * @throws ErrorException
     */
    protected function getTreeWithSimpleLinks()
    {
        return array_map([$this, 'treeBranchWithSimpleLinks'], $this->getDocumentsStructure());
    }

    /**
     * Формируем ветви с учётом simple links
     *
     * @param mixed $val - Ветка
     *
     * @return array
     */
    protected function treeBranchWithSimpleLinks($val)
    {
        $linkBool = isset($this->document->statusesTransitionTo[$val->tag]);

        return array_merge(
            [
                'text' => $val->{$val->docName()},
                'href' => '&tagFrom=' . $this->document->tag . '&tagDoc=' . $val->docTag() . '&tagTo=' . $val->tag,
            ],
            ($val->tag === $this->document->tag)
                ? ['backColor' => 'gray']
                : [],
            ($linkBool === true)
                ? ['state' => ['checked' => true]]
                : [],
            (empty($val->statusChildren))
                ? []
                : ['nodes' => array_map([$this, 'treeBranchWithSimpleLinks'], $val->statusChildren)]
        );
    }

    /**
     * Выполняем виджет
     *
     * @return string
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    public function run()
    {
        return $this->render('flTreeWithSimpleLinks', ['tree' => $this->getTreeWithSimpleLinks()]);
    }
}
