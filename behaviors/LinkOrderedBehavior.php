<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:16
 *
 * Поведение предназначено для работы с fltree связями в графическом виде
 * Подключается только к классу - наследнику Documents
 *
 * Обязательные параметры:
 * 1)linkClass - полное имя класса связи
 * 2)documentQuery - callback, содержащий ActiveQuery запрос на получение документов
 *
 * Не обязательные параметры:
 * 1)orderedField  - поле, по которому будет идти упорядочивание
 * 2)indexBy - поле, по которому будет идти индексирование
 *
 * Методы:
 * 1)getDocuments() - получаем документы по переданному в поведение запросу
 * 2)orderUp() - повышаем позицию статуса в уровне вложенности на более высокую позицию
 * 3)orderDown() - опускаем позицию статуса в уровне вложенности на более низкую позицию
 * 4)levelUp() - повышаем уровень вхождения
 * 5)levelDown() - понижаем уровень вхождения
 *
 *
 * Behavior is designed to work with fltree links in graphical form
 * It connects only to the class - successor Documents
 *
 * Required parameters:
 * 1)linkClass - the full name of the node class
 * 2)documentQuery - callback, containing ActiveQuery request for documents
 *
 * Optional parameters:
 * 1)orderedField  - field on which will go ordering
 * 2)indexBy - field on which will go Indexed
 *
 * Methods:
 * 1)getDocuments() - obtain the documents transmitted to the behavior of the request
 * 2)orderUp() - raise the status of the position in the nesting level to a higher position
 * 3)orderDown() - omit status position in the nesting level to a lower position
 * 4)levelUp() - raise the level of entry
 * 5)levelDown() - lowers entry
 */

namespace docflow\behaviors;

use docflow\messages\behaviors\BehaviorsMessages;
use docflow\models\Document;
use docflow\models\Statuses;
use yii;
use yii\base\ErrorException;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;

