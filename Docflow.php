<?php

namespace docflow;

use yii\base\Application;
use yii\base\ErrorException;
use yii\base\Module;

/**
 * Main class for yii2-docflow module.
 */
class Docflow extends Module
{
    /** @var string $db Database component to use in the module */
    public $db;

    public $accessClass;

    /**
     * @inherit
     */
    public function init()
    {
        parent::init();
        $this->registerTranslations();
        $this->setControllers(\Yii::$app);
    }

    /**
     * Метод выполняется при загрузке приложения
     *
     * @param Application $app - приложение
     *
     * @return void
     */
    public function bootstrap($app)
    {
        $this->setControllers($app);
    }

    /**
     * Устанавливаем контроллеры в зависимости от типа приложения
     *
     * @param Application $app - приложение
     *
     * @return void
     */
    protected function setControllers($app)
    {
        if ($app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'docflow\console\controllers';
        }

        if ($app instanceof \yii\web\Application) {
            $this->controllerNamespace = 'docflow\controllers';
            $this->defaultRoute = 'doc-types/index';
            $this->controllerMap = [
                'test-users' => 'docflow\examples\users\controllers\TestUsersController',
                'test-jsonb' => 'docflow\examples\jsonb\controllers\JsonBController'
            ];
        }
    }

    /**
     * @inherit
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => $this->accessClass,
            ],
        ];
    }

    protected function checkAccessClassConfig()
    {
        $reflectionClass = new \ReflectionClass($this->accessClass);
        if ($reflectionClass->implementsInterface('\docflow\AccessInterface') === false) {
            throw new ErrorException('\docflow\Docflow::$accessClass must implement \docflow\AccessInterface.');
        }
    }

    /**
     * Initialization of the i18n translation module.
     *
     * @return void
     */
    public function registerTranslations()
    {
        \Yii::$app->i18n->translations['docflow'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en',
            'basePath' => '@docflow/messages',
            'fileMap' => ['docflow' => 'docflow.php'],
        ];
    }
}
