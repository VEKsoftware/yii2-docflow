<?php

use docflow\models\base\docType\DocTypes;
use docflow\models\statuses\Statuses;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View     $this
 * @var DocTypes $doc
 * @var Statuses $model
 */

$this->title = Yii::t('docflow', 'Create Status');
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $doc->name, 'url' => ['view', 'doc' => $doc->tag]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-create">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <?php echo $this->render('_form-status', ['model' => $model]) ?>

</div>
