<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model docflow\models\DocTypes */

$this->title = Yii::t('docflow', 'Update {modelClass}: ', [
    'modelClass' => 'Status',
]).' '.$model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('docflow', 'Update');
?>
<div class="statuses-doctypes-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form-status', [
        'model' => $model,
    ]) ?>

</div>
