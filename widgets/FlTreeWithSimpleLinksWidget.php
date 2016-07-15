<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 13:03
 */

namespace docflow\widgets;

use docflow\models\Document;
use yii\base\ErrorException;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

class FlTreeWithSimpleLinksWidget extends FlTreeWidget
{
    /**
     * Тайтл документа
     *
     * @var string
     */
    public $title;

    /**
     * Тайтл плоского дерева с простыми связями
     *
     * @var string
     */
    public $titleLink;

    /**
     * Содержащит наименования и ссылки на действия для кнопок
     *
     * @var array
     */
    public $buttons;

    /**
     * Содержит кофигурацию для DataViewWidget
     *
     * @var array
     */
    public $dataViewConfig;

    /**
     * Имя - идентификатор ноды, записываемый в text
     *
     * @var string
     */
    public $nodeName;

    /**
     * Url на начальные данные плоского дерева с простыми связями
     *
     * @var string
     */
    public $flTreeWithSimpleUrl;

    /**
     * Инициализируем виджет
     *
     * @return void
     */
    public function init()
    {
        if ($this->renderView === null) {
            $this->renderView = 'flTreeWithSimpleLinks';
        }
        
        //TODO в init необходимо проводит проверку на наличии требуемых данных
    }

    /**
     * Выполняем виджет
     *
     * @return string
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    public function run()
    {
        return $this->render(
            $this->renderView,
            [
                'title' => $this->title,
                'titleLink' => $this->titleLink,
                'buttons' => $this->buttons,
                'dataViewConfig' => $this->dataViewConfig,
                'nodeName' => $this->nodeName,
                'flTreeUrl' => $this->flTreeUrl,
                'flTreeWithSimpleUrl' => $this->flTreeWithSimpleUrl
            ]
        );
    }

    /**
     * Получаем основную структуру
     *
     * @param ActiveDataProvider $docADP - провайдер с документами
     * @param array              $config - конфигурация
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    protected static function prepareMainStructure(ActiveDataProvider $docADP, array $config)
    {
        return array_map(
            function (Document $value) use ($config) {
                return array_merge(
                    static::getMainPart($value, $config),
                    static::getChildPart($value, $config),
                    static::getSimplePart($value, $config)
                );
            },
            array_values($docADP->models)
        );
    }

    /**
     * Формируем и получаем основную часть структуры
     *
     * @param Document $value  - объект докмента
     * @param array    $config - конфигурация
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    protected static function getMainPart(Document $value, array $config)
    {
        return [
            'text' => $value->{$value->docNameField()},
            'href_addSimple' => static::getLink($config['links']['addSimple'], $value),
            'href_delSimple' => static::getLink($config['links']['delSimple'], $value),
        ];
    }

    /**
     * Формируем и получаем часть структуры, которая описывает наличие простых связей у документа
     *
     * @param Document $value  - объект докмента
     * @param array    $config - конфигурация
     *
     * @return array
     */
    protected static function getSimplePart(Document $value, array $config)
    {
        $simple = [];

        $haveSimpleLink = static::checkSimpleLink($value, $config);

        if ($haveSimpleLink === true) {
            $simple = ['state' => ['checked' => true]];
        }

        return $simple;
    }

    /**
     * Проверяем, есть-ли простая связь между родительским и текущим документами
     *
     * @param Document $value  - объект документа
     * @param array    $config - конфигурация
     *
     * @return bool
     */
    protected static function checkSimpleLink(Document $value, array $config)
    {
        $simpleLinksParentDoc = ArrayHelper::getColumn($config['simpleLinksParentDocument'], $config['nodeIdField']);

        $key = array_search($value->{$config['nodeIdField']}, $simpleLinksParentDoc);

        return ($key !== false);
    }

    /**
     * Проверяем на наличие всех необходимых параметов в конфигурации
     *
     * @param array $config - конфигурация
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkConfiguration(array $config)
    {
        parent::checkConfiguration($config);

    }
}
