<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model statuses\models\DocTypes */

$this->title = Yii::t('docflow', 'Update {modelClass}: ', [
    'modelClass' => 'Document Type',
]).' '.$model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'doc' => $model->tag]];
$this->params['breadcrumbs'][] = Yii::t('docflow', 'Update');
?>
<div class="statuses-doctypes-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
