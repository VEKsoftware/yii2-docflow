<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 08.08.16
 * Time: 15:13
 */

namespace docflow\models\base;

use docflow\base\UnstructuredRecord;
use docflow\Docflow;

abstract class DocFlowBase extends UnstructuredRecord
{
    /**
     * This function returns the structure containing access rights tags.
     *
     * @return mixed Structure is the following
     *  [
     *     [
     *        'operation' => 'view', // This is the name of operation. It will be refered in the access check methods like $user->can(operation)
     *        'label' => 'View document',
     *        'conditions' => [ // These conditions are handled in the document model and are set up in the access settings page
     *            [
     *                'condition' => 'own',
     *                'label' => 'Only my',
     *            ],
     *            [
     *                'condition' => 'all',
     *                'label' => 'All',
     *            ],
     *        ],
     *    ],
     *    [
     *      ...
     *    ],
     *    ...
     *  ],
     */
    /* abstract public static function accessData(); */

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => Docflow::getInstance()->accessClass,
                ],
            ]
        );
    }
}
