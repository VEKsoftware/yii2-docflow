<?php

use yii\helpers\Html;

/**
 * @var $this yii\web\View 
 * @var $model docflow\models\DocTypes
 * @var string $doc
 */

$this->title = Yii::t('docflow', 'Update {modelClass}: ', [
    'modelClass' => 'Status',
]).' '.$model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['status-view', 'tag' => $model->tag, 'doc' => $doc]];
$this->params['breadcrumbs'][] = Yii::t('docflow', 'Update');
?>
<div class="statuses-doctypes-update">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <?php echo $this->render('_form-status', ['model' => $model]) ?>

</div>
