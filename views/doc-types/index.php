<?php

use docflow\models\base\DocType\DocTypesSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;

use yii\grid\GridView;
use yii\web\View;

/**
 * @var View               $this
 * @var DocTypesSearch     $searchModel
 * @var ActiveDataProvider $dataProvider
 */

$this->title = Yii::t('docflow', 'Document Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-index">

    <h1><?php echo Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?php echo Html::a(Yii::t('docflow', 'Create Document Type'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'name',
            'tag',

            [
                'class' => 'yii\grid\ActionColumn',
                'urlCreator' => function ($action, $model, $key, $index) {
                    $params = is_array($key) ? $key : ['doc' => (string)$model->tag];
                    $params[0] = Yii::$app->controller ? '/' . Yii::$app->controller->uniqueId . '/' . $action : $action;

                    return Url::toRoute($params);
                },
            ],
        ],
    ]); ?>

</div>
