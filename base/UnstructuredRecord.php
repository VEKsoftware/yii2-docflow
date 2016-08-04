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
use yii\helpers\ArrayHelper;

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
        $this->_hiddenAttributes = [];

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
            if (!is_string($property)) {
                throw new ErrorException('Не Json строка');
            }

            $fields = $this->jsonBDecode($property);

            if (is_array($fields)) {
                $this->_hiddenAttributes[$jsonBColumn] = new JsonB($fields);
            }
        }
    }

    /**
     * Подготавливаем скрытые аттрибуты для сохранения
     *
     * @param JsonB $jsonB - Объект JsonB
     *
     * @return array
     */
    protected function prepareSaveJsonB(JsonB $jsonB)
    {
        $return = [];

        foreach ($jsonB->attributes as $key => $value) {
            if ($value instanceof JsonB) {
                $return[$key] = call_user_func([$this, 'prepareSaveJsonB'], $value);
            } else {
                $return[$key] = $value;
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
     * Перед сохранением:
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
                /* Проверяем на null значение аттрибутов JsonB (когда только создаем и можем не записать ничего) */
                if ($this->_hiddenAttributes[$jsonBColumn]->attributes !== null) {
                    $hiddenAttribute = $this->_hiddenAttributes[$jsonBColumn];
                    $prepareJsonB = $this->prepareSaveJsonB($hiddenAttribute);
                    $json = json_encode($prepareJsonB);

                    $this->setAttribute($jsonBColumn, $json);
                }

                unset($this->_hiddenAttributes[$jsonBColumn]);
            }
        }

        return true;
    }

    /**
     * После сохраенения:
     *
     * @param bool  $insert            - true - новая запись,
     *                                 false - обновление существующей
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
            $this->setHiddenAttributesInEvents($jsonBColumn);
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
        $explode = explode('.', $name);

        if (array_key_exists($explode[0], $this->_hiddenAttributes) || isset($this->_hiddenAttributes[$explode[0]])) {
            if (count($explode) < 2) {
                return $this->_hiddenAttributes[$explode[0]];
            } else {
                return ArrayHelper::getValue($this->_hiddenAttributes, implode('.', $explode));
            }
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
        $explode = explode('.', $name);

        if ($this->hasHiddenAttribute($explode[0])) {
            if (count($explode) < 2) {
                if (is_array($value)) {
                    $this->_hiddenAttributes[$explode[0]] = new JsonB($value);
                }
            } else {
                /* Распаковка */
                $endField = $this;
                $previous = [];
                foreach ($explode as $field) {
                    $endField = $endField->{$field};
                    $previous[] = $endField;
                }

                /* Установление нового значения */
                $lastNum = (count($previous) - 1);
                $previous[$lastNum] = $value;

                /* Упаковка */
                for ($current = (count($previous) - 1); $current >= 1; $current--) {
                    $next = ($current - 1);
                    $previous[$next]->{$explode[$current]} = $previous[$current];
                }

                /* Финальное присваивание */
                $this->{$explode[0]} = $previous[0];
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
    protected function hasHiddenAttribute($name)
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
                $this->_hiddenAttributes[$key] = new JsonB($value);
            }
        }
    }

    /**
     * Инит
     *
     * @return void
     *
     * @throws ErrorException
     */
    public function init()
    {
        $this->initHiddenAttributes();

        parent::init();
    }

    /**
     * Инициализруем скытые аттрибуты
     *
     * @return void
     *
     * @throws ErrorException
     */
    protected function initHiddenAttributes()
    {
        $jsonBColumns = static::jsonBFields();

        if (!is_array($jsonBColumns)) {
            throw new ErrorException('Не массив');
        }

        foreach ($jsonBColumns as $jsonBColumn) {
            $this->_hiddenAttributes[$jsonBColumn] = new JsonB([]);
        }
    }
}
