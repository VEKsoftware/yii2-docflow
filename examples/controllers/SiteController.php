<?php
namespace test\controllers;

use Yii;
use yii\base\InvalidParamException;

use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use common\models\Version;
use partneruser\models\LoginForm;


/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'page' => [
                'class' => 'yii\web\ViewAction',
                'viewPrefix' => Yii::$app->language,
            ],
            'timezone' => [
                'class' => 'yii2mod\timezone\TimezoneAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new Version();

        return $this->render('index', [
            'model' => $model,
        ]);
    }

    /**
     * User has been signed up. This is the next page.
     *
     * @return mixed
     */
    public function actionSignedup()
    {
        return $this->render('signedup', [
        ]);
    }

}
