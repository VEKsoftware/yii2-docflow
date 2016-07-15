<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 12.07.16
 * Time: 16:14
 *
 * @var Users $document
 */

use docflow\examples\users\models\Users;
use docflow\widgets\FlTreeWithSimpleLinksWidget;
use yii\helpers\Url;

echo FlTreeWithSimpleLinksWidget::widget([
    'title' => $this->title,
    'titleLink' => Yii::t('docflow', 'Тест: пользовательские связи'),
    'buttons' => [
        'update' => [
            'name' => Yii::t('docflow', 'Update Statuses'),
            'url' => [
                'status-update',
                'documentId' => $document->idx
            ],
        ],
        'delete' => [
            'name' => Yii::t('docflow', 'Delete'),
            'url' => [
                'status-delete',
                'documentId' => $document->idx
            ],
        ],
        'treeUp' => [
            'name' => Yii::t('docflow', 'Up'),
            'url' => [
                'ajax-up',
                'nodeId' => $document->idx
            ]
        ],
        'treeDown' => [
            'name' => Yii::t('docflow', 'Down'),
            'url' => [
                'ajax-down',
                'nodeId' => $document->idx
            ]
        ],
        'treeRight' => [
            'name' => Yii::t('docflow', 'In'),
            'url' => [
                'ajax-right',
                'nodeId' => $document->idx
            ]
        ],
        'treeLeft' => [
            'name' => Yii::t('docflow', 'Out'),
            'url' => [
                'ajax-left',
                'nodeId' => $document->idx
            ]
        ],
    ],
    'dataViewConfig' => [
        'model' => $document,
        'attributes' => [
            'short_name',
            'full_name',
            'tag'
        ]
    ],
    'nodeName' => $document->{$document::docNameField()},
    'flTreeUrl' => Url::toRoute(['test-users/ajax-child']),
    'flTreeWithSimpleUrl' => Url::toRoute(
        [
            'test-users/ajax-child-simple',
            'fromNodeId' => $document->idx
        ]
    )
]);