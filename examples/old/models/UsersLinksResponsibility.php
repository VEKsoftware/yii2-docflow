<?php

namespace test\models;

use Yii;

/**
 * This is the model class for table "{{%users_links}}".
 *
 * @property integer $id
 * @property integer $from_id
 * @property integer $to_id
 * @property string $link_type
 * @property string $relation_type
 *
 * @property Users $from
 * @property Users $to
 */
class UsersLinksResponsibility extends UsersLinks
{
    /**
     * @inheritdoc
     */
    public function extraWhere()
    {
        return ['relation_type' => 'responsibility'];
    }

}
