<?php

use docflow\models\base\DocType\DocTypesSearch;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * @var View $this
 * @var DocTypesSearch $model
 * @var ActiveForm $form
 */
?>

<div class="statuses-doctypes-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?php echo $form->field($model, 'id') ?>

    <?php echo $form->field($model, 'name') ?>

    <?php echo $form->field($model, 'tag') ?>

    <div class="form-group">
        <?php echo Html::submitButton(Yii::t('docflow', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?php echo Html::resetButton(Yii::t('docflow', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
