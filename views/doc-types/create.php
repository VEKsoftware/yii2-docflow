<?php

use docflow\models\base\doc_type\DocTypes;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View     $this
 * @var DocTypes $model
 */

$this->title = Yii::t('docflow', 'Create Statuses Doctypes');
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Document Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="statuses-doctypes-create">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <?php echo $this->render('_form', ['model' => $model]) ?>

</div>
