<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:15
 */

namespace docflow\behaviors;

use docflow\Docflow;
use docflow\models\Document;
use docflow\models\Link;
use docflow\models\Statuses;
use yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\db\Transaction;

class LinkSimpleBehavior extends LinkBaseBehavior
{
    /**
     * Получаем все простые связи для данного документа
     *
     * @return ActiveQuery
     *
     * @throws ErrorException
     */
    public function getSimpleLinks()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Текущий документ не определен');
        }

        return $this->getLinksTransitionsTo();
    }

    /**
     * Массово устанавливаем простые связи между текущим документом (owner) и
     * документами содержащимися в массиве, передаваемом через аргумент.
     *
     * @param array $documentsArray - массив содержит объекты документов,
     *                              к которым будет установлена простая связь от текущего документа
     *
     * @return array
     *
     * @throws ErrorException
     * @throws Exception
     */
    public function setSimpleLinks(array $documentsArray)
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Текущий документ (owner) не определен');
        }

        if (count($documentsArray) < 1) {
            throw new ErrorException('Массив с объектами документов пуст');
        }

        $return = ['success' => 'Простые связи установлены'];

        /* Назначаем название класса связи на переменную ради удобства */
        $linkClass = $this->linkClass;

        /**
         * @var $transaction Transaction
         */
        $transaction = Yii::$app->{Docflow::getInstance()->db}->beginTransaction();

        try {
            /* Удаляем все текущие простые связи */
            $linkClass::batchDeleteSimpleLinks($this->owner->{$this->linkFieldsArray['node_id']});

            /* Массово добавляем новые простые связи */
            $linkClass::batchAddSimpleLinks($this->owner, $documentsArray);

            $transaction->commit();
        } catch (\Exception $e) {
            $return = ['error' => $e->getMessage()];
            $transaction->rollBack();
        }

        return $return;
    }

    /**
     * Добавляем простую связь между текущим документом (owner) и документом передаваемом в аргументе
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidConfigException
     */
    public function addSimpleLink($documentObj)
    {
        if (!($documentObj instanceof Document)) {
            throw new ErrorException('Передаваемый документ не является наследником Document');
        }

        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, от которого устанавливаем простую связь, не содержит данных');
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, к которому устанавливаем простую связь, не содержит данных');
        }

        if ($this->owner->{$this->linkFieldsArray['node_id']} === $documentObj->{$this->linkFieldsArray['node_id']}) {
            throw new ErrorException('Нельзя назначить связь на себя');
        }

        $result = ['error' => 'Добавление простой связи не удалось'];

        /* Проверяем, есть ли в БД уже такая связь */
        $statusSimpleLink = $this->getSimpleLinkByDocument($documentObj)->one();

        if (is_object($statusSimpleLink)) {
            throw new ErrorException('Простая связь уже существует');
        }

        /* Сохраняем простую связь */
        $isSave = $this->prepareAndAddSimpleLink($documentObj);

        if ($isSave === true) {
            $result = ['success' => 'Простая связь успешно добавлена'];
        }

        return $result;
    }

    /**
     * Подготавливаем и добавляем простую связь
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return bool
     */
    protected function prepareAndAddSimpleLink($documentObj)
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        $relationType = $linkClass::getRelationType();

        /**
         * @var Link $statusLinkClass
         */
        $statusLinkClass = new $linkClass;
        $statusLinkClass->setScenario($linkClass::LINK_TYPE_SIMPLE);

        $statusLinkClass->{$this->linkFieldsArray['status_from']} = $this->owner->{$this->linkFieldsArray['node_id']};
        $statusLinkClass->{$this->linkFieldsArray['status_to']} = $documentObj->{$this->linkFieldsArray['node_id']};
        $statusLinkClass->{$this->linkFieldsArray['type']} = $linkClass::LINK_TYPE_SIMPLE;
        $statusLinkClass->{$this->linkFieldsArray['right_tag']} = $this->owner->docType->tag . '.' . $this->owner->tag . '.' . $documentObj->tag;

        if (!empty($relationType) && is_string($relationType)) {
            $statusLinkClass->{$this->linkFieldsArray['relation_type']} = $relationType;
        }

        return $statusLinkClass->save();
    }

    /**
     * Удаляем простую связь между текущим документом (owner) и документом передаваемом в аргументе
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return array
     *
     * @throws StaleObjectException
     * @throws \Exception
     * @throws ErrorException
     */
    public function delSimpleLink($documentObj)
    {
        if (!($documentObj instanceof Document)) {
            throw new ErrorException('Передаваемый документ не является наследником Document');
        }

        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, от которого удаляем простую связь, не содержит данных');
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, к которому удаляем простую связь, не содержит данных');
        }

        $result = ['error' => 'Просатя связь не удалена'];

        /* Проверяем, есть ли в БД уже такая связь */
        $statusSimpleLink = $this->getSimpleLinkByDocument($documentObj)->one();

        if (!is_object($statusSimpleLink)) {
            throw new ErrorException('Простая связь не найдена');
        }

        /* Удаляем простую связь */
        $isDelete = $statusSimpleLink->delete();

        if ((bool)$isDelete === true) {
            $result = ['success' => 'Простая связь удалена'];
        }

        return $result;
    }

    /**
     * Получаем простую связь между текущим документом (owner) и документом передаваемом в аргументе
     *
     * @param Statuses|Document $documentObj - Объект документа
     *
     * @return ActiveQuery
     */
    public function getSimpleLinkByDocument($documentObj)
    {
        return $this->getLinksTransitionsTo()
            ->andWhere(
                ['=', $this->linkFieldsArray['status_to'], $documentObj->{$this->linkFieldsArray['node_id']}]
            );
    }

    /**
     * Получаем документы, которые записаны в поле status_to
     *
     * @return ActiveQuery
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
     * Получаем документы, которые записаны в поле status_from
     *
     * @return ActiveQuery
     */
    public function getStatusesTransitionFrom()
    {
        return $this->owner
            ->hasMany(
                $this->owner->currentName,
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksTransitionsFrom')
            ->indexBy('tag');
    }

    /**
     * The method returns a list of Transitions links leading to the source statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksTransitionsFrom()
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksFrom()
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * The method returns a list of Transitions links leading to the target statuses of the current one
     *
     * @return ActiveQuery
     */
    public function getLinksTransitionsTo()
    {
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->getLinksTo()
            ->andOnCondition($linkClass::extraWhere());
    }

    /**
     * Находим простые связи между текущим документом и документами, переданными в аргумент массивом
     *
     * @param array $list - список документов вложенных в корневой статус
     *
     * @return ActiveQuery
     */
    public function getSimpleLinksByList(array $list)
    {
        return $this->getLinksTransitionsTo()
            ->andWhere(['in', $this->linkFieldsArray['status_to'], $list]);
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
}
