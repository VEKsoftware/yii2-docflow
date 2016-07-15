<?php

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;

NavBar::begin([
    'brandLabel' => strtoupper(Yii::$app->params['companyName']),
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-fixed-top navbar-colored',
    ],
]);

$menuItems = [
    ['label' => Yii::t('app','Home'), 'url' => ['/site/index']],
];

$user = Yii::$app->user->identity;

if (Yii::$app->user->isGuest) {
    $menuItems[] = ['label' => 'Signup', 'url' => ['/partneruser/default/signup']];
    $menuItems[] = ['label' => 'Login', 'url' => ['/partneruser/default/login']];
} else {
    $menuItems[] = [
        'label' => 'Profile',
        'items' => array_merge(
            $user->isAllowed('partneruser.profile.invite') ? [[
                'label' => Yii::t('partneruser','Invite a Friend'),
                'url' => ['/partneruser/profile/invite'],
            ]] : [],
            $user->isAllowed('partneruser.profile.view') ? [[
                'label' => Yii::t('partneruser','Profile'),
                'url' => ['/partneruser/profile/view'],
            ]] : [],
            $user->isAllowed('partneruser.profile.index') ? [[
                'label' => Yii::t('partneruser','Referral Network'),
                'url' => ['/partneruser/profile/subscribers'],
            ]] : [],
            [[
                'label' => Yii::t('partneruser','Logout ({user})',['user' => Html::encode(Yii::$app->user->identity->username)]),
                'url' => ['/partneruser/default/logout'],
                'linkOptions' => ['data-method' => 'post'],
            ]],
        []),
    ];
}

echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right'],
    'items' => $menuItems,
]);
NavBar::end();
?>
