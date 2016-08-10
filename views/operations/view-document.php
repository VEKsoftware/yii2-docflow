<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 10.08.16
 * Time: 11:05
 *
 * @var OperationsFlTree $document
 */

use docflow\models\base\operations\flTree\OperationsFlTree;
use docflow\widgets\FlTreeWidgetWithSimpleLinks;

echo FlTreeWidgetWithSimpleLinks::widget([
    'base' => [
        'title' => (string)$document->id,
        'titleLink' => Yii::t('docflow', 'Пользовательские связи'),
        'nodeName' => (string)$document->id,
        'renderTree' => ['operations/ajax-child']
    ],
    'detailViewConfig' => [
        'model' => $document,
        'attributes' => [
            'operation_type',
            'status.name',
            'unit_real_id',
            'unit_resp_id',
            'atime',
        ]
    ],
    'buttons' => [
        'setParent' => [
            'name' => 'Назначить родителя',
            'modalId' => '#myModal',
            'childShowCheckBox' => false,
            'parentShowCheckBox' => false,
            'setParentUrl' => ['operations/set-parent']
        ]
    ]
]);
