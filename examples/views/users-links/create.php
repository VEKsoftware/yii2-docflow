<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model test\models\UsersLinks */

$this->title = 'Create Users Links';
$this->params['breadcrumbs'][] = ['label' => 'Users Links', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-links-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
