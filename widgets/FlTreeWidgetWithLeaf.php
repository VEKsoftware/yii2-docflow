<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 02.08.16
 * Time: 9:54
 */

namespace docflow\widgets;

use docflow\models\Document;
use docflow\widgets\helpers\FlTreeWidgetsHelper;
use yii\base\ErrorException;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;

class FlTreeWidgetWithLeaf extends FlTreeWidget
{
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
            $this->renderView = 'flTreeWithLeaf';
        }

        FlTreeWidgetsHelper::checkFlTreeWidgetAndWithLeafRunConfig(
            [
                'renderView' => $this->renderView,
                'base' => $this->base,
                'widget' => $this->widget
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
        FlTreeWidgetsHelper::checkFlTreeWidgetWithLeafStructureConfig($config);

        return array_merge(
            static::prepareMainStructure($docADP, $config),
            static::prepareNextPageButtonStructure($docADP, $config)
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
            'href' => static::getLink($config['links']['documentView'], $value),
        ];
    }
}
