<?php

namespace docflow\models;

use yii;

/**
 * This is the model class for table "statuses_links".
 * This model describes forkflow for documents, i.e. transitions of the statuses from one to another.
 *
 * @property int $status_from
 * @property int $status_to
 * @property string $right_tag
 * @property Statuses $statusFrom
 * @property Statuses $statusTo
 */
class StatusesLinksTransitions extends StatusesLinks
{
    /**
     * @inheritdoc
     */
    public function extraWhere()
    {
        return ['type' => static::LINK_TYPE_SIMPLE];
    }

}
