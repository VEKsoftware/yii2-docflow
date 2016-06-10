<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 03.06.16
 * Time: 12:30
 */

namespace docflow\models;

use docflow\Docflow;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\di\Instance;
use yii\helpers\Url;

class StatusTreePosition extends Model
{
    const LINK_TYPE_SIMPLE = 'simple';

    const LINK_TYPE_FLTREE = 'fltree';

    /**
     * Инициируем класс статуса
     *
     * @return \docflow\models\Statuses
     */
    protected function initStatuses()
    {
        return Instance::ensure([], Statuses::className());
    }

    /**
     * Инициируем класс ссылок статусов
     *
     * @return \docflow\models\StatusesLinks
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function initStatusesLinks()
    {
        return Instance::ensure([], StatusesLinks::className());
    }

    /**
     * Изменяем позицию статуса
     *
     * @param array    $changeArray   - содержит данные для изменения позиции статуса
     * @param Statuses $currentStatus - модель перемещаемого статуса
     *
     * @return array
     */
    protected function changeStatusPositionIinTreeOnUpOrDown(array $changeArray, $currentStatus)
    {
        $statusClass = $this->initStatuses();

        // Получаем статус, c  которым поменяемся местами
        $changeStatus = $statusClass->getStatusForTag($changeArray['change']['tag']);

        try {
            // Изменяем им положения
            $currentStatus->setAttribute('order_idx', $changeArray['change']['orderIdx']);
            $changeStatus->setAttribute('order_idx', $changeArray['current']['orderIdx']);

            $docflow = Docflow::getInstance();
            // Сохраняем изменения через транзакцию
            Yii::$app->{$docflow->db}->transaction(function () use ($currentStatus, $changeStatus) {
                $currentResult = $currentStatus->save();
                $changeResult = $changeStatus->save();

                if (($currentResult === false) || ($changeResult === false)) {
                    throw new ErrorException('Позиция не изменена');
                }
            });
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
     * @param string $statusTag    - тэг перемещаемого статуса
     * @param string $actionInTree - Up или Down
     *
     * @return array
     */
    public function setStatusInTreeVertical($statusTag, $actionInTree)
    {
        $currentStatus = $this->initStatuses();

        $currentStatusArray = $currentStatus->getStatusForTag($statusTag, true);
        $currentStatus->setAttributes($currentStatusArray, false);
        $currentStatus->setIsNewRecord(false);

        $orderIdxInLevelArray = $currentStatus->getStatusesForLevel(
            $currentStatusArray['fromId'],
            $currentStatusArray['level'],
            $currentStatusArray['doc_type_id']
        );

        $changeArray = $this->getChangeArrayForActionInTree(
            $currentStatusArray['order_idx'],
            $orderIdxInLevelArray,
            $actionInTree
        );

        $result = ['error' => 'Позиция не может быть изменена'];

        if (count($changeArray) !== 0) {
            $result = $this->changeStatusPositionIinTreeOnUpOrDown($changeArray, $currentStatus);
        }

        return $result;
    }

    /**
     * Получаем структуру, необходимую для изменения положения статуса для действия
     *
     * @param integer $currentOrderIdx - номер положения перемещаемого статуса
     * @param array   $array           - массив, содержащий статусы находящиеся на одном уровне с перемещаемым статусом
     * @param string  $action          - содержит совершаемое действие
     *
     * @return array
     */
    protected function getChangeArrayForActionInTree($currentOrderIdx, array $array, $action)
    {
        try {
            $position = $this->checkPositionInTreeArray($currentOrderIdx, $array);

            if ($position === '') {
                throw new ErrorException('Не найдена позиция');
            }

            $return = [];

            switch ($action) {
                case 'Up':
                    $return = $this->getStructureForUp($array, $position);
                    break;
                case 'Down':
                    $return = $this->getStructureForDown($array, $position);
                    break;
            }
        } catch (ErrorException $e) {
            $return = [];
        }

        return $return;
    }

    /**
     * Находим позицию в массиве
     *
     * @param integer $currentOrderIdx - номер положения перемещаемого статуса
     * @param array   $array           - массив, содержащий статусы находящиеся на одном уровне с перемещаемым статусом
     *
     * @return int|string
     */
    protected function checkPositionInTreeArray($currentOrderIdx, $array)
    {
        $position = '';
        //TODO переписать
        foreach ($array as $key => $value) {
            if ((string)$value['orderIdx'] === (string)$currentOrderIdx) {
                $position = $key;
                break;
            }
        }

        return $position;
    }

    /**
     * Получаем массив с данными, необходимыми для поднятия статуса в дереве
     *
     * @param array   $array    - массив, содержащий статусы находящиеся на одном уровне с перемещаемым статусом
     * @param integer $position - позиция перемещаемого статуса в массиве $array
     *
     * @return array
     */
    protected function getStructureForUp(array $array, $position)
    {
        $changePosition = ($position - 1);

        $return = [];

        if ($position > 0) {
            $return = $this->getStructure($array, $position, $changePosition);
        }

        return $return;
    }

    /**
     * Получаем массив с данными, необходимыми для понижения статуса в дереве
     *
     * @param array   $array    - массив, содержащий статусы находящиеся на одном уровне с перемещаемым статусом
     * @param integer $position - позиция перемещаемого статуса в массиве $array
     *
     * @return array
     */
    protected function getStructureForDown(array $array, $position)
    {
        $changePosition = ($position + 1);

        $return = [];

        if ($position < (count($array) - 1)) {
            $return = $this->getStructure($array, $position, $changePosition);
        }

        return $return;
    }

    /**
     * Формируем структуру необходимую для изменения полложения статуса
     *
     * @param array   $array          - массив, содержащий статусы находящиеся на одном уровне с перемещаемым статусом
     * @param integer $position       - позиция перемещаемого статуса в массиве $array
     * @param integer $changePosition - позиция статуса (на который переместится перемещаемый статус) в массиве $array
     *
     * @return array
     */
    protected function getStructure(array $array, $position, $changePosition)
    {
        return [
            'current' => [
                'orderIdx' => $array[$position]['orderIdx'],
                'tag' => $array[$position]['tag']
            ],
            'change' => [
                'orderIdx' => $array[$changePosition]['orderIdx'],
                'tag' => $array[$changePosition]['tag']
            ]
        ];
    }

    /**
     * Получаем структуру дерева
     *
     * @param array $rawStructure - сырые данные
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
     * Перемещение статуса во внутрь(вложенный уровень) другого статуса или вынесение из вложенного уровня во внешний
     *
     * @param string $statusTag    - Тэг статуса
     * @param string $docTag       - Тэг документа
     * @param string $actionInTree - действие Right - во внутренний уровень, действие Left - во внешний уровень
     *
     * @return array
     */
    public function setStatusInTreeHorizontal($statusTag, $docTag, $actionInTree)
    {
        $statusesClass = $this->initStatuses();
        $statusesLinksClass = $this->initStatusesLinks();

        $return = [];

        $statusArray = $statusesClass->getStatusForTag($statusTag, true);

        switch ($actionInTree) {
            case 'Left':
                $flTreeLinks = $statusesLinksClass->getFlTreeLinkForStatusForLevel1And2($statusArray['id']);
                $return = $this->setStatusInTreeLeft($flTreeLinks);
                break;
            case 'Right':
                $statusLinksArray = $statusesClass->getStatusesForLevel(
                    $statusArray['fromId'],
                    $statusArray['level'],
                    $statusArray['doc_type_id']
                );
                $flTreeLink = $statusesLinksClass->getFlTreeLinkForStatusForLevel1($statusArray['id']);
                $paramsNewLink = [
                    'status_to' => $statusArray['id'],
                    'doc_tag' => $docTag,
                    'to_tag' => $statusArray['tag'],
                    'type' => 'fltree',
                    'level' => 1,
                ];
                $return = $this->setStatusInTreeRight($paramsNewLink, $statusLinksArray, $flTreeLink);
                break;
        }

        return $return;
    }

    /**
     * Перемещаем статус внутрь уровня верхлежащего статуса
     *
     * @param array                               $params           - массив с данными для перемещения перемещаемого статуса
     * @param array                               $statusLinksArray - массив с данными о статусах на одном (1-ом) уровне с перемещаемым статусом
     * @param \docflow\models\StatusesLinks|array $flTreeLink       - ссылка 1-го уровня для перемещаемого статуса
     *
     * @return array
     */
    protected function setStatusInTreeRight(array $params, array  $statusLinksArray, $flTreeLink)
    {
        try {
            if (count($statusLinksArray) < 2) {
                throw new ErrorException('Переход невозможен');
            }

            if ($statusLinksArray[0]['id'] === $params['status_to']) {
                throw new ErrorException('Переход невозможен');
            }

            $valueFrom = $this->getStatusFrom($params['status_to'], $statusLinksArray);

            if (empty($flTreeLink)) {
                $newStatusLink = $this->initStatusesLinks();

                $newStatusLink->setScenario(static::LINK_TYPE_FLTREE);

                $newStatusLink->status_from = $valueFrom['id'];
                $newStatusLink->status_to = $params['status_to'];
                $newStatusLink->type = $params['type'];
                $newStatusLink->level = $params['level'];

                $result = $newStatusLink->save();
            } else {
                $flTreeLink->setScenario(static::LINK_TYPE_FLTREE);
                $flTreeLink->status_from = $valueFrom['id'];

                $result = $flTreeLink->save();
            }

            $return = $this->moveResult($result);
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Возвращяем сообщение в зависимости от результата
     *
     * @param bool $result - результат перемещения
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
     * Получаем массив с данными о статусе в который перемещается перемещаемый статус
     *
     * @param integer $id               - id перемещаемого статуса
     * @param array   $statusLinksArray - массив с данными о статусах на одном (1-ом) уровне с перемещаемым статусом
     *
     * @return array
     */
    protected function getStatusFrom($id, array $statusLinksArray)
    {
        $oldValue = [];
        //TODO переписать
        foreach ($statusLinksArray as $value) {
            if ($value['id'] === $id) {
                break;
            }

            $oldValue = $value;
        }

        return $oldValue;
    }

    /**
     * Перемещаем статус из внутренного уровня во внешний
     *
     * @param array $flTreeLinks - массив с ссылками 1 и 2 уровня перемещаемого статуса
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function setStatusInTreeLeft(array $flTreeLinks)
    {
        if (array_key_exists(2, $flTreeLinks)) {
            /**
             * @var StatusesLinks $link
             */
            $link = $flTreeLinks[1];

            $link->status_from = $flTreeLinks[2]['status_from'];
            $link->setScenario(static::LINK_TYPE_FLTREE);

            $result = $link->save();
        } elseif (array_key_exists(1, $flTreeLinks)) {
            /**
             * @var StatusesLinks $link
             */
            $link = $flTreeLinks[1];
            $result = (bool)$link->delete();
        } else {
            $result = false;
        }

        return $this->moveResult($result);
    }
}
