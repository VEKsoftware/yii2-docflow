<?php

use yii\helpers\Html;
use yii\helpers\Url;

use yii\widgets\DetailView;
//use yii\widgets\Pjax;

use yii\grid\GridView;

use yii\web\JsExpression;
use docflow\assets\TreeViewAsset;

/* @var $this yii\web\View */
/* @var $model docflow\models\DocTypes */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Statuses Doctypes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('docflow', 'Update'), ['update', 'doc' => $model->tag], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('docflow', 'Delete'), ['delete', 'doc' => $model->tag], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('docflow', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'tag',
        ],
    ]) ?>

    <p>
        <?= Html::a(Yii::t('docflow', 'Create Statuses'), ['create-status', 'doc' => $model->tag], ['class' => 'btn btn-success']) ?>
    </p>

<div class="row">
    <div class="col-sm-3">
        <h3> <?=Yii::t('docflow', 'List of statuses')?> </h3>
        <div id="tree"></div>
    </div>
    <div class="col-sm-9">
        <div id="tree-leaf"></div>
    </div>
</div>

<?php
function treeBranch($val) {
    return [
        'text' => $val->name,
        'href' => Url::to(['status-view', 'doc' => $val->docType->tag, 'tag' => $val->tag]),
    ] + (empty($val->statusChildren) ? [] : [
        'nodes' => array_map('treeBranch', $val->statusChildren),
    ]);
}

$items = array_map('treeBranch', $model->statusesStructure);

$data = json_encode($items);
$this->registerJs("var data = $data");

TreeViewAsset::register($this);

$this->registerJs(<<<'JS'
var onSelect = function (undefined, item) {
    if (item.href !== location.pathname) {
        $("#tree-leaf").load(item.href);
    }
}

var onUnselect = function (undefined, item) {
    $("#tree-leaf").html('');
}

var $searchableTree = $('#tree').treeview({
    data: data,
    onNodeSelected: onSelect,
    onNodeUnselected: onUnselect
});

var search = function(e) {
    var pattern = $('#input-search').val();
    var options = {
        ignoreCase: $('#chk-ignore-case').is(':checked'),
        exactMatch: $('#chk-exact-match').is(':checked'),
        revealResults: $('#chk-reveal-results').is(':checked')
    };
    var results = $searchableTree.treeview('search', [ pattern, options ]);

    var output = '<p>' + results.length + ' matches found</p>';
    $.each(results, function (index, result) {
        output += '<p>- ' + result.text + '</p>';
    });
    $('#search-output').html(output);
}
JS
);
?>

</div>
