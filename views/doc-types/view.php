<?php

use Yii;

use yii\helpers\Html;
use yii\helpers\Url;

use yii\widgets\DetailView;
use yii\widgets\Pjax;

use yii\grid\GridView;

use yii\web\JsExpression;
use execut\widget\TreeView;

/* @var $this yii\web\View */
/* @var $model statuses\models\DocTypes */

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

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'tag',
            'name',
            'description',
            [
                'class' => 'yii\grid\ActionColumn',
/*
                'urlCreator' => function( $action, $model, $key, $index ){
                    $params = is_array($key) ? $key : ['doc' => (string) $model->tag];
                    $params[0] = Yii::$app->controller ? '/' . Yii::$app->controller->uniqueId . '/' . $action : $action;

                    return Url::toRoute($params);
                },
*/
            ],
/*
            [
                'label' => '',
                'format' => 'raw',
                'value' => function ($model, $key) {
                    return Html::a(Yii::t('docflow', 'View Statuses'), ['view', 'id' => $key]);
                },
            ],
*/
        ],
    ]); ?>

<?php
Pjax::begin([
    'id' => 'pjax-container',
]);

echo Yii::$app->request->get('page');

Pjax::end();

$onSelect = new JsExpression(<<<JS
function (undefined, item) {
    if (item.href !== location.pathname) {
        $.pjax({
            container: '#pjax-container',
            url: item.href,
            timeout: null
        });
    }

    var otherTreeWidgetEl = $('.treeview.small').not($(this)),
        otherTreeWidget = otherTreeWidgetEl.data('treeview'),
        selectedEl = otherTreeWidgetEl.find('.node-selected');
    if (selectedEl.length) {
        otherTreeWidget.unselectNode(Number(selectedEl.attr('data-nodeid')));
    }
}
JS
);

$items = [
    [
        'text' => 'Parent 1',
        'href' => Url::to(['', 'page' => 'parent1']),
        'nodes' => [
            [
                'text' => 'Child 1',
                'href' => Url::to(['', 'page' => 'child1']),
                'nodes' => [
                    [
                        'text' => 'Grandchild 1',
                        'href' => Url::to(['', 'page' => 'grandchild1'])
                    ],
                    [
                        'text' => 'Grandchild 2',
                        'href' => Url::to(['', 'page' => 'grandchild2'])
                    ]
                ]
            ],
        ],
    ],
];

echo TreeView::widget([
    'data' => $items,
    'size' => TreeView::SIZE_SMALL,
    'clientOptions' => [
        'onNodeSelected' => $onSelect,
    ],
]);
?>

</div>
