<?php

use docflow\models\base\doc_type\DocTypes;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View     $this
 * @var DocTypes $model
 * @var string   $doc
 */

$this->title = Yii::t('docflow', 'Update {modelClass}: ', ['modelClass' => 'Status']) . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = [
    'label' => $model->name,
    'url' => ['status-view', 'tag' => $model->tag, 'doc' => $doc]
];
$this->params['breadcrumbs'][] = Yii::t('docflow', 'Update');
?>
<div class="statuses-doctypes-update">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <?php echo $this->render('_form-status', ['model' => $model]) ?>

</div>
