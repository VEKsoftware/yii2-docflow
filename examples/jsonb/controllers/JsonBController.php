<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.07.16
 * Time: 18:09
 */

namespace docflow\examples\jsonb\controllers;

use docflow\base\JsonB;
use docflow\examples\jsonb\models\JsonBTest;
use yii\web\Controller;

class JsonBController extends Controller
{
    public $defaultAction = 'get-one';

    /**
     * Смотрим _hiddenAttributes у всёх записей
     *
     * @return void
     */
    public function actionGetAll()
    {
        $all = JsonBTest::getAll();
        foreach ($all as $one) {
            var_dump($one->hiddenAttributes);
        }
    }

    /**
     * Смотрим _hiddenAttributes у 3 записи
     *
     * @return void
     */
    public function actionGetOne()
    {
        $all = JsonBTest::getAll();
        $one = $all[2];
        echo '------------------------------------------Все-------------------------------------------------';
        var_dump($one->hiddenAttributes);
        echo '----------------------------------------Контакты----------------------------------------------';
        var_dump($one->contacts);
        echo '-----------------------------------------Карты------------------------------------------------';
        var_dump($one->payment_cards);
        echo '----------------------------------Контакты => Соц сети----------------------------------------';
        var_dump($one->contacts->{'соц сети'});
        echo '---------------------------Контакты => Соц сети => Вконтакте----------------------------------';
        var_dump($one->contacts->{'соц сети'}->вконтакте);
        echo '---------------------------Контакты => Соц сети => Facebook-----------------------------------';
        var_dump($one->contacts->{'соц сети'}->лицокнига);
    }

    /**
     * Массовая установка _hiddenAttributes у загруженного объекта
     *
     * @return void
     */
    public function actionSetHiddenAttributeNotNewRecord()
    {
        $all = JsonBTest::getAll();
        $one = $all[0];
        echo '----------------------------------Начальное значение------------------------------------------';
        var_dump($one->hiddenAttributes);
        echo '-------------------------------Устанавливаемое значение---------------------------------------';
        $array = [
            'referees' => [1 => 'Вася', 2 => 'Петя', 3 => 'Мирослав'],
            'contacts' => 'asdasd',
            'payment_cards' => [1 => 'asd', 2 => 'zxc', 3 => 'qwe']
        ];
        var_dump($array);
        echo '--------------------------------Установленное значение----------------------------------------';
        $one->hiddenAttributes = $array;
        var_dump($one->hiddenAttributes);
    }

    /**
     * Массовая установка _hiddenAttributes у нового объекта
     *
     * @return void
     */
    public function actionSetHiddenAttributeNewRecord()
    {
        $one = new JsonBTest();
        echo '----------------------------------Начальное значение------------------------------------------';
        var_dump($one->hiddenAttributes);
        echo '-------------------------------Устанавливаемое значение---------------------------------------';
        var_dump([
            'referees' => [1 => 'Вася', 2 => 'Петя', 3 => 'Мирослав'],
            'contacts' => [1 => 123, 2 => 456, 3 => 789],
            'payment_cards' => [1 => 'asd', 2 => 'zxc', 3 => 'qwe']
        ]);
        echo '--------------------------------Установленное значение----------------------------------------';
        $one->hiddenAttributes = [
            'referees' => [1 => 'Вася', 2 => 'Петя', 3 => 'Мирослав'],
            'contacts' => [1 => 123, 2 => 456, 3 => 789],
            'payment_cards' => [1 => 'asd', 2 => 'zxc', 3 => 'qwe']
        ];
        var_dump($one->hiddenAttributes);
    }

