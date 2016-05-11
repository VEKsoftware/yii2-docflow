<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $model statuses\models\Statuses */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('statuses', 'Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
    <div class="row">
        <div class="col-xs-6">
            <?= Html::a(Yii::t('statuses', 'Update Statuses'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        </div>
        <div class="col-xs-6 text-right">
            <?= Html::a(Yii::t('statuses', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('statuses', 'Are you sure you want to delete this item?'),
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

    <h3><?= Html::encode(Yii::t('statuses', 'Statuses Links')) ?></h3>

    <?= ListView::widget([
        'id' => 'statuses-to-list',
        'dataProvider' => $dataProvider,
        'itemView' => function ($item, $key, $index, $widget) use($model){
            $statusesTo = $model->statusesTransitionTo;
            $model->activeLinks[$item->tag] = isset($statusesTo[$item->tag]);
            return Html::activeCheckbox($model,'activeLinks['.$item->tag.']',
                    ($model->tag === $item->tag || ! $model->docType->isAllowed('statuses_links_edit') ? ['disabled' => 'disabled'] : []) + [
                    'label' => $item->name,
                    'data' => [
                        'tag' => $item->tag,
                    ],
                ]);
        },
    ]); ?>

</div>
<?php
$this->registerJs("
    var status_to_check_url = '" . Url::toRoute(['ajax-update-link']) . "';
    var current_doc_type = '" . $model->docType->tag . "';
    var current_status = '" . $model->tag . "';
");
$this->registerJs(<<<JS

$('#statuses-to-list input[type=checkbox]').click(function(event){

    var checkbox_element = this;
    $.get(status_to_check_url,
        {
            doc: current_doc_type,
            status_from: current_status,
            status_to: checkbox_element.dataset.tag,
            linked: checkbox_element.checked
        }, function(data) {
            checkbox_element.checked = data.linked;
        }
    )
});

JS
);
?>