<?php

/* @var $this yii\web\View */
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = strtoupper(Yii::$app->params['companyName']);

?>


<div class="site-index">

    <div class="jumbotron">
        <h1 style="font-family: helvetica;color:#ebf4f8; margin-bottom: 0.3em">INET-TAXI</h1>
        <h2 style="font-family: helvetica;color:#ebf4f8; font-size: 18px;">простой сервис удобных поездок</h2>
        <div>

        </div>
    </div>

    <div class="body-content">
        <div class="row">
            <div class="col-md-3">
                <div class="bordered-white round-bordered bg-primary first-page-block" >
                    <h2>Пассажирам</h2>
                    <p>Необыкновенно простой и быстрый заказ такси. Цену поездки назначаете вы сами.</p>
                    <p><?=Html::a('Подробнее...',['site/page','view'=>'pass'])?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bordered-white round-bordered bg-primary first-page-block" >
                <h2>Водителям</h2>
                <p>Диспетчер больше не нужен. Водитель сам может взять любой заказ. Прямая связь с клиентом.</p>
                <p><?=Html::a('Подробнее...',['site/page','view'=>'drivers'])?></p>
                </div></div>
                <div class="col-md-3">
                    <div class="bordered-white round-bordered bg-primary first-page-block" >
                <h2>Партнерская программа</h2>
                <p>INET-TAXI - часть партнерской программы <?=Html::a('VEKCLUB.COM','http://vekclub.com')?>.
                    Вы можете получать деньги за поездку, даже если вы пассажир!"</p>
                <p><?=Html::a('Подробнее...',['site/page','view'=>'partnership'])?></p>
                    </div></div>
        </div>
    </div>


    </div>
</div>
