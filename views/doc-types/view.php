<?php
/**
 * @var View     $this
 * @var DocTypes $model
 * @var array    $flTreeWidgetParam
 */

use docflow\models\DocTypes;
use docflow\widgets\FlTreeWidget;
use yii\helpers\Html;

use yii\web\View;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Statuses Doctypes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-view">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <p>
        <?php echo Html::a(
            Yii::t('docflow', 'Update'),
            ['update', 'doc' => $model->tag],
            ['class' => 'btn btn-primary']
        ); ?>
        <?php echo Html::a(
            Yii::t('docflow', 'Delete'),
            ['delete', 'doc' => $model->tag],
            [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('docflow', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]
        ); ?>
    </p>

    <?php echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'tag',
        ],
    ]) ?>

    <p>
        <?php echo Html::a(
            Yii::t('docflow', 'Create Statuses'),
            ['create-status', 'doc' => $model->tag],
            ['class' => 'btn btn-success']
        ) ?>
    </p>

    <?php echo FlTreeWidget::widget([
        'base' => [
            'titleList' => $flTreeWidgetParam['titleList']
        ],
        'sources' => [
            'flTreeUrl' => $flTreeWidgetParam['flTreeUrl'],
        ]
    ]) ?>
</div>
