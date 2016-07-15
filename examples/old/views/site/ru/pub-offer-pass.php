<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'Публичная оферта: Пассажирам';
$this->params['breadcrumbs'][] = ['label' => 'Публичная оферта', 'url' => ['site/page','view'=>'pub-offer']];
$this->params['breadcrumbs'][] = 'Пассажирам';
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Это договор публичной оферты</p>

</div>
