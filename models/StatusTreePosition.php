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
use yii\helpers\Url;

class StatusTreePosition extends Model
{
    /**
     * Инициируем класс статуса
     *
     * @return \docflow\models\Statuses
     */
    protected function initStatuses()
    {
        return new Statuses();
    }

    /**
     * Изменяем позицию статуса
     *
     * @param array    $changeArray   - содержит данные для изменения позиции статуса
     * @param Statuses $currentStatus - модель перемещаемого статуса
     *
     * @return array
     */
    protected function changeStatusPositionIinTreeOnUpOrDown($changeArray, $currentStatus)
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
            $return = ['success' => 'Позиция изменена', 'changeName' => $changeStatus->name];
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
    protected function getChangeArrayForActionInTree($currentOrderIdx, $array, $action)
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
    protected function getStructureForUp($array, $position)
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
    protected function getStructureForDown($array, $position)
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
    protected function getStructure($array, $position, $changePosition)
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
}
