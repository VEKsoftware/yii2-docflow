<?php
namespace docflow\base;

use Yii;
use yii\base\ErrorException;

use docflow\Docflow;
use docflow\base\MultipleActiveRecord;

class CommonRecord extends MultipleActiveRecord
{
    public static function getDb() {
        $instance = Docflow::getInstance();
        if($instance === NULL) {
            throw new ErrorException('You should use this class through yii2-status module.');
        } elseif(!$instance->db) {
            $db = 'db';
        } else {
            $db = $instance->db;
        }
        return Yii::$app->get($db);
    }
}
