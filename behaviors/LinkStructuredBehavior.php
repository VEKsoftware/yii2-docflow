<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:15
 */

namespace docflow\behaviors;

use docflow\models\Statuses;
use docflow\models\StatusesLinks;
use yii\base\ErrorException;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class LinkStructuredBehavior extends LinkSimpleBehavior
{
    /**
     * Получаем все родительсие статусы
     *
     * @return array
     */
    public function getParents()
    {
        try {
            $parentsLinks = StatusesLinks::findUpperLinks($this->owner->id)->all();

            $idArray = array_map(
                function ($value) {
                    return $value->status_from;
                },
                $parentsLinks
            );

            $return = $this->owner->getStatusesByIdArray($idArray);
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }


        return $return;
    }

    /**
     * Устанавливаем родителя документу
     *
     * @param object $statusObj - Статус
     *
     * @return array
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function setParent($statusObj)
    {
        try {
            if (empty($this->owner->id)) {
                throw new ErrorException('Id перемещаемого статуса пуст');
            }

            if (!($statusObj instanceof Statuses)) {
                throw new ErrorException('Аргумент не объект Statuses');
            }

            if (empty($statusObj->id)) {
                throw new ErrorException('Id родительского статуса пуст');
            }

            /* Получаем родителей у текущего документа */
            $childrens = $this->getChildrens();

            if (array_key_exists($statusObj->tag, $childrens)) {
                throw new ErrorException('Нельзя устанавливать ребенка родителем');
            }

            /**
             * @var StatusesLinks $flTreeLink
             */
            $flTreeLink = StatusesLinks::getFlTreeLinkForStatusForLevel1($this->owner->id);

            if (empty($flTreeLink)) {
                $statusesLinksClass = Instance::ensure([], StatusesLinks::className());

                $statusesLinksClass->setScenario(StatusesLinks::LINK_TYPE_FLTREE);

                $statusesLinksClass->status_from = $statusObj->id;
                $statusesLinksClass->status_to = $this->owner->id;
                $statusesLinksClass->type = 'fltree';
                $statusesLinksClass->level = 1;

                $relationType = $this->getRelationType();
                if (!empty($relationType) && is_string($relationType)) {
                    $statusesLinksClass->relation_type = $relationType;
                }

                $moveResult = $statusesLinksClass->save();
            } else {
                if ($flTreeLink->status_from === $statusObj->id) {
                    throw new ErrorException('Id нового статуса родителя совпадает с Id текущего статуса родителя');
                }

                $flTreeLink->status_from = $statusObj->id;
                $flTreeLink->setScenario(StatusesLinks::LINK_TYPE_FLTREE);

                $moveResult = $flTreeLink->save();
            }

            $result = $this->moveResult($moveResult);
        } catch (ErrorException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Получаем все вложенные статусы
     *
     * @return array
     */
    public function getChildes()
    {
        try {
            $childesLinks = StatusesLinks::findLowerLinks($this->owner->id)->all();

            $idArray = array_map(
                function ($value) {
                    return $value->status_to;
                },
                $childesLinks
            );

            $return = $this->owner->getStatusesByIdArray($idArray);
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Добавляем документу вложенный документ
     *
     * @param object $statusObj - Статус
     *
     * @return array
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function setChildes($statusObj)
    {
        try {
            if (empty($this->owner->id)) {
                throw new ErrorException('Id родительского статуса пуст');
            }

            if (!($statusObj instanceof Statuses)) {
                throw new ErrorException('Аргумент не объект Statuses');
            }

            if (!is_int($statusObj->id)) {
                throw new ErrorException('Id перемещаемого статуса пуст');
            }

            /* Получаем родителей у текущего документа */
            $parents = $this->getParents();

            if (array_key_exists($statusObj->tag, $parents)) {
                throw new ErrorException('Нельзя устанавливать родителя ребенком');
            }

            /**
             * @var StatusesLinks $flTreeLink
             */
            $flTreeLink = StatusesLinks::getFlTreeLinkForStatusForLevel1($this->owner->id);

            if (empty($flTreeLink)) {
                $statusesLinksClass = Instance::ensure([], StatusesLinks::className());

                $statusesLinksClass->setScenario(StatusesLinks::LINK_TYPE_FLTREE);

                $statusesLinksClass->status_from = $this->owner->id;
                $statusesLinksClass->status_to = $statusObj->id;
                $statusesLinksClass->type = 'fltree';
                $statusesLinksClass->level = 1;

                $relationType = $this->getRelationType();
                if (!empty($relationType) && is_string($relationType)) {
                    $statusesLinksClass->relation_type = $relationType;
                }

                $moveResult = $statusesLinksClass->save();
            } else {
                if ($flTreeLink->status_from === $statusObj->id) {
                    throw new ErrorException('Id нового статуса родителя совпадает с Id текущего статуса родителя');
                }

                $flTreeLink->status_from = $statusObj->id;
                $flTreeLink->setScenario(StatusesLinks::LINK_TYPE_FLTREE);

                $moveResult = $flTreeLink->save();
            }

            $result = $this->moveResult($moveResult);
        } catch (ErrorException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }


    /**
     * Возвращяем сообщение в зависимости от результата
     *
     * @param bool $result - результат перемещения,
     *                     true - перемещение произошло удачно,
     *                     false - перемещение не произошло
     *
     * @return array
     */
    protected function moveResult($result)
    {
        $return = ['error' => 'Ошибка перемещения'];

        if ($result === true) {
            $return = ['success' => 'Позиция изменена'];
        }

        return $return;
    }

    /**
     * Получаем структуру дерева
     *
     * @return array
     */
    public function getTree()
    {
        return array_map([$this, 'treeBranch'], $this->owner->docType->statusesStructure);
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
}
