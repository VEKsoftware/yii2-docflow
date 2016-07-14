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
     * Тайтл списка плоского дерева
     *
     * @var string
     */
    public $titleList;

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
        return $this->render($this->renderView, ['dataUrl' => $this->dataUrl, 'titleList' => $this->titleList]);
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
        //TODO разкоментировать и переписать проверки
        //static::checkConfiguration($config);

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
            'href' => static::getLink($config['documentView'], $value),
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
                'href_child' => static::getLink($config['child'], $value),
                'tags' => ['Вложений: ' . $countChild],
            ];
        }

        return $child;
    }

    /**
     * Формируем ссылку на просмотр документа
     *
     * @param array         $data  - содержит маршрут и параметры к маршруту
     * @param Document|null $value - объект документа
     *
     * @return string
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    protected static function getLink(array $data, $value = null)
    {
        $routeArray = [$data['route']];

        foreach ($data['params'] as $param) {
            if (($value === null) && (($param['type'] === 'property') || ($param['type'] === 'function'))) {
                throw new ErrorException('Ошибка формирования URL: Если отсутствует объект, то невозможно использовать типы property и function');
            }

            $paramValue = '';

            if ($param['type'] === 'value') {
                $paramValue = $param['value'];
            }

            if ($param['type'] === 'property') {
                $paramValue = $value->{$param['value']};
            }

            if ($param['type'] === 'function') {
                $paramValue = call_user_func([$value::className(), $param['value']]);
            }

            $routeArray = array_merge($routeArray, [$param['key'] => $paramValue]);
        }

        return Url::to($routeArray);
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
                'href_next' => static::getLink($config['next']),
                'tags' => ['Осталось: '.$countLast]
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
