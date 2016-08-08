<?php

use docflow\models\base\DocTypes;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View     $this
 * @var DocTypes $model
 */

$this->title = Yii::t('docflow', 'Update {modelClass}: ', ['modelClass' => 'Document Type']) . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'doc' => $model->tag]];
$this->params['breadcrumbs'][] = Yii::t('docflow', 'Update');
?>
<div class="statuses-doctypes-update">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <?php echo $this->render('_form', ['model' => $model]) ?>

</div>
