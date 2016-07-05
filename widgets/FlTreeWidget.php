<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 13:03
 */

namespace docflow\widgets;

use yii\base\ErrorException;
use yii\base\InvalidParamException;
use yii\helpers\Url;

class FlTreeWidget extends FlTreeBase
{
    /**
     * Получаем структуру дерева
     *
     * @return array
     * @throws ErrorException
     */
    protected function getTree()
    {
        return array_map([$this, 'treeBranch'], $this->getDocumentsStructure());
    }

    /**
     * Формируем ветви
     *
     * @param mixed $val Ветка
     *
     * @return array
     *
     * @throws InvalidParamException
     */
    protected function treeBranch($val)
    {
        return array_merge(
            [
                'text' => $val->{$val->docName()},
                'href' => Url::to(['status-view', 'doc' => $val->docTag(), 'tag' => $val->tag]),
            ],
            (empty($val->statusChildren))
                ? []
                : ['nodes' => array_map([$this, 'treeBranch'], $val->statusChildren)]
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
        return $this->render('flTree', ['items' => $this->getTree()]);
    }
}
