<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'Пассажирам';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-pass bordered-white round-bordered content-block row">
    <div class="col-md-2">
        <img class="img-responsive bordered-white"  src="/img/girlphone.jpg"/>
    </div>
    <div class="col-md-10">
                <h1><?= Html::encode($this->title) ?></h1>
                <p>Воспользоваться услугами такси очень просто.</p>
                <div class="bs-callout bs-callout-warning">
                <h3>Нужно сделать три шага</h3>
                <ol>
                    <li>Скачать и установить на ваш смартфон программу INET-TAXI</li>
                    <li>Зарегистрироваться в системе</li>
                    <li>Сделать свой первый заказ</li>
                </ol>
                </div>
                <h3>Для чего мне нужна регистрация?</h3>
                <p>Отличный вопрос!</p>
                <p>При регистрации вы получаете собственный личный кабинет на сайте inet-taxi.com.
                Там вы можете, например, посмотреть историю своих поездок.</p>
                <p>Но главное, для чего мы хотим оставаться с вами на связи, это ваша возможность зарабатывать вместе с нами.</p>
                <p>Сервис INET-TAXI входит в партнерскую программу <?=Html::a('vekclub.com','http://vekclub.com')?>. Каждый участник этой программы имеет свой реферальный код. Примерно такой</p>
                <img class="img-responsive" src="/img/ref-code.png"/>
                <p>Предложите кому-либо присоединиться к партнерской программе, сообщите ему свой код и станьте его рефералом.</p>
                <p>С этого момента вы будете получать на личный счет небольшую сумму каждый раз, когда ваши подписчики
                будут пользоваться услугами сервисов партнерской программы.</p>
                <p>Мало того, если ваш подписчик тоже станет чьим-то рефералом, то вы будете получать пополнения личного счета и за его подписчиков!</p>
                <p>Мы не обещаем вам сверкающую яхту и собственный остров. В каждой цепочке будет не более трех звеньев. Средства за подписчиков чертвертого поколения и дальше будут идти уже их рефералам.
                Ведь мы хотим, чтобы это было выгодно и вам, и вашим будущим подписчикам.</p>
    </div>

</div>
