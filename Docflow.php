<?php

namespace docflow;

use yii\base\ErrorException;
use yii\base\Module;

/**
 * Main class for yii2-docflow module.
 */
class Docflow extends Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'docflow\controllers';

    /** @var string $db Database component to use in the module */
    public $db;

    public $accessClass;

    /**
     * @inherit
     */
    public function init()
    {
        parent::init();
//        $this->checkAccessClassConfig();
        $this->registerTranslations();

        $this->defaultRoute = 'doc-types/index';
        $this->controllerMap = [
            'test-users' => 'docflow\examples\users\controllers\TestUsersController'
        ];
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
     */
    public function registerTranslations()
    {
        \Yii::$app->i18n->translations['docflow'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en',
            'basePath' => '@docflow/messages',
            'fileMap' => [
                'docflow' => 'docflow.php',
            ],
        ];
    }
}
