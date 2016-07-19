<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.07.16
 * Time: 12:19
 */

namespace docflow\widgets\helpers;

use yii\base\ErrorException;

class FlTreeWidgetsHelper extends BaseFlTreeWidgetsHelper
{
    /**
     * Проверяем конфигурацию FlTreeWidget при запуске виджета
     *
     * @param array $config - конфигурация
     *
     * @return void
     *
     * @throws ErrorException
     */
    public static function checkFlTreeWidgetRunConfig(array $config)
    {
        static::checkParamIsNotEmptyAndString($config['renderView']);

        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'titleList');

        static::checkParamInArrayExistAndNotEmptyAndArray($config['sources'], 'flTreeUrl');
    }

    /**
     * Проверяем конфигурацию FlTreeWidget при формировании структуры виджета
     *
     * @param array $config - конфигурация
     *
     * @return void
     *
     * @throws ErrorException
     */
    public static function checkFlTreeWidgetStructureConfig(array $config)
    {
        static::checkParamIsExistInArray($config, 'links');

        static::checkStructureParamLinks($config['links'], 'documentView');
        static::checkStructureParamLinks($config['links'], 'next');
        static::checkStructureParamLinks($config['links'], 'child');
    }

    /**
     * Проверяем конфигурацию FlTreeWithSimpleLinksWidget при запуске виджета
     *
     * @param array $config - конфигурация
     *
     * @return void
     *
     * @throws ErrorException
     */
    public static function checkFlTreeWithSimpleLinksWidgetRunConfig(array $config)
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
     * Проверяем конфигурацию FlTreeWithSimpleLinksWidget при формировании структуры виджета
     *
     * @param array $config - конфигурация
     *
     * @return void
     *
     * @throws ErrorException
     */
    public static function checkFlTreeWithSimpleLinksWidgetStructureConfig(array $config)
    {
        static::checkParamIsExistInArray($config, 'simpleLinks');
        static::checkParamIsArray($config['simpleLinks']);

        static::checkParamIsExistInArray($config, 'nodeIdField');
        static::checkParamIsNotEmptyAndString($config['nodeIdField']);

        static::checkParamIsExistInArray($config, 'links');

        static::checkStructureParamLinks($config['links'], 'next');
        static::checkStructureParamLinks($config['links'], 'child');
        static::checkStructureParamLinks($config['links'], 'addSimple');
        static::checkStructureParamLinks($config['links'], 'delSimple');
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
     * Проверяем конфигурацию ссылки
     *
     * @param array  $links    - массив с ссылками
     * @param string $linkName - имя ссылки
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkStructureParamLinks(array $links, $linkName)
    {
        static::checkParamIsExistInArray($links, $linkName);
        static::checkStructureParamLink($links[$linkName]);
    }

    /**
     * Проверяем ссылку на верность конфигурации
     *
     * @param mixed $link - конфигурация ссылки
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkStructureParamLink($link)
    {
        static::checkParamIsArray($link);
        static::checkParamInArrayExistAndNotEmptyAndString($link, 'route');

        static::checkParamIsExistInArray($link, 'params');

        if (!empty($link['params'])) {
            static::checkParamIsArray($link['params']);
            static::checkStructureParamValue($link['params']);
        }
    }

    /**
     * Проверяем параметры ссылки
     *
     * @param array $params - парасетры ссылки
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkStructureParamValue($params)
    {
        foreach ($params as $paramValue) {
            if (is_array($paramValue)) {
                static::checkParamIsExistInArray($paramValue, 'value');
                static::checkParamIsNotEmpty($paramValue['value']);

                static::checkParamInArrayExistAndNotEmptyAndString($paramValue, 'type');
            }
        }
    }
}
