<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'О проекте';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-aboutproj bordered-white round-bordered content-block row">
    <div class="col-md-2">
       <img  class="img-responsive bordered-white"  src="/img/driver.jpg"/>
    </div>
    <div class="col-md-10">
            <h1><?= Html::encode($this->title) ?></h1>
            <h2>Добрый день!</h2>
            <p>Если вы находитесь на этой странице, значит, вас заинтересовал наш проект.</p>
            <p>Давайте, мы расскажем о нем подробнее, вдруг это именно то, что вы искали.</p>
            <br/>
            <h3>Главное</h3>
            <ul>
                <li>INET-TAXI помогает вам быстро и без хлопот вызвать такси, если сегодня вы — пассажир.</li>
                <li>INET-TAXI помогает вам быстро и без хлопот найти пассажира, если вы водитель.</li>
            </ul>

            <h3>Это интересно</h3>
            <ul><li>В INET-TAXI вы можете быть и пассажиром и водителем, просто переключив режим работы программы на вашем смартфоне.</li></ul>
            <div style="height: 40px;">
                <img src="/img/car.png" width="32" height="32" style="float:left; margin-top:-20px;"/>
                <hr style="height:1px; margin-left: 50px; margin-top: 45px;" align="left" width="80%" color="brown" />
            </div>
        <div class="bs-callout bs-callout-info">
        <h3>Как воспользоваться</h3><br>
        <ol>
            <li>
                <a class="btn btn-info" href="https://play.google.com/apps/testing/com.veksoftware.inettaxi">Скачать приложение с Play Market</a>
            </li>
            <li>Зарегистрироваться в программе</li>
            <li>Получить подтвержение регистрации по СМС</li>
            <li>Готово!</li>
        </ol>
        </div>
        <div class="">
            <h3>Наши плюсы</h3>
            <p>INET-TAXI работает без диспетчеров. Заказы от пассажиров сразу попадают на наш сервер в интернете и в эту же секунду становятся видны водителям.
                Так как мы активно используем спутниковую навигацию, то на заказ откликнется тот водитель, который ближе всех к пассажиру. Так мы сокращаем время ожидания.</p>
        </div>

            <h3>Что отличает нас от других служб такси</h3>
        <div class="bs-callout bs-callout-danger">
            <h4>Вы зарабатываете деньги, даже когда их тратите!</h4>
        </div>
            <p>Проект INET-TAXI является частью партнерской системы <a href="http://vekclub.com">VEKCLUB.COM</a>. Каждый участник этой системы получает свой процент от сделок, проведенных её участниками. Конечно, не всеми, а только теми, кого вы привели в партнерскую программу.
                При этом деньги от сделки получает не только водитель, но и пассажир!</p>
        <p>Подробности на <?=Html::a('vekclub.com','http://vekclub.com')?></p>
       </div>
</div>
