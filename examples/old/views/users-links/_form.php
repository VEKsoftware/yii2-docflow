<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model test\models\UsersLinks */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="users-links-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'from_id')->textInput() ?>

    <?= $form->field($model, 'to_id')->textInput() ?>

    <?= $form->field($model, 'link_type')->dropDownList([ 'simple' => 'Simple', 'fltree' => 'Fltree', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'relation_type')->dropDownList([ 'subordination' => 'Subordination', 'responsibility' => 'Responsibility', ], ['prompt' => '']) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
