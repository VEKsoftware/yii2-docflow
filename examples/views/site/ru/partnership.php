<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'Партнерская программа';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about bordered-white round-bordered content-block row">
    <div class="col-md-2">
        <img  class="img-responsive bordered-white"  src="/img/manwallet.jpg"/>
    </div>
    <div class="col-md-10">
        <h1><?= Html::encode($this->title) ?></h1>
    <p>Хотите еще сократить расходы на такси? А еще лучше, получать доход?</p>
    <p>Отлично, Вы попали по адресу!</p>
    <p>Партнерская программа <?=Html::a('vekclub.com','http://vekclub.com')?> позволяет своим пользователям заработать на поездке ваших друзей. Чем больше друзей – тем больше заработок!</p>
    <div class="bs-callout bs-callout-warning">
    <h4>Как это работает:</h4>
    <ol>
    <li>Вы регистрируетесь в системе и получаете реферальный код</li>
    <li>Передаете ваш код своему другу</li>
    <li>Друг пользуется услугами нашего такси, а вы получаете комиссию с его заказов.</li>
    <li>Полученные средства можно потратить на поездки или же вывести.</li>
    </ol>
    </div>
    <p>Следует заметить, что вы также будете получать процент от заказов друзей вашего друга и так до третьего уровня!
    </p>
        <p>Подробности на <?=Html::a('vekclub.com','http://vekclub.com')?></p>
        </div>

</div>
