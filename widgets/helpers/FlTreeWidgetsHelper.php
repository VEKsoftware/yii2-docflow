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
    public static function checkFlTreeWidgetAndWithLeafRunConfig(array $config)
    {
        static::checkParamIsExistInArray($config, 'renderView', 'renderView');
        static::checkParamIsNotEmptyAndString($config['renderView'], 'renderView');

        static::checkParamIsArray($config['base'], 'base');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'titleList', 'base => titleList');

        static::checkParamIsArray($config['widget'], 'widget');
        static::checkParamInArrayExistAndNotEmptyAndArray($config['widget'], 'source', 'widget => source');
        static::checkParamInArrayExistAndNotEmptyAndBool($config['widget'], 'showCheckBox', 'widget => showCheckBox');
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

        static::checkStructureParamLinks($config['links'], 'next', 'links');
        static::checkStructureParamLinks($config['links'], 'child', 'links');
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
    public static function checkFlTreeWidgetWithLeafStructureConfig(array $config)
    {
        static::checkParamIsExistInArray($config, 'links', 'links');

        static::checkStructureParamLinks($config['links'], 'documentView', 'links');
        static::checkStructureParamLinks($config['links'], 'next', 'links');
        static::checkStructureParamLinks($config['links'], 'child', 'links');
    }

    /**
     * Проверяем конфигурацию FlTreeWidgetWithSimpleLinks при запуске виджета
     *
     * @param array $config - конфигурация
     *
     * @return void
     *
     * @throws ErrorException
     */
    public static function checkFlTreeWithSimpleLinksWidgetRunConfig(array $config)
    {
        static::checkParamIsExistInArray($config, 'renderView', 'renderView');
        static::checkParamIsNotEmptyAndString($config['renderView'], 'renderView');

        static::checkParamIsArray($config['base'], 'base');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'title', 'base=>title');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'titleLink', 'base=>titleLink');
        static::checkParamInArrayExistAndNotEmptyAndString($config['base'], 'nodeName', 'base=>nodeName');

        if (is_array($config['widget'])) {
            static::checkParamInArrayExistAndNotEmptyAndArray($config['widget'], 'source', 'widget => source');
            static::checkParamInArrayExistAndNotEmptyAndBool(
                $config['widget'],
                'showCheckBox',
                'widget => showCheckBox'
            );
        } elseif ($config['widget'] !== null) {
            throw new ErrorException('параметр widget не массив');
        }

        static::checkParamIsNotEmptyAndArray($config['detailViewConfig'], 'detailViewConfig');


        $buttonsExistInConfig = array_key_exists('buttons', $config);
        $buttonsIsArray = is_array($config['buttons']);
        $buttonsNotEmpty = (count($config['buttons']) > 0);

        if ($buttonsExistInConfig && $buttonsIsArray && $buttonsNotEmpty) {
            static::checkRunParamButtons($config['buttons']);
        }
    }

    /**
     * Проверяем конфигурацию FlTreeWidgetWithSimpleLinks при формировании структуры виджета
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
        if (array_key_exists('update', $buttons)) {
            static::checkRunParamButton($buttons, 'update');
        }

        if (array_key_exists('delete', $buttons)) {
            static::checkRunParamButton($buttons, 'delete');
        }

        if (array_key_exists('treeUp', $buttons)) {
            static::checkRunParamButton($buttons, 'treeUp');
        }

        if (array_key_exists('treeDown', $buttons)) {
            static::checkRunParamButton($buttons, 'treeDown');
        }

        if (array_key_exists('treeRight', $buttons)) {
            static::checkRunParamButton($buttons, 'treeRight');
        }

        if (array_key_exists('treeLeft', $buttons)) {
            static::checkRunParamButton($buttons, 'treeLeft');
        }
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
