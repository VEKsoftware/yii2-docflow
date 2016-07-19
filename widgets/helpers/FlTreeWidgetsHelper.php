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
        static::checkParamIsNotEmptyAndString($config['renderView'], 'renderView');

        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'titleList', 'base');

        static::checkParamInArrayExistAndNotEmptyAndArray($config['sources'], 'flTreeUrl', 'sources');
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
        static::checkParamIsExistInArray($config, 'links', 'links');

        static::checkStructureParamLinks($config['links'], 'documentView', 'links');
        static::checkStructureParamLinks($config['links'], 'next', 'links');
        static::checkStructureParamLinks($config['links'], 'child', 'links');
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
        static::checkParamIsNotEmptyAndString($config['renderView'], 'renderView');

        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'title', 'base=>title');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'titleLink', 'base=>titleLink');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'nodeName', 'base=>nodeName');

        static::checkParamInArrayExistAndNotEmptyAndArray($config['sources'], 'flTreeUrl', 'sources=>flTreeUrl');
        static::checkParamInArrayExistAndNotEmptyAndArray(
            $config['sources'],
            'flTreeWithSimpleUrl',
            'sources=>flTreeWithSimpleUrl'
        );

        static::checkParamIsNotEmptyAndArray($config['detailViewConfig'], 'detailViewConfig');

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
        static::checkParamIsExistInArray($config, 'simpleLinks', 'simpleLinks');
        static::checkParamIsArray($config['simpleLinks'], 'simpleLinks');

        static::checkParamIsExistInArray($config, 'nodeIdField', 'nodeIdField');
        static::checkParamIsNotEmptyAndString($config['nodeIdField'], 'nodeIdField');

        static::checkParamIsExistInArray($config, 'links', 'links');

        static::checkStructureParamLinks($config['links'], 'next', 'links');
        static::checkStructureParamLinks($config['links'], 'child', 'links');
        static::checkStructureParamLinks($config['links'], 'addSimple', 'links');
        static::checkStructureParamLinks($config['links'], 'delSimple', 'links');
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
        /* Формируем карту-путь, для определения места где в конфиге ошибка */
        $paramRouteButton = '=>' . $button;
        $paramRouteButtonName = $paramRouteButton . '=>name';
        $paramRouteButtonUrl = $paramRouteButton . '=>url';

        static::checkParamIsExistInArray($buttons, $button, $paramRouteButton);
        static::checkParamIsNotEmptyAndArray($buttons[$button], $paramRouteButton);

        static::checkParamIsExistInArray($buttons[$button], 'name', $paramRouteButtonName);
        static::checkParamIsNotEmptyAndString($buttons[$button]['name'], $paramRouteButtonName);

        static::checkParamIsExistInArray($buttons[$button], 'url', $paramRouteButtonUrl);
        static::checkParamIsNotEmptyAndArray($buttons[$button]['url'], $paramRouteButtonUrl);
    }

    /**
     * Проверяем конфигурацию ссылки
     *
     * @param array  $links      - массив с ссылками
     * @param string $linkName   - имя ссылки
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkStructureParamLinks(array $links, $linkName, $paramRoute)
    {
        $paramRoute .= '=>' . $linkName;

        static::checkParamIsExistInArray($links, $linkName, $paramRoute);
        static::checkStructureParamLink($links[$linkName], $paramRoute);
    }

    /**
     * Проверяем ссылку на верность конфигурации
     *
     * @param mixed  $link       - конфигурация ссылки
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkStructureParamLink($link, $paramRoute)
    {
        /* Формируем карту-путь, для определения места где в конфиге ошибка */
        $paramRouteRoute = $paramRoute . '=>route';
        $paramRouteParams = $paramRoute . '=>params';

        static::checkParamIsArray($link, $paramRoute);
        static::checkParamInArrayExistAndNotEmptyAndString($link, 'route', $paramRouteRoute);

        static::checkParamIsExistInArray($link, 'params', $paramRouteParams);

        if (!empty($link['params'])) {
            static::checkParamIsArray($link['params'], $paramRouteParams);
            static::checkStructureParamValue($link['params'], $paramRouteParams);
        }
    }

    /**
     * Проверяем параметры ссылки
     *
     * @param array  $params     - парасетры ссылки
     * @param string $paramRoute - картуа-путь до параметра, в котором несоответствие
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected static function checkStructureParamValue($params, $paramRoute)
    {
        foreach ($params as $key => $paramValue) {
            if (is_array($paramValue)) {
                /* Формируем карту-путь, для определения места где в конфиге ошибка */
                $paramRouteValue = $paramRoute . '=>' . $key . '=>value';
                $paramRouteType = $paramRoute . '=>' . $key . '=>type';

                static::checkParamIsExistInArray($paramValue, 'value', $paramRouteValue);
                static::checkParamIsNotEmpty($paramValue['value'], $paramRouteValue);

                static::checkParamInArrayExistAndNotEmptyAndString($paramValue, 'type', $paramRouteType);
            }
        }
    }
}
