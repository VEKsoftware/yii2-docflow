<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 19.07.16
 * Time: 16:03
 */

namespace docflow\base;

use yii;
use yii\base\ErrorException;
use yii\base\InvalidParamException;

class UnstructuredRecord extends MultipleActiveRecord
{
    /**
     * Свойство, содержащее распарсенные данные
     *
     * @var array
     */
    private $_hiddenAttributes = [];

    /**
     * Получаем массив, содержащий наименования полей, которые содержат данные в формате json
     *
     * @return array
     */
    public static function jsonBFields()
    {
        return [];
    }

    /**
     * После того как объект был создан и наполнен данными из запроса,
     * находим поля которые содержат данные в формате jsonb,
     * заспасиваем их, наполняем объект jsonB и записываем в свойство _hiddenAttributes
     *
     * @return void
     *
     * @throws ErrorException
     */
    public function afterFind()
    {
        $jsonBColumns = static::jsonBFields();

        if (!is_array($jsonBColumns)) {
            throw new ErrorException('Не массив');
        }

        foreach ($jsonBColumns as $jsonBColumn) {
            $this->setHiddenAttributesInEvents($jsonBColumn);
        }

        parent::afterFind();
    }

    /**
     * Устанавливаем значения в $_hiddenAttributes
     *
     * @param string $jsonBColumn - имя столбцы в БД, содержазий данные в формате json
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected function setHiddenAttributesInEvents($jsonBColumn)
    {
        $property = $this->{$jsonBColumn};

        if (!empty($property)) {
            if (!is_string($this->{$jsonBColumn})) {
                throw new ErrorException('Не Json строка');
            }

            $fields = $this->jsonBDecode($this->{$jsonBColumn});

            if (is_array($fields)) {
                $this->_hiddenAttributes[$jsonBColumn] = new JsonB($this->preparePopulateJsonB($fields));
            }
        }
    }


    /**
     * Обрабатываем поступающие параметры
     *
     * @param array $fields - массив параметров ключ->значение
     *
     * @return array
     */
    protected function preparePopulateJsonB(array $fields)
    {
        $return = [];

        foreach ($fields as $key => $field) {
            if (is_array($field)) {
                $return[$key] = new JsonB($this->preparePopulateJsonB($field));
            } else {
                $return[$key] = $field;
            }
        }

        return $return;
    }

    /**
     * Подготавливаем скрытые аттрибуты для сохранения
     *
     * @param JsonB $hiddenAttributes - Объект JsonB
     *
     * @return array
     */
    protected function prepareSaveJsonB(JsonB $hiddenAttributes)
    {
        $return = [];

        foreach ($hiddenAttributes->attributes as $key => $hiddenAttribute) {
            if ($hiddenAttribute instanceof JsonB) {
                $return[$key] = call_user_func([$this, 'prepareSaveJsonB'], $hiddenAttribute);
            } else {
                $return[$key] = $hiddenAttribute;
            }
        }

        return $return;
    }


    /**
     * Кодируем объект jsonB в json формат
     *
     * @param JsonB $jsonB - объект jsonB
     *
     * @return string
     */
    protected function jsonBEncode(JsonB $jsonB)
    {
        return json_encode($jsonB);
    }


    /**
     * Декодиреум из формата json
     *
     * @param string $json - строка, содержащяя данные в формате json
     *
     * @return mixed
     */
    protected function jsonBDecode($json)
    {
        return json_decode($json, true);
    }

    /**
     * Перед валидацией, записываем в _attributes новые значения и удаляем из _hiddenAttributes,
     * т.к валидация будет смотреть на _hiddenAttributes и выдавать ошибку,
     * после сохранения если была валидация восстанавливаем _hiddenAttributes
     *
     * @return bool
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $jsonBColumns = static::jsonBFields();

        if (!is_array($jsonBColumns)) {
            throw new ErrorException('Не массив');
        }

        foreach ($jsonBColumns as $jsonBColumn) {
            $json = json_encode($this->prepareSaveJsonB($this->_hiddenAttributes[$jsonBColumn]));
            $this->setAttribute($jsonBColumn, $json);
            unset($this->_hiddenAttributes[$jsonBColumn]);
        }

        return true;
    }

    /**
     * Перед сохранением:
     * 1)Если была валидация(отсутствуют значения в _hiddenAttributes), то ничего не делаем
     * 2)Если валидации не было(присутствуют значения в _hiddenAttributes), то производим обновление _attributes
     *
     * @param bool $insert - true - данные добавляются, false - данные обновляются
     *
     * @return bool
     *
     * @throws ErrorException
     * @throws InvalidParamException
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $jsonBColumns = static::jsonBFields();

        if (!is_array($jsonBColumns)) {
            throw new ErrorException('Не массив');
        }

        foreach ($jsonBColumns as $jsonBColumn) {
            if (array_key_exists($jsonBColumn, $this->_hiddenAttributes)) {
                $json = json_encode($this->prepareSaveJsonB($this->_hiddenAttributes[$jsonBColumn]));
                $this->setAttribute($jsonBColumn, $json);
            }
        }

        return true;
    }

    /**
     * После сохраенения:
     * 1)Если была валидация(отсутствуют значения в _hiddenAttributes), то восстанавливаем
     * 2)Если валидации не было(присутствуют значения в _hiddenAttributes), то ничего не делаем
     *
     * @param bool  $insert            - true - новая запись, false - обновление существующей
     * @param array $changedAttributes - измененные аттрибуты
     *
     * @return void
     *
     * @throws ErrorException
     */
    public function afterSave($insert, $changedAttributes)
    {
        $jsonBColumns = static::jsonBFields();

        if (!is_array($jsonBColumns)) {
            throw new ErrorException('Не массив');
        }

        foreach ($jsonBColumns as $jsonBColumn) {
            if (!array_key_exists($jsonBColumn, $this->_hiddenAttributes)) {
                $this->setHiddenAttributesInEvents($jsonBColumn);
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Магический метод получения значения аттрибута по его имени
     *
     * @param string $name - имя свойства
     *
     * @return mixed
     *
     * @throws InvalidParamException
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_hiddenAttributes) || isset($this->_hiddenAttributes[$name])) {
            return $this->_hiddenAttributes[$name];
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Магический метод установки значения аттрибуту
     *
     * @param string $name  - имя свойства
     * @param mixed  $value - новое значение
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if ($this->hasHiddenAttribute($name)) {
            if (is_array($value)) {
                $this->_hiddenAttributes[$name] = new JsonB($this->preparePopulateJsonB($value));
            }
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Проверяем, присутствует-ли аттрибут в скрытых атрибутах или он должен быть там
     *
     * @param string $name - имя свойства
     *
     * @return bool
     */
    public function hasHiddenAttribute($name)
    {
        return isset($this->_hiddenAttributes[$name]) || in_array($name, static::jsonBFields(), true);
    }

    /**
     * Получаем все скрытые аттрибуты
     *
     * @return array
     */
    public function getHiddenAttributes()
    {
        return $this->_hiddenAttributes;
    }

    /**
     * Массовая установка
     *
     * @param array $array - массив содержащий новые данные
     *
     * @return void
     */
    public function setHiddenAttributes(array $array)
    {
        foreach ($array as $key => $value) {
            if ($this->hasHiddenAttribute($key) && is_array($value)) {
                $this->_hiddenAttributes[$key] = new JsonB($this->preparePopulateJsonB($value));
            }
        }
    }
}
