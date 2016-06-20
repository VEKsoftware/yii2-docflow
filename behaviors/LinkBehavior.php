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

class LinkBehavior extends LinkOrderedBehavior
{
    /**
     * @var object - Класс связей
     */
    public $linkClass;

    /**
     * @var string Тип связи
     *             1) none - разрешены все методы - значение по умолчанию
     *             2) simple - разрешены методы манипуляции с простыми связями
     *             3) fltree - разрешены методы манипуляции со связями типа плоское дерево
     */
    public $type = 'none';

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
}
