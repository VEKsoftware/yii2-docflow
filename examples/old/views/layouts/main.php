<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\widgets\Menu;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use taxi\assets\AppAsset;
use common\widgets\Alert;
use common\models;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"><!-- "?> -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">

    <?= $this->render('_menu',[
//        'menuItems' => $menuItems,
    ]); ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>

        <div class="content-container">
        <?= $content ?>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
    <?php
        echo Menu::widget([
            'items' => [
                ['label' => Yii::t('app','Home'), 'url' => ['/site/index']],
                ['label' => Yii::t('app','About'), 'url' => ['/site/page','view'=>'about']],
                [
                    'label' => Yii::t('app','Public Offer'),
                    'url' => ['/site/page','view'=>'pub-offer'],
/*
                    'items' => [
                        [ 'label' => Yii::t('app','For passengers'), 'url' => ['/site/page','view'=>'pub-offer-pass'] ],
                        [ 'label' => Yii::t('app','For drivers'), 'url' => ['/site/page','view'=>'pub-offer-drv'] ],
                        [ 'label' => Yii::t('app','Partnership'), 'url' => ['/site/page','view'=>'pub-offer-partn'] ],
                        [ 'label' => Yii::t('app','Personal Data'), 'url' => ['/site/page','view'=>'pub-offer-persdat'] ],
                    ],
*/
                ],
                ['label' => Yii::t('app','Contact'), 'url' => ['/site/contact']],
            ],
            'activateParents'=>true,
            'options' => [
                'class' => 'navbar-nav nav',
            ],
        ]);
    ?>
    </div>
    <div class="container">
        <p class="pull-left">&copy; <?= Yii::$app->params['companyName'].' '.date('Y') ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
