<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:15
 */

namespace docflow\behaviors;

use docflow\models\Link;
use docflow\models\Statuses;
use docflow\models\StatusesLinks;
use yii\base\ErrorException;
use yii\helpers\Url;

class LinkStructuredBehavior extends LinkSimpleBehavior
{
    public function attach($owner)
    {
        parent::attach($owner);
    }

    /**
     * Получаем все родительсие статусы
     *
     * @return array
     */
    public function getParents()
    {
        $linkClass = $this->linkClass;

        $parentsLinks = $linkClass::findUpperLinks($this->owner->id)->all();

        $idArray = array_map(
            function ($value) {
                return $value->{$this->linkFieldsArray['status_from']};
            },
            $parentsLinks
        );

        return $this->owner->getStatusesByIdArray($idArray);
    }

    /**
     * Устанавливаем родителя документу
     *
     * @param object $statusObj - Статус
     *
     * @return void
     *
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function setParent($statusObj)
    {
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
        $childes = $this->getChildes()->all();

        if (array_key_exists($statusObj->tag, $childes)) {
            throw new ErrorException('Нельзя устанавливать ребенка родителем');
        }

        /**
         * @var StatusesLinks $flTreeLink
         */
        $flTreeLink = $this->getFlTreeLinkForStatusForLevel1($this->owner->id);

