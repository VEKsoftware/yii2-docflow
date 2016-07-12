<?php

use docflow\assets\TreeViewAsset;
use docflow\models\Statuses;
use docflow\widgets\FlTreeWithSimpleLinksWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\DetailView;

/**
 * @var View     $this
 * @var Statuses $model
 * @var array    $documents
 * @var          $doc
 * @var string   $dataUrl
 * @var array    $extra
 * @var string   $flTreeUrl
 * @var string   $flTreeWithSimpleUrl
 */

TreeViewAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
    <div class="statuses-view">

        <h1><?php echo Html::encode($this->title) ?></h1>
        <p>
        <div class="row">
            <div class="col-xs-2 text-left">
                <?php echo Html::a(
                    Yii::t('docflow', 'Update Statuses'),
                    ['status-update', 'doc' => $doc, 'status' => $model->tag],
                    ['class' => 'btn btn-primary']
                ) ?>
            </div>
            <div class="col-xs-8 text-center" id="actions-tree-buttons">
                <?php echo Html::tag(
                    'div',
                    Yii::t('docflow', 'Out'),
                    [
                        'name' => 'left-in-tree',
                        'data-href' => Url::toRoute(
                            [
                                'ajax-status-tree-left',
                                'statusTag' => $model->tag,
                                'docTag' => $doc,
                                'extra' => $extra
                            ]
                        ),
                        'data-doc-name' => $model->{$model->docNameField()},
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-fl-tree-with-simple-url' => $flTreeWithSimpleUrl,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-left'
                    ]
                ) ?>
                <?php echo Html::tag(
                    'div',
                    Yii::t('docflow', 'Up'),
                    [
                        'name' => 'up-in-tree',
                        'data-href' => Url::toRoute(
                            [
                                'ajax-status-tree-up',
                                'statusTag' => $model->tag,
                                'docTag' => $doc,
                                'extra' => $extra
                            ]
                        ),
                        'data-doc-name' => $model->{$model->docNameField()},
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-fl-tree-with-simple-url' => $flTreeWithSimpleUrl,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-up'
                    ]
                ) ?>
                <?php echo Html::tag(
                    'div',
                    Yii::t('docflow', 'Down'),
                    [
                        'name' => 'down-in-tree',
                        'data-href' => Url::toRoute(
                            [
                                'ajax-status-tree-down',
                                'statusTag' => $model->tag,
                                'docTag' => $doc,
                                'extra' => $extra
                            ]
                        ),
                        'data-doc-name' => $model->{$model->docNameField()},
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-fl-tree-with-simple-url' => $flTreeWithSimpleUrl,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-down'
                    ]
                ) ?>
                <?php echo Html::tag(
                    'div',
                    Yii::t('docflow', 'In'),
                    [
                        'name' => 'right-in-tree',
                        'data-href' => Url::toRoute(
                            [
                                'ajax-status-tree-right',
                                'statusTag' => $model->tag,
                                'docTag' => $doc,
                                'extra' => $extra
                            ]
                        ),
                        'data-doc-name' => $model->{$model->docNameField()},
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-fl-tree-with-simple-url' => $flTreeWithSimpleUrl,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-right'
                    ]
                ) ?>
            </div>
            <div class="col-xs-2 text-right">
                <?php echo Html::a(
                    Yii::t('docflow', 'Delete'),
                    ['status-delete', 'doc' => $doc, 'status' => $model->tag],
                    [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => Yii::t('docflow', 'Are you sure you want to delete this item?'),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </div>
        </div>
        </p>

        <?php echo DetailView::widget(
            [
                'model' => $model,
                'attributes' => [
                    'tag',
                    'name',
                    'description'
                ]
            ]
        ) ?>

    </div>

<?php echo FlTreeWithSimpleLinksWidget::widget([
    'renderView' => 'flTreeWithSimpleLinks',
    'dataUrl' => $flTreeWithSimpleUrl
]);
