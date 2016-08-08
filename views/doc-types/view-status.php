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

use docflow\models\base\Document;
use docflow\widgets\FlTreeWidgetWithSimpleLinks;
use yii\web\View;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

echo FlTreeWidgetWithSimpleLinks::widget([
    'base' => [
        'title' => $this->title,
        'titleLink' => Yii::t('docflow', 'Statuses Links'),
        'nodeName' => $model->docName,
        'renderTree' => [
            'doc-types/ajax-get-child',
            'docType' => $doc,
            'extra' => $extra,
        ]
    ],
    'detailViewConfig' => [
        'model' => $model,
        'attributes' => [
            'tag',
            'name',
            'description'
        ]
    ],
    'widget' => [
        'source' => [
            'doc-types/ajax-get-child-with-simple',
            'extra' => $extra,
            'fromNodeId' => $model->id
        ],
        'showCheckBox' => true
    ],
    'buttons' => [
        'update' => [
            'name' => Yii::t('docflow', 'Update Statuses'),
            'url' => [
                'status-update',
                'doc' => $doc,
                'status' => $model->tag
            ],
        ],
        'delete' => [
            'name' => Yii::t('docflow', 'Delete'),
            'url' => ['status-delete', 'doc' => $doc, 'status' => $model->tag],
        ],
        'treeUp' => [
            'name' => Yii::t('docflow', 'Up'),
            'url' => [
                'ajax-status-tree-up',
                'statusTag' => $model->tag,
                'docTag' => $doc,
                'extra' => $extra
            ]
        ],
        'treeDown' => [
            'name' => Yii::t('docflow', 'Down'),
            'url' => [
                'ajax-status-tree-down',
                'statusTag' => $model->tag,
                'docTag' => $doc,
                'extra' => $extra
            ]
        ],
        'treeRight' => [
            'name' => Yii::t('docflow', 'In'),
            'url' => [
                'ajax-status-tree-right',
                'statusTag' => $model->tag,
                'docTag' => $doc,
                'extra' => $extra
            ]
        ],
        'treeLeft' => [
            'name' => Yii::t('docflow', 'Out'),
            'url' => [
                'ajax-status-tree-left',
                'statusTag' => $model->tag,
                'docTag' => $doc,
                'extra' => $extra
            ]
        ],
        'setParent' => [
            'name' => 'Назначить родителя',
            'modalId' => '#myModal',
            'childShowCheckBox' => false,
            'parentShowCheckBox' => false,
            'setParentUrl' => ['doc-types/set-parent']
        ]
    ],
]);
