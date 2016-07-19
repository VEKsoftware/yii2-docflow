<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 13:03
 */

namespace docflow\widgets;

use docflow\models\Document;
use docflow\widgets\helpers\FlTreeWidgetsHelper;
use yii\base\ErrorException;
use yii\base\InvalidParamException;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;

class FlTreeWidget extends Widget
{
    /**
     * Имя представления для отображения данных
     *
     * @var string
     */
    public $renderView;

    /**
     * Содержит инфорацию о:
     *
     * FlTreeWidget:
     * 1)Название списка плоского дерева - titleList
     *
     * FlTreeWithSimpleLinksWidget:
     * 1)Названии документа - title
     * 2)Названии плоского дерева с простыми связями - titleLink
     * 3)Имя текущего документа, которое отображается в виджете - nodeName
     *
     * @var array
     */
    public $base;

    /**
     * Содержит Url до действий, возвращающих первичную структуру деревьев:
     *
     * FlTreeWidget:
     * 1)Плоское дерево - flTreeUrl
     *
     * FlTreeWithSimpleLinksWidget:
     * 1)Плоское дерево - flTreeUrl
     * 2)Плоское дерево с простыми связями - flTreeWithSimpleUrl
     *
     * @var array
     */
    public $sources;

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
            $this->renderView = 'flTree';
        }

        FlTreeWidgetsHelper::checkFlTreeWidgetRunConfig(
            [
                'renderView' => $this->renderView,
                'base' => $this->base,
                'sources' => $this->sources
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
        return $this->render($this->renderView, ['base' => $this->base, 'sources' => $this->sources]);
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
        FlTreeWidgetsHelper::checkFlTreeWidgetStructureConfig($config);

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
                    static::getChildPart($value, $config)
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
            'href' => static::getLink($config['links']['documentView'], $value),
        ];
    }

    /**
     * Формируем и получаем часть структуры, которая описывает наличие детей у документа
     *
     * @param Document $value  - объект докмента
     * @param array    $config - конфигурация
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    protected static function getChildPart(Document $value, array $config)
    {
        $child = [];

        /* Проверяем, есть-ли у документа подчиненные документы */
        $haveChild = static::checkChild($value);

        /* Получаем количество подчиненных документов у текущего, для отображения в тэге дерева */
        $countChild = count($value->linksTo);

        if ($haveChild === true) {
            $child = [
                'href_child' => static::getLink($config['links']['child'], $value),
                'tags' => [$countChild, 'Вложений: '],
            ];
        }

        return $child;
    }

    /**
     * Формируем ссылку на просмотр документа
     *
     * @param array         $linkParam - содержит маршрут и параметры к маршруту
     * @param Document|null $document  - объект документа
     *
     * @return string
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    protected static function getLink(array $linkParam, $document = null)
    {
        $routeArray = [$linkParam['route']];

        if ($linkParam['params'] !== null) {
            $routeArray = array_merge(
                static::prepareLinkParam($linkParam['params'], $document),
                $routeArray
            );
        }

        return Url::to($routeArray);
    }

    /**
     * Подготавливаем параметры ссылки
     *
     * @param array    $params   - массив параметров
     * @param Document $document - объект документа
     *
     * @return array
     *
     * @throws ErrorException
     */
    protected static function prepareLinkParam($params, $document)
    {
        $paramsArray = [];

        foreach ($params as $key => $value) {
            $paramValue = '';

            if (!is_array($value)) {
                $paramValue = $value;
            } else {
                if (($document === null) && (($value['type'] === 'property') || ($value['type'] === 'function'))) {
                    throw new ErrorException('Ошибка формирования URL: Если отсутствует объект, то невозможно использовать типы property и function');
                }

                /* Если тип value, то значение берем из  $value['value'] */
                if ($value['type'] === 'value') {
                    $paramValue = $value['value'];
                }

                /* Если тип property, то значение берем из свойства, указанном в $value['value'], объекта $document */
                if ($value['type'] === 'property') {
                    $paramValue = $document->{$value['value']};
                }

                /* Если тип function, то значение берем из метода, указанном в $value['value'], объекта $document */
                if ($value['type'] === 'function') {
                    $paramValue = call_user_func([$document::className(), $value['value']]);
                }
            }

            $paramsArray[$key] = $paramValue;
        }

        return $paramsArray;
    }

    /**
     * Добавляем к структуре кнопку для отображения следующего набора данных(следующая страница)
     *
     * @param ActiveDataProvider $documentsADP - провайдер с документами
     * @param array              $config       - конфигурация
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    protected static function prepareNextPageButtonStructure(ActiveDataProvider $documentsADP, array $config)
    {
        /* Структура следующей страницы */
        $nextPage = [];

        $pagination = $documentsADP->pagination;
        /* К номеру страницы добавляем 1 т.к нумерация в пагинации начинается с 0 */
        $currentPage = ($pagination->page + 1);
        /* Количество документов на странице */
        $pageSize = $pagination->pageSize;

        /* Получаем общее количество оставшихся документов для подгрузки */
        $countLast = ($documentsADP->totalCount - ($currentPage * $pageSize));

        if ($currentPage < $pagination->pageCount) {
            $nextPage = static::getNextPagePart($config, $countLast);
        }

        return $nextPage;
    }

    /**
     * Формируем и получаем структуру, описывающую элемент - подгружащий документы, которые не вошли в прошлую выборку
     *
     * @param array   $config    - конфигурация
     * @param integer $countLast - количество оставшихся документов не раскрытыми
     *
     * @return array
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    protected static function getNextPagePart(array $config, $countLast)
    {
        return [
            [
                'text' => '...',
                'href_next' => static::getLink($config['links']['next']),
                'tags' => [$countLast, 'Осталось: ']
            ]
        ];
    }

    /**
     * Проверяем, есть-ли у документа подчиненные документы
     *
     * @param Document $document - документ
     *
     * @return bool
     */
    protected static function checkChild(Document $document)
    {
        $haveChild = false;

        if (count($document->linksTo) > 0) {
            $haveChild = true;
        }

        return $haveChild;
    }
}
