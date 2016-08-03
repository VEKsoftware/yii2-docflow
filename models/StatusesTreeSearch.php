<?php

namespace docflow\models;

use docflow\behaviors\LinkStructuredBehavior;
use yii;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UnitsSearch represents the model behind the search form about `vekuser\models\units\Units`.
 */
class StatusesTreeSearch extends StatusesSearch
{
    /**
     * Документ
     *
     * @var Document
     */
    public $document;

    /**
     * Имя поведения
     *
     * @var string
     */
    public $behaviorName;

    /**
     * Дополнительные условия для фильтрации
     *
     * @var array
     */
    public $extraFilter = [];

    /**
     * Creates data provider instance with search query applied
     *
     * @param Document $document     - объект документа
     * @param string   $behaviorName - имя поведения
     * @param string   $extra        - дополнительные параметры фильтрации
     * @param array    $params       - массив параметров
     *
     * @return ActiveDataProvider
     *
     * @throws InvalidParamException
     */
    public function search($params)
    {
        /* @var LinkStructuredBehavior $structureBehavior */
        $structureBehavior = $this->document->getBehavior($this->behaviorName);
        $structureBehavior->extraFilter = $this->extraFilter;

        $dataProvider = parent::search($params);
        $dataProvider->query = $structureBehavior->getDocumentsWhichChild1LevelByRootDocument($dataProvider->query);

        return $dataProvider;
    }
}
