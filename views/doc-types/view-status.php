<?php

use docflow\assets\TreeViewAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $model docflow\models\Statuses */
/* @var $doc string */

TreeViewAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
    <div class="row">
        <div class="col-xs-2 text-left">
            <?= Html::a(Yii::t('docflow', 'Update Statuses'), ['status-update', 'doc' => $doc, 'status' => $model->tag], ['class' => 'btn btn-primary']) ?>
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
                            'docTag' => $doc
                        ]
                    ),
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
                            'docTag' => $doc
                        ]
                    ),
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
                            'docTag' => $doc
                        ]
                    ),
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
                            'docTag' => $doc
                        ]
                    ),
                    'class' => 'btn btn-primary glyphicon glyphicon-arrow-right'
                ]
            ) ?>
        </div>
        <div class="col-xs-2 text-right">
            <?= Html::a(Yii::t('docflow', 'Delete'), ['status-delete', 'doc' => $doc, 'status' => $model->tag], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('docflow', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'tag',
            'name',
            'description',
        ],
    ]) ?>

</div>
<div class="statuses-index">
    <h3><?= Html::encode(Yii::t('docflow', 'Statuses Links')) ?></h3>
    <span id="simple-link-change-status"></span>
    <div id="tree-simple-link"></div>
</div>
<?php
$tree = json_encode($tree);
$this->registerJs("var dataTree = $tree");
$this->registerJs(<<<'JS'
var onChecked = function (undefined, item) {
    var url = '/docflow/doc-types/ajax-add-simple-link?' + item.href;
    var $tree = $('#tree-simple-link');
    
    $tree.treeview('selectNode', [ item.nodeId, { silent: true } ]);
    getSimpleLinksAjax(url);
}

var onUnchecked = function (undefined, item) {
    var url = '/docflow/doc-types/ajax-remove-simple-link?' + item.href;
    var $tree = $('#tree-simple-link');
    
    $tree.treeview('selectNode', [ item.nodeId, { silent: true } ]);
    getSimpleLinksAjax(url);
}

var $tree = $('#tree-simple-link').treeview({
    data: dataTree,
    showCheckbox: true,
    levels: 5,
    onNodeChecked: onChecked,
    onNodeUnchecked: onUnchecked
});
JS
);
