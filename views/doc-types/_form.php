<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model statuses\models\DocTypes */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="statuses-doctypes-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput() ?>

    <?= $form->field($model, 'status_id')->dropDownList(ArrayHelper::map(array_merge([$model->status],$model->allowedStatuses), 'tag', 'name')) ?>

    <?= $form->field($model, 'tag')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('statuses', 'Create') : Yii::t('statuses', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
