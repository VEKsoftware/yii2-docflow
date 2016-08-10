<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 10.08.16
 * Time: 10:38
 */

namespace docflow\models\base\operations\flTree;

use docflow\behaviors\LinkOrderedBehavior;
use docflow\behaviors\LinkStructuredBehavior;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;

class OperationsFlTreeTreeSearch extends OperationsFlTreeSearch
{
    /**
     * Документ
     *
     * @var OperationsFlTree
     */
    public $document;

    /**
     * Имя поведения
     *
     * @var string
     */
    public $behaviorName;

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params - массив параметров
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

        if ($structureBehavior instanceof LinkOrderedBehavior) {
            $dataProvider->query = $dataProvider->query->orderBy($structureBehavior->orderedFieldDb . ' asc');
        }

        return $dataProvider;
    }
}
