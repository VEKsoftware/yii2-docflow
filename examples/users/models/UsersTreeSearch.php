<?php

namespace docflow\examples\users\models;

use docflow\behaviors\LinkStructuredBehavior;
use docflow\models\Document;
use yii;
use yii\data\ActiveDataProvider;

class UsersTreeSearch extends UsersSearch
{
    /**
     * Имя поведения
     *
     * @var string
     */
    public $behaviorName;

    /**
     * Объект документа
     *
     * @var Document
     */
    public $document;

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params - массив параметров
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        /* @var LinkStructuredBehavior $structureBehavior */
        $structureBehavior = $this->document->getBehavior($this->behaviorName);

        $dataProvider = parent::search($params);
        $dataProvider->query = $structureBehavior->getDocumentsWhichChild1LevelByRootDocument($dataProvider->query);

        return $dataProvider;
    }
}
