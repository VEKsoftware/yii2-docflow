<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 14.06.16
 * Time: 14:28
 */

namespace docflow\behaviors;

use docflow\models\Link;
use docflow\models\Statuses;
use docflow\models\StatusesLinks;
use docflow\models\StatusSimpleLink;
use docflow\models\StatusTreePosition;
use yii;
use yii\base\Behavior;
use yii\base\ErrorException;
use yii\di\Instance;

class LinksBehavior extends Behavior
{
    /**
     * @var object - Класс связей
     */
    public $linkClass;

    /**
     * @var string Тип связи
     */
    public $type = 'simple';

    /**
     * @var string Указываем разновидность типа связи
     */
    public $relation_type = null;

    /**
     * @var bool Сортировать или нет
     */
    public $ordered = false;

    /**
     * @var null По какому полю индексировать
     */
    public $indexBy = null;

    public function attach($owner)
    {
        parent::attach($owner);

        if (!($owner instanceof Statuses)) {
            throw new ErrorException('Класс узла не принадлежит Statuses');
        }

        if (empty($this->linkClass) || !($this->linkClass instanceof Link)) {
            throw new ErrorException('Отсутствует класс связей или не принадлежит Link');
        }

        Yii::$container->set(StatusesLinks::className(), $this->linkClass);
        Yii::$container->set(Statuses::className(), $owner);
    }

    /**
     * Иницииализируем класс StatusTreePosition
     *
     * @return StatusTreePosition
     * @throws \yii\base\InvalidConfigException
     */
    protected function initStatusTreePosition()
    {
        return Instance::ensure([], StatusTreePosition::className());
    }

    /**
     * Инициируем класс StatusSimpleLink
     *
     * @return StatusSimpleLink
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function initStatusSimpleLink()
    {
        return Instance::ensure([], StatusSimpleLink::className());
    }

    /**
     * Поднимаем статус вверх в дереве
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function moveUp()
    {
        $sTPClass = $this->initStatusTreePosition();

        try {
            if ($this->type === 'simple') {
                throw new ErrorException('Метод не может быть вызван при текущем типе связи');
            }

            if (empty($this->owner->tag)) {
                throw new ErrorException('Тэг статуса пуст');
            }

            $return = $sTPClass->setStatusInTreeVertical($this->owner->tag, 'Up');
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Опускаем статус вниз в дереве
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function moveDown()
    {
        $sTPClass = $this->initStatusTreePosition();

        try {
            if ($this->type === 'simple') {
                throw new ErrorException('Метод не может быть вызван при текущем типе связи');
            }

            if (empty($this->owner->tag)) {
                throw new ErrorException('Тэг статуса пуст');
            }

            $return = $sTPClass->setStatusInTreeVertical($this->owner->tag, 'Down');
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Углубляем статус
     *
     * @return array
     * @throws \Exception
     */
    public function moveRight()
    {
        $sTPClass = $this->initStatusTreePosition();

        try {
            if ($this->type === 'simple') {
                throw new ErrorException('Метод не может быть вызван при текущем типе связи');
            }

            if (empty($this->owner->tag)) {
                throw new ErrorException('Тэг статуса пуст');
            }

            $return = $sTPClass->setStatusInTreeHorizontal($this->owner->tag, 'Right');
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Выводим статус
     *
     * @return array
     * @throws \Exception
     */
    public function moveLeft()
    {
        $sTPClass = $this->initStatusTreePosition();

        try {
            if ($this->type === 'simple') {
                throw new ErrorException('Метод не может быть вызван при текущем типе связи');
            }

            if (empty($this->owner->tag)) {
                throw new ErrorException('Тэг статуса пуст');
            }

            $return = $sTPClass->setStatusInTreeHorizontal($this->owner->tag, 'Left');
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Устанавливаем простую связь
     *
     * @param string $docTag - тэг документа
     * @param string $toTag  - тэг to
     *
     * @return array
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function setSimpleLink($docTag, $toTag)
    {
        $sSLClass = $this->initStatusSimpleLink();

        try {
            if ($this->type === 'fltree') {
                throw new ErrorException('Метод не может быть вызван при текущем типе связи');
            }

            if (empty($this->owner->tag)) {
                throw new ErrorException('Тэг from пуст');
            }

            $return = $sSLClass->addSimpleLink($docTag, $this->owner->tag, $toTag);
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Удаляем простую связь
     *
     * @param string $toTag - тэг to
     *
     * @return array
     */
    public function removeSimpleLink($toTag)
    {
        $sSLClass = $this->initStatusSimpleLink();

        try {
            if ($this->type === 'fltree') {
                throw new ErrorException('Метод не может быть вызван при текущем типе связи');
            }

            if (empty($this->owner->tag)) {
                throw new ErrorException('Тэг from пуст');
            }

            $return = $sSLClass->removeSimpleLink($this->owner->tag, $toTag);
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Устанавливаем статусу родителя
     *
     * @param int $statusIdTo - Id статуса нового родителя
     *
     * @return array
     */
    public function setParent($statusIdTo)
    {
        $sTPClass = $this->initStatusTreePosition();

        try {
            if ($this->type === 'simple') {
                throw new ErrorException('Метод не может быть вызван при текущем типе связи');
            }

            if (empty($this->owner->id)) {
                throw new ErrorException('Id статуса пуст или не integer');
            }

            $return = $sTPClass->setParent($this->owner->id, $statusIdTo);
        } catch (ErrorException $e) {
            $return = ['error' => $e->getMessage()];
        }

        return $return;
    }
}
