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
            throw new ErrorException('Текущий документ (owner) пуст');
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException('Документ, у которого получаем простые связи, не содержится в списке доступных документов');
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
            throw new ErrorException('Текущий документ (owner) пуст');
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException('Документ, от которого устанавливаем простую связь, не содержится в списке доступных документов');
        }

        if (count($documentsArray) < 1) {
            throw new ErrorException('Массив с объектами документов пуст');
        }

        /* Проверяем документы содержащиеся в массиве на соответвтвие условиям */
        $this->checkDocumentsArray($documentsArray);

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
     * Проверяем документы в массиве на соответствие:
     * 1)Должны принадлежать классу Document.
     * 2)Документ должен присутствовать с списке разрешенных документов.
     * 3)Нельзя создать связь самому с собой.
     * P.S Разрешенные документы - это документы, которые получаем по запросу,
     * переданным ползователем в поведение при инициализации
     *
     * @param array $documentsArray - массив документов
     *
     * @return void
     *
     * @throws \yii\base\ErrorException
     */
    protected function checkDocumentsArray(array $documentsArray)
    {
        foreach ($documentsArray as $value) {
            if (!($value instanceof Document)) {
                throw new ErrorException('Не все документы, к которым устанавливается простая связь, являются наследником Document');
            }

            if (($value->{$this->linkFieldsArray['node_id']} === null) || !is_int($value->{$this->linkFieldsArray['node_id']})) {
                throw new ErrorException('Документ, к которому устанавливаем простую связь, не содержит данных');
            }

            if ($this->owner->{$this->linkFieldsArray['node_id']} === $value->{$this->linkFieldsArray['node_id']}) {
                throw new ErrorException('Нельзя назначить связь на себя');
            }

            if (!array_key_exists($value->tag, $this->getAvailableDocuments())) {
                throw new ErrorException('В списке присутствует документ, который не входит в список разрешенных');
            }
        }
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
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, от которого устанавливаем простую связь, не содержит данных');
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException('Документ, от которого устанавливаем простую связь, не содержится в списке доступных документов');
        }

        if (!($documentObj instanceof Document)) {
            throw new ErrorException('Документ, к которому устанавливаем простую связь, не является наследником Document');
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, к которому устанавливаем простую связь, не содержит данных');
        }

        if (!array_key_exists($documentObj->tag, $this->getAvailableDocuments())) {
            throw new ErrorException('Документ, к которому устанавливаем простую связь, не содержится в списке доступных документов');
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
        $statusLinkClass->{$this->linkFieldsArray['right_tag']} = $this->owner->docTag() . '.' . $this->owner->tag . '.' . $documentObj->tag;

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
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, от которого удаляем простую связь, не содержит данных');
        }

        if (!array_key_exists($this->owner->tag, $this->getAvailableDocuments())) {
            throw new ErrorException('Документ, от которого удаляем простую связь, не содержится в списке доступных документов');
        }

        if (!($documentObj instanceof Document)) {
            throw new ErrorException('Передаваемый документ не является наследником Document');
        }

        if (($documentObj->{$this->linkFieldsArray['node_id']} === null) || !is_int($documentObj->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException('Документ, к которому удаляем простую связь, не содержит данных');
        }

        if (!array_key_exists($documentObj->tag, $this->getAvailableDocuments())) {
            throw new ErrorException('Документ, к которому удаляем простую связь, не содержится в списке доступных документов');
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
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_to']]
            )
            ->via('linksTo', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere());
            })
            ->indexBy('tag');
    }

    /**
     * Получаем документы, которые записаны в поле status_from
     *
     * @return ActiveQuery
     */
    public function getStatusesTransitionFrom()
    {
        $owner = $this->owner;
        /* Передаем название класса связи в переменную ради удобства */
        $linkClass = $this->linkClass;

        return $this->owner
            ->hasMany(
                $owner::className(),
                [$this->linkFieldsArray['node_id'] => $this->linkFieldsArray['status_from']]
            )
            ->via('linksFrom', function (ActiveQuery $query) use ($linkClass) {
                $query->andOnCondition($linkClass::extraWhere());
            })
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
