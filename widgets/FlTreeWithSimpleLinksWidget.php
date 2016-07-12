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
use yii\helpers\Url;

class FlTreeWithSimpleLinksWidget extends FlTreeWidget
{
    /**
     * Получаем основную структуру
     *
     * @param ActiveDataProvider $docADP - провайдер с документами
     * @param array              $config - конфигурация
     *
     * @return array
     * @throws \yii\base\InvalidParamException
     */
    protected static function prepareMainStructure(ActiveDataProvider $docADP, array $config)
    {
        return array_map(
            function (Document $value) use ($config) {
                $main = static::getMainPart($value, $config);
                $self = static::getSelfPart($value, $config);
                $child = static::getChildPart($value, $config);
                $simple = static::getSimplePart($value, $config);

                return array_merge(
                    $main,
                    $self,
                    $child,
                    $simple
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
     * @throws InvalidParamException
     */
    protected static function getMainPart(Document $value, array $config)
    {
        return [
            'text' => $value->{$value->docNameField()},
            'href_addSimple' => Url::toRoute(
                [
                    $config['routeAddSLink'],
                    'fromNodeId' => $config['fromNodeId'],
                    'toNodeId' => $value->{$config['toNodeIdField']}
                ]
            ),
            'href_delSimple' => Url::toRoute(
                [
                    $config['routeDelSLink'],
                    'fromNodeId' => $config['fromNodeId'],
                    'toNodeId' => $value->{$config['toNodeIdField']}
                ]
            ),
        ];
    }

    /**
     * Формируем и получаем часть структуры, которая красит элемент в цвет, если он равен родительскому
     *
     * @param Document $value  - объект докмента
     * @param array    $config - конфигурация
     *
     * @return array
     */
    protected static function getSelfPart(Document $value, array $config)
    {
        $self = [];

        if ((int)$config['fromNodeId'] === (int)$value->{$config['toNodeIdField']}) {
            $self = ['backColor' => 'gray'];
        }

        return $self;
    }

    /**
     * Формируем и получаем часть структуры, которая описывает наличие детей у документа
     *
     * @param Document $value  - объект докмента
     * @param array    $config - конфигурация
     *
     * @return array
     *
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
                'href_child' => Url::toRoute(
                    [
                        $config['routeChild'],
                        'fromNodeId' => $config['fromNodeId'],
                        'currentNodeId' => $value->{$config['toNodeIdField']},
                        'extra' => $config['extra'],
                    ]
                ),
                'tags' => [$countChild],
            ];
        }

        return $child;
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
     * Формируем и получаем структуру, описывающую элемент - подгружащий документы, которые не вошли в прошлую выборку
     *
     * @param array   $config    - конфигурация
     * @param integer $countLast - количество оставшихся документов не раскрытыми
     *
     * @return array
     *
     * @throws InvalidParamException
     */
    protected static function getNextPagePart(array $config, $countLast)
    {
        return [
            [
                'text' => '...',
                'href_next' => Url::toRoute([
                    $config['routeNext'],
                    'page' => ++$config['page'],
                    'fromNodeId' => $config['fromNodeId'],
                    'currentNodeId' => $config['currentNodeId'],
                    'extra' => $config['extra'],
                ]),
                'tags' => [$countLast]
            ]
        ];
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
        $simpleLinksParentDoc = ArrayHelper::getColumn($config['simpleLinksParentDocument'], 'id');

        $key = array_search($value->{$config['toNodeIdField']}, $simpleLinksParentDoc);

        return ($key !== false) ? true : false;
    }

    /**
     * Добавляем к структуре кнопку для отображения следующего набора данных(следующая страница)
     *
     * @param ActiveDataProvider $documentsADP - провайдер с документами
     * @param array              $config       - конфигурация
     *
     * @return array
     *
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

        if (!array_key_exists('currentDocIdentVal', $config)) {
            throw new ErrorException('Ключ currentDocIdentVal не найден в конфигурации');
        }

        if (!array_key_exists('routeAddSLink', $config)
            || (empty($config['routeAddSLink']))
            || (!is_string($config['routeAddSLink']))
        ) {
            throw new ErrorException('Ключ routeAddSLink не найден в конфигурации или пустое значение или не строковый тип');
        }

        if (!array_key_exists('routeDelSLink', $config)
            || (empty($config['routeDelSLink']))
            || (!is_string($config['routeDelSLink']))
        ) {
            throw new ErrorException('Ключ routeDelSLink не найден в конфигурации или пустое значение или не строковый тип');
        }
    }
}
