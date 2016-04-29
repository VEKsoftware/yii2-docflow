<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'О компании';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about bordered-white round-bordered content-block row">
    <div class="col-md-2">
        <img  class="img-responsive"  src="/img/logo-supersim.png"/><br>
        <img  class="img-responsive"  src="/img/logo-supercall.png"/><br>
        <img  class="img-responsive"  src="/img/logo-inet-taxi.png"/><br>
    </div>
    <div class="col-md-10">
        <div style="">
            <img class="img-responsive bordered-white"  src="/img/logo-vek-software.png"/>
        </div>
        <h1><?= Html::encode($this->title) ?></h1>
        <p>Компания veksoftware - технологический стартап из Екатеринбурга.</p>
        <p>Первой разработкой стала корпоративная учетная система <span style="color:#d58512;font-weight: 800;">SuperSIM.top</span> для сети салонов сотовой связи SuperSIM,
            расположенных на территории Уральского федерального округа.</p>
        <p>Система была создана в 2015 году буквально за 4 месяца и полностью заменила громоздкую и дорогую 1С.</p>
        <p>Полученные знания и навыки мы использовали  при создании линейки новых продуктов.</p>
        <div class="bs-callout bs-callout-success">
        <p><b>INET-TAXI</b> - мобильное приложение и сайт для прямого соединения клиента и водителя,
            исключая расходы на диспетчерскую систему.</p>
        </div>
        <div class="bs-callout bs-callout-success">
        <p><b>SuperSIM - Выгодный звонок</b> - мобильное приложение для автоматического поиска оптимальных тарифов и операторов связи
        на основе анализа местоположения абонента и статистики его звонков.</p>
        </div>
        <div style="">
            <img class="img-responsive bordered-white"  src="/img/map.png"/>
        </div>
    </div>

</div>
