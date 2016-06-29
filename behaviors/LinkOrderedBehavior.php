<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 16.06.16
 * Time: 12:16
 */

namespace docflow\behaviors;

use docflow\models\Document;
use docflow\models\Statuses;
use yii;
use yii\base\ErrorException;
use yii\base\InvalidParamException;

class LinkOrderedBehavior extends LinkStructuredBehavior
{
    /**
     * Повышаем позицию статуса в уровне вложенности на более высокую позицию
     *
     * @return array
     */
    public function orderUp()
    {
        return $this->setStatusInTreeVertical('Up');
    }

    /**
     * Опускаем позицию статуса в уровне вложенности на более низкую позицию
     *
     * @return array
     */
    public function orderDown()
    {
        return $this->setStatusInTreeVertical('Down');
    }

    /**
     * Повышаем уровень вхождения
     *
     * @return array
     */
    public function levelUp()
    {
        return $this->setStatusInTreeHorizontal('Right');
    }

    /**
     * Понижаем уровень вхождения
     *
     * @return array
     */
    public function levelDown()
    {
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
                'current' => $this->owner->{$this->orderedField},
                'change' => $changeDocument->{$this->orderedField},
            ];
            /* Меняем положения */
            $this->owner->setAttribute($this->orderedField, $array['change']);
            $changeDocument->setAttribute($this->orderedField, $array['current']);

            if ((!$changeDocument->save()) || (!$this->owner->save())) {
                throw new ErrorException('Позиция не изменена');
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
            $documentsWithParentLinks = $this->getDocumentsWithFlTreeLinks1Level($this->owner->doc_type_id)->all();

            /* Получаем все документы, находящиеся на одном уровне с текущим документом, включая текущий */
            $documentsOnLevel = $this->getDocumentsWithLevel($documentsWithParentLinks);

            /* Получаем документ, с которым будет производиться обмен позиций */
            $changeDocument = $this->getChangeDocument($documentsOnLevel, $actionInTree);

            if ($changeDocument === null) {
                throw new ErrorException('Позиция не может быть изменена');
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
        /**
         * @var array $parentLink
         */
        $parentLink = $documents[$this->owner->tag]->linksParent;

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
                    if ($var->linksParent === []) {
                        return $var;
                    }
                } else {
                    if (($var->linksParent !== []) && ($var->linksParent[0][$this->linkFieldsArray['status_from']] === $currentDocumentParent)) {
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

        /**
         * @var Statuses|Document $value
         */
        foreach ($docsOnLevel as $value) {
            if ($value->{$this->orderedField} < $this->owner->{$this->orderedField}) {
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

        /**
         * @var Statuses|Document $value
         */
        foreach ($docsOnLevel as $value) {
            /* Если нашли документ на сменту, то выходим из перебора */
            if ($value->{$this->orderedField} > $this->owner->{$this->orderedField}) {
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
                    $parentLinks = $this->owner->parentLinks1And2Levels;

                    /* Получаем родительские документы 1 и 2 уровней */
                    $parentDocuments = $this->owner->parentDocuments1And2Levels;

                    $return = $this->setStatusInTreeLeft($parentDocuments, $parentLinks);
                    break;
                case 'Right':
                    /* Получаем все документы с ближайшей родительский связью */
                    $documentsWithParentLinks = $this->getDocumentsWithFlTreeLinks1Level($this->owner->doc_type_id)->all();

                    /* Получаем все документы, находящиеся на одном уровне с текущим документом, включая текущий */
                    $documentsOnLevel = $this->getDocumentsWithLevel($documentsWithParentLinks);

                    /* Получаем новый родительский документ, для перемещаемого */
                    $newRootDocument = $this->getUpDocument($documentsOnLevel);

                    if ($newRootDocument === null) {
                        throw new ErrorException('Позиция не может быть изменена');
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
     */
    protected function setStatusInTreeRight($newRootDocument)
    {
        $this->owner->setParent($newRootDocument);

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
     */
    protected function setStatusInTreeLeft(array $parentDocuments, array $parentLinks)
    {
        if (array_key_exists(2, $parentLinks)) {
            /* Меняем родителя */
            $documentId = $parentLinks[2]->{$this->linkFieldsArray['status_from']};
            $this->owner->setParent($parentDocuments[$documentId]);

            $return = ['success' => 'Позиция изменена'];
        } elseif (array_key_exists(1, $parentLinks)) {
            /* Удаляем родителей */
            $this->owner->removeParents();

            $return = ['success' => 'Позиция изменена'];
        } else {
            $return = ['error' => 'Перемещение невозможно'];
        }

        return $return;
    }
}
