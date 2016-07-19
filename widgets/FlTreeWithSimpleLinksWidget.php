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
     *
     * @throws ErrorException
     */
    public function init()
    {
        if ($this->renderView === null) {
            $this->renderView = 'flTreeWithSimpleLinks';
        }

        $this->checkRunConfiguration(
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
        $simpleLinksParentDoc = ArrayHelper::getColumn($config['simpleLinks'], $config['nodeIdField']);

        $key = array_search($value->{$config['nodeIdField']}, $simpleLinksParentDoc);

        return ($key !== false);
    }

    /**
     * Проверяем на наличие и соответствие всех необходимых параметов в конфигурации
     *
     * @param array $config - конфиграция для проверки
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected function checkRunConfiguration(array $config)
    {
        static::checkParamIsNotEmptyAndString($config['renderView']);
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'title');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'titleLink');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'nodeName');
        static::checkParamInArrayExistAndNotEmptyAndArray($config['sources'], 'flTreeUrl');
        static::checkParamInArrayExistAndNotEmptyAndArray($config['sources'], 'flTreeWithSimpleUrl');
        static::checkParamIsNotEmptyAndArray($config['detailViewConfig']);
        static::checkRunParamButtons($config['buttons']);
    }

    /**
     * Проверяем конфигурацию кнопок
     *
     * @param array $buttons - массив с конфигурацией для кнопок
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkRunParamButtons(array $buttons)
    {
        static::checkRunParamButton($buttons, 'update');
        static::checkRunParamButton($buttons, 'delete');
        static::checkRunParamButton($buttons, 'treeUp');
        static::checkRunParamButton($buttons, 'treeDown');
        static::checkRunParamButton($buttons, 'treeRight');
        static::checkRunParamButton($buttons, 'treeLeft');
    }

    /**
     * Проверяем конфигурацию кнопки
     *
     * @param array  $buttons - массив с конфигурацией кнопок
     * @param string $button  - наименование кнопки в конфигурации
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkRunParamButton(array $buttons, $button)
    {
        static::checkParamIsExistInArray($buttons, $button);
        static::checkParamIsNotEmptyAndArray($buttons[$button]);

        static::checkParamIsExistInArray($buttons[$button], 'name');
        static::checkParamIsNotEmptyAndString($buttons[$button]['name']);

        static::checkParamIsExistInArray($buttons[$button], 'url');
        static::checkParamIsNotEmptyAndArray($buttons[$button]['url']);
    }


    /**
     * Проверяем на наличие всех необходимых параметов в конфигурации
     *
     * @param array $config - конфиграция для проверки
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkStructureConfiguration(array $config)
    {
        static::checkParamIsExistInArray($config, 'simpleLinks');
        static::checkParamIsNotEmptyAndString($config['simpleLinks']);

        static::checkParamIsExistInArray($config, 'nodeIdField');
        static::checkParamIsNotEmptyAndString($config['nodeIdField']);

        static::checkParamIsExistInArray($config, 'links');

        static::checkStructureParamLinks($config['links'], 'next');
        static::checkStructureParamLinks($config['links'], 'child');
        static::checkStructureParamLinks($config['links'], 'addSimple');
        static::checkStructureParamLinks($config['links'], 'delSimple');
    }
}
