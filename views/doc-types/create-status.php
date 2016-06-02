<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $doc docflow\models\DocTypes */
/* @var $model docflow\models\Statuses */

$this->title = Yii::t('docflow', 'Create Status');
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $doc->name, 'url' => ['view', 'doc' => $doc->tag]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form-status', [
        'model' => $model,
    ]) ?>

</div>
