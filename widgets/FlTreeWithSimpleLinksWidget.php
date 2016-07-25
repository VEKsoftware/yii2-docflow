<?php
/**
 * Виджет для отображения плоского дерева с простыми связями
 */

namespace docflow\widgets;

use docflow\models\Document;
use docflow\widgets\helpers\FlTreeWidgetsHelper;
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
     *
     * @throws ErrorException
     */
    public function init()
    {
        if ($this->renderView === null) {
            $this->renderView = 'flTreeWithSimpleLinks';
        }

        FlTreeWidgetsHelper::checkFlTreeWithSimpleLinksWidgetRunConfig(
            [
                'renderView' => $this->renderView,
                'base' => $this->base,
                'sources' => $this->sources,
                'buttons' => $this->buttons,
                'detailViewConfig' => $this->detailViewConfig,
            ]
        );
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
     * Получаем структуру для treeview
     *
     * @param ActiveDataProvider $docADP - провайдер с документами
     * @param array              $config - конфигурация
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    public static function getStructure(ActiveDataProvider $docADP, array $config)
    {
        FlTreeWidgetsHelper::checkFlTreeWithSimpleLinksWidgetStructureConfig($config);

        return array_merge(
            static::prepareMainStructure($docADP, $config),
            static::prepareNextPageButtonStructure($docADP, $config)
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
            'text' => $value->docName,
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
        $simpleLinksParentDoc = ArrayHelper::getColumn($config['simpleLinks'], $config['nodeIdField']);

        $key = array_search($value->{$config['nodeIdField']}, $simpleLinksParentDoc);

        return ($key !== false);
    }
}
