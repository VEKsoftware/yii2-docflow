<?php

namespace docflow\models\base\statuses\links;

use docflow\models\base\statuses\Statuses;
use yii;

/**
 * This is the model class for table "statuses_links".
 * This model describes the tree structure of the statusesa allowing substatuses.
 *
 * @property int      $status_from
 * @property int      $status_to
 * @property string   $right_tag
 * @property Statuses $statusFrom
 * @property Statuses $statusTo
 */
class StatusesLinksStructure extends StatusesLinks
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function extraWhere()
    {
        return ['type' => static::LINK_TYPE_FLTREE];
    }

}
