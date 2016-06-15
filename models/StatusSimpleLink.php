<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 10.06.16
 * Time: 11:17
 */

namespace docflow\models;

use yii\base\ErrorException;
use yii\base\Model;
use yii\di\Instance;

class StatusSimpleLink extends Model
{

    /**
     * Инициируем класс StatusesLinks
     *
     * @return StatusesLinks
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function initStatusesLinks()
    {
        return Instance::ensure([], StatusesLinks::className());
    }

    /**
     * Инициируем класс статуса
     *
     * @return \docflow\models\Statuses
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function initStatuses()
    {
        return Instance::ensure([], Statuses::className());
    }

    /**
     * Добавляем SimpleLink
     *
     * @param string $docTag  - тэг документа
     * @param string $fromTag - тэг статуса From
     * @param string $toTag   - тэг статуса To
     *
     * @return array - ['error' => .....] or ['success' => .....]
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function addSimpleLink($docTag, $fromTag, $toTag)
    {
        $statusLinkClass = $this->initStatusesLinks();
        $statusClass = $this->initStatuses();

        try {
            $result = ['error' => 'Добавление не удалось'];

            if (!is_string($docTag)) {
                throw new ErrorException('Тэг документа не строкового типа');
            }

            if (!is_string($fromTag)) {
                throw new ErrorException('Тэг статуса From не строкового типа');
            }

            if (!is_string($toTag)) {
                throw new ErrorException('Тэг статуса To не строкового типа');
            }

            if ($fromTag === $toTag) {
                throw new ErrorException('Нельзя назначить ссылку на себя');
            }

            /* Получаем массив со статусами From и To */
            $statusesArray = $statusClass->getStatusesForTagsArray([$fromTag, $toTag]);

            if (count($statusesArray) < 2) {
                throw new ErrorException('Статус(ы) не существуют');
            }

            /* Смотрим, есть ли в БД уже такая ссылка */
            $statusSimpleLink = $statusLinkClass->getSimpleLinkForStatusFromIdAndStatusToId(
                $statusesArray[$fromTag]->id,
                $statusesArray[$toTag]->id
            );

            if (is_object($statusSimpleLink)) {
                throw new ErrorException('Ссылка уже добавлена');
            }

            $statusLinkClass->setScenario($statusLinkClass::LINK_TYPE_SIMPLE);

            $statusLinkClass->status_from = $statusesArray[$fromTag]->id;
            $statusLinkClass->status_to = $statusesArray[$toTag]->id;
            $statusLinkClass->type = $statusLinkClass::LINK_TYPE_SIMPLE;
            $statusLinkClass->right_tag = $docTag . '.' . $fromTag . '.' . $toTag;

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
     * Удаляем SimpleLink
     *
     * @param string $fromTag - тэг статуса From
     * @param string $toTag   - тэг статуса To
     *
     * @return array - ['error' => .....] or ['success' => .....]
     *
     * @throws \yii\db\StaleObjectException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function removeSimpleLink($fromTag, $toTag)
    {
        $statusLinkClass = $this->initStatusesLinks();
        $statusClass = $this->initStatuses();

        try {
            $result = ['error' => 'Удаление не удалось'];

            if (!is_string($fromTag)) {
                throw new ErrorException('Тэг статуса From не строкового типа');
            }

            if (!is_string($toTag)) {
                throw new ErrorException('Тэг статуса To не строкового типа');
            }

            /* Получаем массив со статусами From и To */
            $statusesArray = $statusClass->getStatusesForTagsArray([$fromTag, $toTag]);

            if (count($statusesArray) < 2) {
                throw new ErrorException('Статус(ы) не существуют');
            }

            /* Получаем ссылку */
            $statusSimpleLink = $statusLinkClass->getSimpleLinkForStatusFromIdAndStatusToId(
                $statusesArray[$fromTag]->id,
                $statusesArray[$toTag]->id
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
}
