<?php
/**
 * Виджет для отображения плоского дерева с простыми связями
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
     * Содержащит наименования и ссылки на действия для кнопок:
     * 1)Удаление документа - delete
     * 2)Обновление документа - update
     * 3)Перемещение документа на 1 позицию выше в вертикальной плоскости - treeUp
     * 4)Перемещение документа на 1 позицию ниже в вертикальной плоскости - treeDown
     * 5)Перемещение докумеента во вложенный уровень документа, стоящего выше перемещаемого - treeRight
     * 6)Перемещение докумеента из вложенного уровня документа, на один уровень с ним - treeLeft
     *
     * @var array
     */
    public $buttons;

    /**
     * Содержит кофигурацию для DetailView
     *
     * @var array
     * @see DetailView
     */
    public $detailViewConfig;

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
                'base' => $this->base,
                'sources' => $this->sources,
                'buttons' => $this->buttons,
                'detailViewConfig' => $this->detailViewConfig,
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
