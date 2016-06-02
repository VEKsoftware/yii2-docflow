<?php

use yii\helpers\Html;
use yii\helpers\Url;

use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel docflow\models\DocTypesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('docflow', 'Document Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('docflow', 'Create Document Type'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'name',
            'tag',

            [
                'class' => 'yii\grid\ActionColumn',
                'urlCreator' => function( $action, $model, $key, $index ){
                    $params = is_array($key) ? $key : ['doc' => (string) $model->tag];
                    $params[0] = Yii::$app->controller ? '/' . Yii::$app->controller->uniqueId . '/' . $action : $action;

                    return Url::toRoute($params);
                },
            ],
        ],
    ]); ?>

</div>
