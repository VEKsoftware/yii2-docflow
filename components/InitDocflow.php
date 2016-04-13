<?php
namespace docflow\components;

use Yii;
use yii\base\Component;
use yii\base\BootstrapInterface;

class InitDocflow extends Component implements BootstrapInterface
{
    public function bootstrap($app) {
        parent::init();

        $app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, function ($event) use $app {
            $app->set('doc', 'docflow\components\DocComponent');
        });
    }
}
