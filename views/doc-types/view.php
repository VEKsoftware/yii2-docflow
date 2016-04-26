<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model statuses\models\DocTypes */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('statuses', 'Statuses Doctypes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('statuses', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('statuses', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('statuses', 'Are you sure you want to delete this item?'),
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
            'status.name',
        ],
    ]) ?>

    <p>
        <?= Html::a(Yii::t('statuses', 'Create Statuses'), ['create'], ['class' => 'btn btn-success']) ?>
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
                'label' => '',
                'format' => 'raw',
                'value' => function ($model, $key) {
                    return Html::a(Yii::t('statuses', 'View Statuses'), ['view', 'id' => $key]);
                },
            ],
        ],
    ]); ?>
</div>
