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
use yii\helpers\Url;

class FlTreeWithSimpleLinksWidget extends FlTreeWidget
{
    /**
     * Значение идентификатора документа, для которого работаем с простыми связями
     *
     * @var string
     */
    public $currentDocIdentVal;

    /**
     * Получаем основную структуру
     *
     * @param ActiveDataProvider $docADP - провайдер с документами
     * @param array              $config - конфигурация
     *
     * @return array
     * @throws \yii\base\InvalidParamException
     */
    protected static function getMainStructure(ActiveDataProvider $docADP, array $config)
    {
        return array_map(
            function (Document $value) use ($config) {
                /* Проверяем, есть-ли у документа подчиненные документы */
                $haveChild = static::checkChild($value);
                /* Получаем количество подчиненных документов у текущего, для отображения в тэге дерева */
                $countChild = count($value->linksTo);

                return array_merge(
                    [
                        'text' => $value->{$value->docName()},
                        'href_addSimple' => Url::toRoute(
                            [
                                $config['routeAddSLink'],
                                'docIdentValFrom' => $config['currentDocIdentVal'],
                                'docIdentValTo' => $value->{$config['docIdentField']}
                            ]
                        ),
                        'href_delSimple' => Url::toRoute(
                            [
                                $config['routeDelSLink'],
                                'docIdentValFrom' => $config['currentDocIdentVal'],
                                'docIdentValTo' => $value->{$config['docIdentField']}
                            ]
                        ),
                    ],
                    ($haveChild === true)
                        ?
                        [
                            'href_child' => Url::toRoute(
                                [
                                    $config['routeChild'],
                                    'docIdentVal' => $value->{$config['docIdentField']},
                                    'currentDocIdentVal' => $config['currentDocIdentVal'],
                                ]
                            ),
                            'tags' => [$countChild],
                        ]
                        : []
                );
            },
            array_values($docADP->models)
        );
    }

    /**
     * Добавляем к структуре кнопку для отображения следующего набора данных(следующая страница)
     *
     * @param ActiveDataProvider $documentsADP - провайдер с документами
     * @param array              $config       - конфигурация
     * @param array              $structure    - структура для передачи в treeview
     *
     * @return array
     *
     * @throws InvalidParamException
     */
    protected static function getNextPageButton(ActiveDataProvider $documentsADP, array $config, array $structure)
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
            $nextPage = [
                [
                    'text' => '...',
                    'href_next' => Url::toRoute([
                        $config['routeNext'],
                        'page' => ++$config['page'],
                        'docIdentVal' => $config['docIdentVal'],
                        'currentDocIdentVal' => $config['currentDocIdentVal'],
                    ]),
                    'tags' => [$countLast]
                ]
            ];
        }

        return array_merge($structure, $nextPage);
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
