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

}
