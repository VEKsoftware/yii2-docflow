<?php
/**
 * Представление Статуса
 *
 * @var View     $this
 * @var Document $model
 * @var array    $documents
 * @var string   $doc
 * @var string   $dataUrl
 * @var array    $extra
 * @var string   $flTreeUrl
 * @var string   $flTreeWithSimpleUrl
 */

use docflow\models\Document;
use docflow\widgets\FlTreeWithSimpleLinksWidget;
use yii\helpers\Url;
use yii\web\View;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

echo FlTreeWithSimpleLinksWidget::widget([
    'renderView' => 'flTreeWithSimpleLinks',
    'title' => $this->title,
    'titleLink' => Yii::t('docflow', 'Statuses Links'),
    'buttons' => [
        'update' => [
            'name' => Yii::t('docflow', 'Update Statuses'),
            'url' => ['status-update', 'doc' => $doc, 'status' => $model->tag],
        ],
        'delete' => [
            'name' => Yii::t('docflow', 'Delete'),
            'url' => ['status-delete', 'doc' => $doc, 'status' => $model->tag],
        ],
        'treeUp' => [
            'name' => Yii::t('docflow', 'Up'),
            'url' => Url::toRoute(
                [
                    'ajax-status-tree-up',
                    'statusTag' => $model->tag,
                    'docTag' => $doc,
                    'extra' => $extra
                ]
            ),
        ],
        'treeDown' => [
            'name' => Yii::t('docflow', 'Down'),
            'url' => Url::toRoute(
                [
                    'ajax-status-tree-down',
                    'statusTag' => $model->tag,
                    'docTag' => $doc,
                    'extra' => $extra
                ]
            ),
        ],
        'treeRight' => [
            'name' => Yii::t('docflow', 'In'),
            'url' => Url::toRoute(
                [
                    'ajax-status-tree-right',
                    'statusTag' => $model->tag,
                    'docTag' => $doc,
                    'extra' => $extra
                ]
            ),
        ],
        'treeLeft' => [
            'name' => Yii::t('docflow', 'Out'),
            'url' => Url::toRoute(
                [
                    'ajax-status-tree-left',
                    'statusTag' => $model->tag,
                    'docTag' => $doc,
                    'extra' => $extra
                ]
            ),
        ],
    ],
    'dataViewConfig' => [
        'model' => $model,
        'attributes' => [
            'tag',
            'name',
            'description'
        ]
    ],
    'nodeName' => $model->{$model->docNameField()},
    'flTreeUrl' => Url::toRoute(
        [
            'doc-types/ajax-get-child',
            'extra' => $extra,
        ]
    ),
    'flTreeWithSimpleUrl' => Url::toRoute(
        [
            'doc-types/ajax-get-child-with-simple',
            'extra' => $extra,
            'fromNodeId' => $model->id
        ]
    )
]);
