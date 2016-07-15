<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 12.07.16
 * Time: 16:14
 *
 * @var Users $document
 */

use docflow\testing\Users;
use docflow\widgets\FlTreeWithSimpleLinksWidget;
use yii\helpers\Url;

echo FlTreeWithSimpleLinksWidget::widget([
    'renderView' => 'flTreeWithSimpleLinks',
    'title' => $this->title,
    'titleLink' => Yii::t('docflow', 'Тест: пользовательские связи'),
    'buttons' => [
        'update' => [
            'name' => Yii::t('docflow', 'Update Statuses'),
            'url' => ['status-update', 'documentId' => $document->idx],
        ],
        'delete' => [
            'name' => Yii::t('docflow', 'Delete'),
            'url' => ['status-delete', 'documentId' => $document->idx],
        ],
        'treeUp' => [
            'name' => Yii::t('docflow', 'Up'),
            'url' => Url::toRoute(
                [
                    'ajax-up',
                    'nodeId' => $document->idx
                ]
            ),
        ],
        'treeDown' => [
            'name' => Yii::t('docflow', 'Down'),
            'url' => Url::toRoute(
                [
                    'ajax-down',
                    'nodeId' => $document->idx
                ]
            ),
        ],
        'treeRight' => [
            'name' => Yii::t('docflow', 'In'),
            'url' => Url::toRoute(
                [
                    'ajax-right',
                    'nodeId' => $document->idx
                ]
            ),
        ],
        'treeLeft' => [
            'name' => Yii::t('docflow', 'Out'),
            'url' => Url::toRoute(
                [
                    'ajax-left',
                    'nodeId' => $document->idx
                ]
            ),
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
    'nodeName' => $document->{$document->docNameField()},
    'flTreeUrl' => Url::toRoute(['test-users/ajax-child']),
    'flTreeWithSimpleUrl' => Url::toRoute(
        [
            'test-users/ajax-child-simple',
            'fromNodeId' => $document->idx
        ]
    )
]);
