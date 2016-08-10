<?php

use docflow\models\base\docType\DocTypes;
use yii\helpers\Html;

use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * @var View       $this $this
 * @var DocTypes   $model
 * @var ActiveForm $form
 */
?>

<div class="statuses-doctypes-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php echo $form->field($model, 'name')->textInput() ?>

    <?php echo $form->field($model, 'tag')->textInput() ?>

    <div class="form-group">
        <?php echo Html::submitButton(
            $model->isNewRecord
                ? Yii::t('docflow', 'Create')
                : Yii::t('docflow', 'Update'),
            [
                'class' => $model->isNewRecord
                    ? 'btn btn-success'
                    : 'btn btn-primary'
            ]
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
