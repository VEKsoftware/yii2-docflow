<?php

use docflow\widgets\FlTreeWidget;
use yii\helpers\Html;

use yii\widgets\DetailView;

/**
 * @var $this yii\web\View
 * @var $model docflow\models\DocTypes
 * @var $dataUrl string
 */

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

    <?php echo FlTreeWidget::widget(['renderView' => 'flTree', 'dataUrl' => $dataUrl]) ?>
</div>