        if (empty($flTreeLink)) {
            $this->prepareAndAddFlTreeLinks($statusObj);
        } else {
            if ($flTreeLink->{$this->linkFieldsArray['status_from']} === $statusObj->id) {
                throw new ErrorException('Id нового статуса родителя совпадает с Id текущего статуса родителя');
            }

            $this->prepareAndUpdateFlTreeLinks($flTreeLink, $statusObj);
        }
    }

    /**
     * Получаем все вложенные статусы
     *
     * @return array
     */
    public function getChildes()
    {
        $linkClass = $this->linkClass;

        $childesLinks = $linkClass::findLowerLinks($this->owner->id)->all();

        $idArray = array_map(
            function ($value) {
                return $value->{$this->linkFieldsArray['status_to']};
            },
            $childesLinks
        );

        return $this->owner->getStatusesByIdArray($idArray);
    }

    /**
     * Добавляем документу вложенный документ
     *
     * @param object $statusObj - Статус
     *
     * @return void
     *
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function setChild($statusObj)
    {
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
        $parents = $this->getParents()->all();

        if (array_key_exists($statusObj->tag, $parents)) {
            throw new ErrorException('Нельзя устанавливать родителя ребенком');
        }

        /**
         * @var StatusesLinks $flTreeLink
         */

        $flTreeLink = $this->getFlTreeLinkForStatusForLevel1($statusObj->id);
        if (empty($flTreeLink)) {
            $this->prepareAndAddFlTreeLinks($statusObj, false);
        } else {
            if ($flTreeLink->{$this->linkFieldsArray['status_from']} === $statusObj->id) {
                throw new ErrorException('Id нового статуса родителя совпадает с Id текущего статуса родителя');
            }

            $this->prepareAndUpdateFlTreeLinks($flTreeLink, $this->owner);
        }
    }


    /**
     * Подготавливаем и добавляем fltree связь
     *
     * @param Statuses $statusObj - Объект статуса
     *
     * @param bool     $parent    - true - создаем родителя, false - ребенка
     */
    protected function prepareAndAddFlTreeLinks($statusObj, $parent = true)
    {

        /**
         * @var Link $statusesLinksClass
         */
        $statusesLinksClass = new $this->linkClass;

        $statusesLinksClass->setScenario(Link::LINK_TYPE_FLTREE);

        if ($parent === true) {
            $statusesLinksClass->{$this->linkFieldsArray['status_from']} = $statusObj->id;
            $statusesLinksClass->{$this->linkFieldsArray['status_to']} = $this->owner->id;
        } else {
            $statusesLinksClass->{$this->linkFieldsArray['status_from']} = $this->owner->id;
            $statusesLinksClass->{$this->linkFieldsArray['status_to']} = $statusObj->id;
        }

        $statusesLinksClass->{$this->linkFieldsArray['type']} = Link::LINK_TYPE_FLTREE;
        $statusesLinksClass->{$this->linkFieldsArray['level']} = 1;

        $relationType = Link::getRelationType();
        if (!empty($relationType) && is_string($relationType)) {
            $statusesLinksClass->{$this->linkFieldsArray['relation_type']} = $relationType;
        }

        $statusesLinksClass->save();
    }

    /**
     * Подготавливаем и обновляем fltree связь
     *
     * @param Link     $flTreeLink - Объект связи типа fltree
     * @param Statuses $statusObj  - Объект статуса
     *
     * @return void
     */
    protected function prepareAndUpdateFlTreeLinks($flTreeLink, $statusObj)
    {
        $flTreeLink->{$this->linkFieldsArray['status_from']} = $statusObj->id;

        $flTreeLink->setScenario(Link::LINK_TYPE_FLTREE);

        $flTreeLink->save();
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

    /**
     * Получаем массив с данными о наличии "детей" - вложенных статусов
     *
     * @param integer $statusId - id Статуса, у которого имем вложенные статусы
     * @param bool    $asArray  - true - выдавать массив, false - объекты
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getChildStatusesForStatus($statusId, $asArray = true)
    {
        $linkClass = $this->linkClass;

        /* Формируем запрос */
        $query = $linkClass::find()
            ->where([
                'and',
                ['=', $this->linkFieldsArray['status_from'], $statusId],
            ]);

        if ($asArray === true) {
            $query->asArray(true);
        }

        $query->andWhere($linkClass::extraWhere());

        return $query->all();
    }

    /**
     * Получаем связь на ближайшего родителя (1 уровень)
     *
     * @param integer $statusId - id статуса
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getFlTreeLinkForStatusForLevel1($statusId)
    {
        $linkClass = $this->linkClass;

        /* Формируем запрос */
        $query = $linkClass::find()
            ->where(
                [
                    'and',
                    ['=', $this->linkFieldsArray['status_to'], $statusId],
                    ['=', $this->linkFieldsArray['level'], 1]
                ]
            );

        $query->andWhere($linkClass::extraWhere());

        return $query->one();
    }

    /**
     * Получаем связи на родителя родителя (1 и 2 уровня)
     *
     * @param integer $statusId - id статуса
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getFlTreeLinkForStatusForLevel1And2($statusId)
    {
        $linkClass = $this->linkClass;

        /* Получаем наименования полей */
        $linkTo = $this->linkFieldsArray['status_to'];
        $level = $this->linkFieldsArray['level'];

        /* Формируем запрос */
        $query = $linkClass::find()
            ->where(
                [
                    'and',
                    ['=', $linkTo, $statusId],
                    ['in', $level, [1, 2]]
                ]
            )
            ->indexBy($level);

        $query->andWhere($linkClass::extraWhere());

        return $query->all();
    }


    /**
     * Получаем детей статуса
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatusChildren()
    {
        $query = $this->owner
            ->hasMany(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksChildren')
            ->inverseOf('statusParent');

        if (!empty($this->orderedField) && is_string($this->orderedField)) {
            $query->orderBy([
                $this->orderedField => SORT_ASC
            ]);
        }

        return $query;
    }


    /**
     * The method returns a list of all links leading to the source statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksFrom()
    {
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $this->linkClass,
                [$this->linkFieldsArray['status_to'] => $this->linkFieldsArray['node_id']]
            )
            ->from($linkClass::tableName() . ' l_from');
    }

    /**
     * The method returns a list of all links leading to the target statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTo()
    {
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $this->linkClass,
                [$this->linkFieldsArray['status_from'] => $this->linkFieldsArray['node_id']]
            )
            ->from($linkClass::tableName() . ' l_to');
    }

    /**
     * The method returns a list of structure links leading to the source statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksStructureFrom()
    {
        /**
         * Получаем наименования полей
         */
        $type = 'l_from.' . $this->linkFieldsArray['type'];

        return $this->getLinksFrom()->andOnCondition([$type => Link::LINK_TYPE_FLTREE]);
    }

    /**
     * The method returns a list of structure links leading to the target statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksStructureTo()
    {
        /**
         * Получаем наименования полей
         */
        $type = 'l_to.' . $this->linkFieldsArray['type'];

        return $this->getLinksTo()->andOnCondition([$type => Link::LINK_TYPE_FLTREE]);
    }

    /**
     * The method returns a list of Transitions links leading to the source statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTransitionsFrom()
    {
        /**
         * Получаем наименования полей
         */
        $type = 'l_from.' . $this->linkFieldsArray['type'];

        return $this->getLinksFrom()->andOnCondition([$type => Link::LINK_TYPE_SIMPLE]);
    }

    /**
     * The method returns a list of Transitions links leading to the target statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksTransitionsTo()
    {
        /**
         * Получаем наименования полей
         */
        $type = 'l_to.' . $this->linkFieldsArray['type'];

        return $this->getLinksTo()->andOnCondition([$type => Link::LINK_TYPE_SIMPLE]);
    }

    /**
     * The method returns a structure link with level=1 leading to the source statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksParent()
    {
        /**
         * Получаем наименования полей
         */
        $level = 'l_from.' . $this->linkFieldsArray['level'];

        $query = $this->getLinksStructureFrom()->andOnCondition([$level => 1]);

        $relationType = Link::getRelationType();

        if (!empty($relationType)) {
            $relType = 'l_from.' . $this->linkFieldsArray['relation_type'];

            $query->andOnCondition([$relType => $relationType]);
        }

        return $query;
    }

    /**
     * The method returns a list of structure links with level=1 leading to the target statuses of the current one
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinksChildren()
    {
        /* Получаем наименования полей */
        $level = 'l_to.' . $this->linkFieldsArray['level'];

        $query = $this->getLinksStructureTo()->andOnCondition([$level => 1]);

        $relationType = Link::getRelationType();

        if (!empty($relationType)) {
            $relType = 'l_to.' . $this->linkFieldsArray['relation_type'];

            $query->andOnCondition([$relType => $relationType]);
        }

        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesTo()
    {
        return $this->owner
            ->hasMany(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTo')
            ->indexBy('tag');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesLower()
    {
        return $this->owner
            ->hasMany(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksStructureTo')
            ->indexBy('tag');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesUpper()
    {
        return $this->owner
            ->hasMany(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksStructureFrom')
            ->indexBy('tag');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesTransitionTo()
    {
        return $this->owner
            ->hasMany(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTransitionsTo')
            ->indexBy('tag');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusesTransitionFrom()
    {
        return $this->owner
            ->hasMany(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTransitionsFrom')
            ->indexBy('tag');
    }

    /**
     * Получаем родителей статуса
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatusParent()
    {
        return $this->owner
            ->hasOne(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksParent');
    }
}
