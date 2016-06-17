<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 03.06.16
 * Time: 12:30
 */

namespace docflow\models;

use docflow\Docflow;
use yii;
use yii\base\ErrorException;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\di\Instance;
use yii\helpers\Url;

class StatusTreePosition extends Model
{
    /**
     * @var string Данное свойство нужно для возможности сравнения в callback
     */
    protected $statusTag;
    /**
     * @var array Массив с simple links для данного стутаса для сравнения в callback
     */
    protected $simpleLinks;

    /**
     * Получаем структуру дерева
     *
     * @param array $rawStructure - начальная структура
     *
     * @return array
     */
    public function getTree(array $rawStructure)
    {
        return array_map([$this, 'treeBranch'], $rawStructure);
    }

    /**
     * Формируем ветви
     *
     * @param mixed $val Ветка
     *
     * @return array
     *
     * @throws \yii\base\InvalidParamException
     */
    protected function treeBranch($val)
    {
        return array_merge(
            [
                'text' => $val->name,
                'href' => Url::to(['status-view', 'doc' => $val->docType->tag, 'tag' => $val->tag]),
            ],
            (empty($val->statusChildren))
                ? []
                : ['nodes' => array_map([$this, 'treeBranch'], $val->statusChildren)]
        );
    }

    /**
     * Получаем структуру дерева статусов, для simple links
     *
     * @param array    $rawStructure - начальная структура
     * @param Statuses $model        - найденные simple links для Статуса
     *
     * @return array
     */
    public function getTreeWithSimpleLinks(array $rawStructure, Statuses $model)
    {
        $this->statusTag = $model->tag;
        $this->simpleLinks = $model->statusesTransitionTo;

        return array_map([$this, 'treeBranchWithSimpleLinks'], $rawStructure);
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
        $linkBool = isset($this->simpleLinks[$val->tag]);

        return array_merge(
            [
                'text' => $val->name,
                'href' => '&tagFrom=' . $this->statusTag . '&tagDoc=' . $val->docType->tag . '&tagTo=' . $val->tag,
            ],
            ($val->tag === $this->statusTag)
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
}
