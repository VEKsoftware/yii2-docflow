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
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;

class FlTreeWidget extends Widget
{
    /**
     * Роут до метода с первоначальными данными
     *
     * @var string
     */
    public $dataUrl;

    /**
     * Имя представлени я для отображения данных
     *
     * @var string
     */
    public $renderView;

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
        return $this->render($this->renderView, ['dataUrl' => '\'' . $this->dataUrl . '\'']);
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
        static::checkConfiguration($config);

        $structure = static::getMainStructure($docADP, $config);
        $structure = static::getNextPageButton($docADP, $config, $structure);

        return $structure;
    }
    
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
                        //TODO надо сдлеать абстрактно переменные
                        'href' => Url::to(['status-view', 'doc' => $value->docTag(), 'tag' => $value->tag, 'currentDocIdentVal' => $value->tag]),
                    ],
                    ($haveChild === true)
                        ?
                        [
                            'href_child' => Url::toRoute(
                                [
                                    $config['routeChild'],
                                    'docIdentVal' => $value->{$config['docIdentField']}
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
                        'docIdentVal' => $config['docIdentVal']
                    ]),
                    'tags' => [$countLast]
                ]
            ];
        }

        return array_merge($structure, $nextPage);
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
        if (!array_key_exists('docIdentField', $config)
            || (empty($config['docIdentField']))
            || (!is_string($config['docIdentField']))
        ) {
            throw new ErrorException('Ключ docIdentField не найден в конфигурации или пустое значение или не строковый тип');
        }

        if (!array_key_exists('docIdentVal', $config)) {
            throw new ErrorException('Ключ docIdentVal не найден в конфигурации');
        }

        if (!array_key_exists('routeNext', $config)
            || (empty($config['routeNext']))
            || (!is_string($config['routeNext']))
        ) {
            throw new ErrorException('Ключ routeNext не найден в конфигурации или пустое значение или не строковый тип');
        }

        if (!array_key_exists('routeChild', $config)
            || (empty($config['routeChild']))
            || (!is_string($config['routeChild']))
        ) {
            throw new ErrorException('Ключ routeChild не найден в конфигурации или пустое значение или не строковый тип');
        }

        if (!array_key_exists('page', $config) || (empty($config['page']))) {
            throw new ErrorException('Ключ page не найден в конфигурации или пустое значение');
        }
    }
}