class LinkOrderedBehavior extends LinkStructuredBehavior
{
    /**
     * Повышаем позицию статуса в уровне вложенности на более высокую позицию
     *
     * @return array
     *
     * @throws ErrorException
     */
    public function orderUp()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::ORD_DOCUMENT_ORDER_UP_NOT_HAS_AVAILABLE);
        }

        return $this->setStatusInTreeVertical('Up');
    }

    /**
     * Опускаем позицию статуса в уровне вложенности на более низкую позицию
     *
     * @return array
     *
     * @throws ErrorException
     */
    public function orderDown()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::ORD_DOCUMENT_ORDER_DOWN_NOT_HAS_AVAILABLE);
        }

        return $this->setStatusInTreeVertical('Down');
    }

    /**
     * Повышаем уровень вхождения
     *
     * @return array
     *
     * @throws ErrorException
     */
    public function levelUp()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::ORD_DOCUMENT_LEVEL_UP_NOT_HAS_AVAILABLE);
        }

        return $this->setStatusInTreeHorizontal('Right');
    }

    /**
     * Понижаем уровень вхождения
     *
     * @return array
     *
     * @throws ErrorException
     */
    public function levelDown()
    {
        if (($this->owner->{$this->linkFieldsArray['node_id']} === null) || !is_int($this->owner->{$this->linkFieldsArray['node_id']})) {
            throw new ErrorException(BehaviorsMessages::U_OWNER_ID_NULL_OR_NOT_INT);
        }

        if (!array_key_exists($this->owner->{$this->indexBy}, $this->documents)) {
            throw new ErrorException(BehaviorsMessages::ORD_DOCUMENT_LEVEL_DOWN_NOT_HAS_AVAILABLE);
        }

        return $this->setStatusInTreeHorizontal('Left');
    }

    /**
     * Изменяем позицию документа
     *
     * @param Statuses|Document $changeDocument - документ, с которым будет происходить обмен позиций
     *
     * @return array
     */
    protected function changeStatusPositionIinTreeOnUpOrDown($changeDocument)
    {
        try {
            /* Массив, содержащий позицию текущего документа и с которым будет произведен обмен местами */
            $array = [
                'current' => $this->owner->{$this->orderedFieldValue},
                'change' => $changeDocument->{$this->orderedFieldValue},
            ];
            /* Меняем положения */
            $this->owner->{$this->orderedFieldValue} = $array['change'];
            $changeDocument->{$this->orderedFieldValue} = $array['current'];

            if ((!$changeDocument->save()) || (!$this->owner->save())) {
                throw new ErrorException(BehaviorsMessages::ORD_POSITION_NOT_CHANGE);
            }

            $return = ['success' => 'Позиция изменена'];
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        } catch (InvalidParamException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Перемещаем Статус вертикально в зависимости от $actionInTree
     *
     * @param string $actionInTree - Up или Down
     *
     * @return array
     */
    protected function setStatusInTreeVertical($actionInTree)
    {
        try {
            /* Получаем все документы с ближайшей родительский связью */
            $documentsWithParentLinks = $this->documentsWithFlTreeLinks1Level;

            /* Получаем все документы, находящиеся на одном уровне с текущим документом, включая текущий */
            $documentsOnLevel = $this->getDocumentsWithLevel($documentsWithParentLinks);

            /* Получаем документ, с которым будет производиться обмен позиций */
            $changeDocument = $this->getChangeDocument($documentsOnLevel, $actionInTree);

            if ($changeDocument === null) {
                throw new ErrorException(BehaviorsMessages::ORD_POSITION_CAN_NOT_CHANGE);
            }

            $result = $this->changeStatusPositionIinTreeOnUpOrDown($changeDocument);
        } catch (ErrorException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Получаем документы находящиеся на одном уровне с текущим документом
     *
     * @param array $documents - массив с документами
     *
     * @return array
     */
    protected function getDocumentsWithLevel(array $documents)
    {
        /* @var array $parentLink */
        $parentLink = $documents[$this->owner->{$this->indexBy}]->linksFrom;

        $currentDocumentParent = null;

        if (count($parentLink) > 0) {
            $currentDocumentParent = $parentLink[0][$this->linkFieldsArray['status_from']];
        }

        /**
         * Выбираем статусы из массива:
         * 1)Если родительской связи нет, то все документы без родительской связи
         * 2)Если родительская связь есть, то все документы с общим родителем
         */
        $statusesList = array_filter(
            $documents,
            function ($var) use ($currentDocumentParent) {
                if ($currentDocumentParent === null) {
                    if ($var->linksFrom === []) {
                        return $var;
                    }
                } else {
                    if (($var->linksFrom !== []) && ($var->linksFrom[0][$this->linkFieldsArray['status_from']] === $currentDocumentParent)) {
                        return $var;
                    }
                }
            }
        );

        return $statusesList;
    }

    /**
     * Получаем документ, с которым будет произведен обмент позициями по вертикали
     *
     * @param array  $docsOnLevel - отсортированный (от меньшего к большему )по сортировочному полю массив,
     *                            содержит документы находящиеся на одном уровне с текущим(включая его самого)
     * @param string $action      - действие: Up - переместить выше, Down - переместить ниже
     *
     * @return Document|Statuses|null
     */
    protected function getChangeDocument(array $docsOnLevel, $action)
    {
        $return = null;

        ArrayHelper::multisort($docsOnLevel, $this->orderedFieldValue);

        if ($action === 'Up') {
            $return = $this->getUpDocument($docsOnLevel);
        }

        if ($action === 'Down') {
            $return = $this->getDownDocument($docsOnLevel);
        }

        return $return;
    }

    /**
     * Получаем документ, находящийся выше текущего
     *
     * @param array $docsOnLevel - отсортированный (от меньшего к большему )по сортировочному полю массив,
     *                           содержит документы находящиеся на одном уровне с текущим(включая его самого)
     *
     * @return Document|Statuses|null
     */
    protected function getUpDocument(array $docsOnLevel)
    {
        $document = null;

        /* @var Document $value */
        foreach ($docsOnLevel as $value) {
            /* Если нашли документ на сменту, то выходим из перебора */
            if ($value->{$this->orderedFieldValue} < $this->owner->{$this->orderedFieldValue}) {
                $document = $value;
            }
        }

        return $document;
    }

    /**
     * Получаем документ, находящийся ниже текущего
     *
     * @param array $docsOnLevel - отсортированный (от меньшего к большему) по сортировочному полю массив,
     *                           содержит документы находящиеся на одном уровне с текущим(включая его самого)
     *
     * @return Document|Statuses|null
     */
    protected function getDownDocument(array $docsOnLevel)
    {
        $document = null;

        /* @var Document $value */
        foreach ($docsOnLevel as $value) {
            /* Если нашли документ на сменту, то выходим из перебора */
            if ($value->{$this->orderedFieldValue} > $this->owner->{$this->orderedFieldValue}) {
                $document = $value;
                break;
            }
        }

        return $document;
    }

    /**
     * Перемещение документа:
     * 1)Right - во внутрь(вложенный уровень) другого документа
     * 2)Left - вынесение из вложенного уровня во внешний
     *
     * @param string $actionInTree - действие: Right - во внутренний уровень, Left - во внешний уровень
     *
     * @return array
     */
    protected function setStatusInTreeHorizontal($actionInTree)
    {
        $return = [];
        try {
            switch ($actionInTree) {
                case 'Left':
                    /* Получаем родительские связи 1 и 2 уровней */
                    $parentLinks = $this->parentLinks1And2Levels;

                    /* Получаем родительские документы 1 и 2 уровней */
                    $parentDocuments = $this->parentDocuments1And2Levels;

                    $return = $this->setStatusInTreeLeft($parentDocuments, $parentLinks);
                    break;
                case 'Right':
                    /* Получаем все документы с ближайшей родительский связью */
                    $documentsWithParentLinks = $this->documentsWithFlTreeLinks1Level;

                    /* Получаем все документы, находящиеся на одном уровне с текущим документом, включая текущий */
                    $documentsOnLevel = $this->getDocumentsWithLevel($documentsWithParentLinks);

                    /* Получаем новый родительский документ, для перемещаемого */
                    $newRootDocument = $this->getUpDocument($documentsOnLevel);

                    if ($newRootDocument === null) {
                        throw new ErrorException(BehaviorsMessages::ORD_POSITION_CAN_NOT_CHANGE);
                    }

                    $return = $this->setStatusInTreeRight($newRootDocument);
                    break;
            }
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Перемещаем статус внутрь уровня верхлежащего статуса
     *
     * @param Document|Statuses $newRootDocument - документ, в который перемещаем
     *
     * @return array
     *
     * @throws ErrorException
     */
    protected function setStatusInTreeRight($newRootDocument)
    {
        $this->setParent($newRootDocument);

        return ['success' => 'Позиция изменена'];
    }

    /**
     * Перемещаем статус из внутренного уровня во внешний:
     * 1)Если у документа есть родительская связь 2 уровня, то меняем текущего родителя на родителя 2 уровня
     * 2)Если у документа нет родительской связи 2 уровня, но есть 1-го, то удляем родительскую свзь (переходим в корень)
     * 3)Если у документа нет родительской связи 2 и 1 уровней, то смена позиции невозможна, т.к текущая позиция - корень
     *
     * @param array $parentDocuments - родительские документы 1 и 2 уровней
     * @param array $parentLinks     - родительские связи 1 и 2 уровней
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function setStatusInTreeLeft(array $parentDocuments, array $parentLinks)
    {
        if (array_key_exists(2, $parentLinks)) {
            /* Меняем родителя */
            $documentId = $parentLinks[2]->{$this->linkFieldsArray['status_from']};
            $this->setParent($parentDocuments[$documentId]);

            $return = ['success' => 'Позиция изменена'];
        } elseif (array_key_exists(1, $parentLinks)) {
            /* Удаляем родителей */
            $this->removeParents();

            $return = ['success' => 'Позиция изменена'];
        } else {
            $return = ['error' => 'Перемещение невозможно'];
        }

        return $return;
    }
}
