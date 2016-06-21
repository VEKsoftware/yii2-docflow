<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:15
 */

namespace docflow\behaviors;

use docflow\models\Document;
use docflow\models\Link;
use docflow\models\Statuses;
use docflow\models\StatusesLinks;
use yii;
use yii\base\Behavior;
use yii\base\ErrorException;
use yii\di\Instance;

class LinkSimpleBehavior extends Behavior
{
    const LINK_TYPE_SIMPLE = 'simple';

    const LINK_TYPE_FLTREE = 'fltree';

    /**
     * @var object - Класс связей (имя)
     */
    public $linkClass;

    /**
     * @var string - поле, по которому будет идти сортировка
     */
    public $orderedField = 'order_idx';

    /**
     * @var string - поле, по которому будет идти индексирование
     */
    public $indexBy = 'tag';

    public function attach($owner)
    {
        parent::attach($owner);

        if (!($owner instanceof Statuses)) {
            throw new ErrorException('Класс узла не принадлежит Statuses');
        }

        if (empty($this->linkClass) || !((new $this->linkClass) instanceof Link)) {
            throw new ErrorException('Отсутствует класс связей или не принадлежит Link');
        }
    }

    /**
     * Получаем все простые связи для данного документа
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @throws \yii\base\ErrorException
     */
    public function getSimpleLinks()
    {
        try {
            if (!is_int($this->owner->id) || empty($this->owner->id)) {
                throw new ErrorException('Id статуса From не integer типа или пустой');
            }

            $return = StatusesLinks::getAllSimpleLinksForTagFromId($this->owner->id);
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Массово устанавливаем простые связи
     *
     * @param array $tagsToArray - массив с объектами статусов To пуст
     *
     * @return array
     */
    public function setSimpleLinks(array $tagsToArray)
    {
        try {
            $result = ['error' => 'Установка простых связей не удалась'];

            if (!is_string($this->owner->docType->tag) || empty($this->owner->docType->tag)) {
                throw new ErrorException('Тэг документа не строкового типа или пустой');
            }

            if (!is_string($this->owner->tag) || empty($this->owner->tag)) {
                throw new ErrorException('Тэг статуса From не строкового типа или пустой');
            }

            if (count(($tagsToArray)) < 1) {
                throw new ErrorException('Массив с объектами статусов пуст');
            }

            $relationType = $this->getRelationType();

            $delCondition = ['status_from' => $this->owner->id, 'type' => 'simple'];

            if (!empty($relationType)) {
                $delCondition = array_merge($delCondition, ['relation_type' => $relationType]);
            }

            /* Удаляем все текущие простые связи */
            StatusesLinks::deleteAll($delCondition);

            /* Подготавливаем столбцы для массового добавления */
            $cols = [
                'status_from',
                'status_to',
                'right_tag',
                'type'
            ];

            if (!empty($relationType)) {
                array_push($cols, 'relation_type');
            }

            /* Подготавливаем содержимое для массового добавления */
            $rows = [];
            foreach ($tagsToArray as $value) {
                $attr = [
                    $this->owner->id,
                    $value->id,
                    $this->owner->docType->tag . '.' . $this->owner->tag . '.' . $value->tag,
                    static::LINK_TYPE_SIMPLE
                ];

                if (!empty($relationType)) {
                    array_push($attr, $relationType);
                }

                $rows[] = $attr;
            }

            /* Массово добавляем */
            $isSave = (bool)Yii::$app->db
                ->createCommand()
                ->batchInsert(StatusesLinks::tableName(), $cols, $rows)
                ->execute();

            if ($isSave === true) {
                $result = ['success' => 'Простые связи добавлены'];
            }
        } catch (ErrorException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Добавляем простую связь между документами From и To
     *
     * @param object $statusObj - Стутаус
     *
     * @return array
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function addSimpleLink($statusObj)
    {
        $statusLinkClass = Instance::ensure([], StatusesLinks::className());

        try {
            if (!($statusObj instanceof Statuses)) {
                throw new ErrorException('Аргумент не объект Statuses');
            }

            if (!is_string($this->owner->docType->tag) || empty($this->owner->docType->tag)) {
                throw new ErrorException('Тэг документа не строкового типа или пустой');
            }

            if (!is_int($this->owner->id) || empty($this->owner->id)) {
                throw new ErrorException('Id статуса From не integer типа или пустой');
            }

            if (!is_int($statusObj->id) || empty($statusObj->id)) {
                throw new ErrorException('Id статуса To не integer типа или пустой');
            }

            if ($this->owner->id === $statusObj->id) {
                throw new ErrorException('Нельзя назначить ссылку на себя');
            }

            $result = ['error' => 'Добавление не удалось'];

            /* Смотрим, есть ли в БД уже такая ссылка */
            $statusSimpleLink = StatusesLinks::getSimpleLinkForStatusFromIdAndStatusToId(
                $this->owner->id,
                $statusObj->id
            );

            if (is_object($statusSimpleLink)) {
                throw new ErrorException('Ссылка уже добавлена');
            }

            $relationType = $this->getRelationType();

            $statusLinkClass->setScenario(StatusesLinks::LINK_TYPE_SIMPLE);

            $statusLinkClass->status_from = $this->owner->id;
            $statusLinkClass->status_to = $statusObj->id;
            $statusLinkClass->type = $statusLinkClass::LINK_TYPE_SIMPLE;
            $statusLinkClass->right_tag = $this->owner->docType->tag . '.' . $this->owner->tag . '.' . $statusObj->tag;

            if (!empty($relationType) && is_string($relationType)) {
                $statusLinkClass->relation_type = $relationType;
            }

            $isSave = $statusLinkClass->save();

            if ($isSave === true) {
                $result = ['success' => 'Ссылка добавлена'];
            }
        } catch (ErrorException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Удаляем простую связь между документами From и To
     *
     * @param object $statusObj - Документ
     *
     * @return array
     *
     * @throws \Exception
     */
    public function delSimpleLink($statusObj)
    {
        try {
            if (!($statusObj instanceof Statuses)) {
                throw new ErrorException('Аргумент не объект Statuses');
            }

            if (!is_int($this->owner->id) || empty($this->owner->id)) {
                throw new ErrorException('Id статуса From не integer типа или пуста');
            }

            if (!is_int($statusObj->id) || empty($statusObj->id)) {
                throw new ErrorException('Id статуса To не integer типа или пуста');
            }

            $result = ['error' => 'Удаление не удалось'];

            /**
             * Получаем простую связь
             */
            $statusSimpleLink = $this->getSimpleLinkForStatusFromIdAndStatusToId(
                $this->owner->id,
                $statusObj->id
            );

            if (!is_object($statusSimpleLink)) {
                throw new ErrorException('Ссылка не найдена');
            }

            $isDelete = $statusSimpleLink->delete();

            if ((bool)$isDelete === true) {
                $result = ['success' => 'Ссылка удалена'];
            }
        } catch (ErrorException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Получаем relation_type
     *
     * @return string
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function getRelationType()
    {
        /**
         * @var array $extraWhere
         */
        $extraWhere = (new $this->linkClass)->extraWhere();

        if (array_key_exists('relation_type', $extraWhere)) {
            $relationType = $extraWhere['relation_type'];
        } else {
            $relationType = '';
        }

        return $relationType;
    }

    /**
     * Получаем структуру дерева статусов, для simple links
     *
     * @return array
     */
    public function getTreeWithSimpleLinks()
    {
        return array_map([$this, 'treeBranchWithSimpleLinks'], $this->owner->docType->statusesStructure);
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
        $linkBool = isset($this->owner->statusesTransitionTo[$val->tag]);

        return array_merge(
            [
                'text' => $val->name,
                'href' => '&tagFrom=' . $this->owner->tag . '&tagDoc=' . $val->docType->tag . '&tagTo=' . $val->tag,
            ],
            ($val->tag === $this->owner->tag)
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

    protected function getSimpleLinkForStatusFromIdAndStatusToId($fromStatusId, $toStatusId)
    {
        /**
         * Оъект связи
         */
        $linkClass = $this->linkClass;

        /**
         * Получаем наименования полей
         */
        $linkTo = $linkClass::$_linkTo;
        $linkFrom = $linkClass::$_linkFrom;
        $typeField = $linkClass::$_typeField;

        return $linkClass::find()
            ->where(
                [
                    'and',
                    ['=', $typeField, static::LINK_TYPE_SIMPLE],
                    ['=', $linkFrom, $fromStatusId],
                    ['=', $linkTo, $toStatusId],
                ]
            )
            ->andWhere($linkClass->extraWhere())
            ->one();
    }
}