    /**
     * Добавляем атттрибуты в jsonB
     *
     * @return void
     */
    public function actionAddAttributesInJsonB()
    {
        echo '-------------------------------Устанавливаемое значение---------------------------------------';
        var_dump($addAttributes = [
            'кафтан' => 123,
            'поло' => 233,
            'мигрень' => null,
            'числовой' => [1, 2, 3, 4],
            'работа' => [
                'пн' => '9:00-13:00, 14:00-18:00',
                'вт' => '9:00-13:00, 14:00-18:00',
                'ср' => '9:00-13:00, 14:00-18:00',
                'чт' => '9:00-13:00, 14:00-18:00',
                'пт' => '9:00-13:00, 14:00-18:00'
            ]
        ]);

        $jsonB = new JsonB($addAttributes);
        echo '--------------------------------Установленное значение----------------------------------------';
        var_dump($jsonB);
        var_dump($jsonB->работа);
    }

    /**
     * Устанавливаем значения присутствующим аттрибутам
     *
     * @return void
     */
    public function actionSetAttributesInJsonB()
    {
        $addAttributes = [
            'кафтан' => 123,
            'поло' => 233,
            'работа' => [
                'пн' => '9:00-13:00, 14:00-18:00',
                'вт' => '9:00-13:00, 14:00-18:00',
                'ср' => '9:00-13:00, 14:00-18:00',
                'чт' => '9:00-13:00, 14:00-18:00',
                'пт' => '9:00-13:00, 14:00-18:00'
            ]
        ];

        $jsonB = new JsonB($addAttributes);
        echo '----------------------------------Начальное значение------------------------------------------';
        var_dump($jsonB);
        var_dump($jsonB->работа);
        echo '-------------------------------Устанавливаемое значение---------------------------------------';
        var_dump($updateAttributes = [
            'кафтан' => ['модный', 'очень'],
            'работа' => 'не волк, в лес не убежит',
            'доширак' => 'не установится'
        ]);
        echo '--------------------------------Установленное значение----------------------------------------';
        $jsonB->setAttributes($updateAttributes);
        var_dump($jsonB);
        var_dump($jsonB->кафтан);
    }

    /**
     * Удалем аттрибуты из JsonB
     *
     * @return void
     */
    public function actionDelAttributesInJsonB()
    {
        $addAttributes = [
            'кафтан' => 123,
            'поло' => 233,
            'работа' => [
                'пн' => '9:00-13:00, 14:00-18:00',
                'вт' => '9:00-13:00, 14:00-18:00',
                'ср' => '9:00-13:00, 14:00-18:00',
                'чт' => '9:00-13:00, 14:00-18:00',
                'пт' => '9:00-13:00, 14:00-18:00'
            ]
        ];

        $jsonB = new JsonB($addAttributes);
        echo '----------------------------------Начальное значение------------------------------------------';
        var_dump($jsonB);
        var_dump($jsonB->работа);
        echo '----------------------------------Удаляемые значения------------------------------------------';
        var_dump($delAttributes = ['кафтан', 'доширак']);
        echo '--------------------------------Установленное значение----------------------------------------';
        $jsonB->delAttributes($delAttributes);
        var_dump($jsonB);
        var_dump($jsonB->работа);
    }

    /**
     * Получаем, изменяем и сохраняем измененные данные в
     *
     * @return void
     */
    public function actionSaveUnstructured()
    {
        $all = JsonBTest::getAll();
        $one = $all[0];
        echo '----------------------------------Начальное значение------------------------------------------';
        var_dump($one->hiddenAttributes);
        echo '-------------------------------Устанавливаемое значение---------------------------------------';
        $array = [
            'referees' => [1 => 'Вася', 2 => 'Петя', 3 => 'Мирослав', 4 => [11, 22, 33, 44]],
            'contacts' => 'asdasd',
            'payment_cards' => [1 => 'asd', 2 => 'zxc', 3 => 'qwe', 4 => [111, 222, 333, 444]]
        ];
        var_dump($array);
        echo '--------------------------------Установленное значение----------------------------------------';
        $one->hiddenAttributes = $array;
        var_dump($one->hiddenAttributes);
        echo '---------------------------------Сохраненное значение-----------------------------------------';
        $one->save();
        var_dump($one->hiddenAttributes);
        exit;
    }
}
