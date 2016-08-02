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
class StatusesTreeSearch extends Statuses
{
    /**
     * Правила валидации
     *
     * @inheritdoc
     *
     * @return array
     */
    public function rules()
    {
        return [
            [
                ['id'],
                'integer',
            ],
            [
                [
                    'name',
                    'tag'
                ],
                'string'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

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
    public function search($document, $behaviorName, $extra, $params)
    {
        /* @var LinkStructuredBehavior $structureBehavior */
        $structureBehavior = $document->getBehavior($behaviorName);

        if ($extra !== null) {
            $structureBehavior->extraFilter = (array)json_decode($extra);
        }

        $query = $structureBehavior->getDocumentsWhichChild1LevelByRootDocument();
        $groupBy = $document::tableName() . '.' . $document->linkFieldsArray['node_id'];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 5,
                'totalCount' => $query->groupBy($groupBy)->count()
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_ASC],
                'attributes' => [
                    'id' => [
                        'asc' => ['id' => SORT_ASC],
                        'desc' => ['id' => SORT_DESC],
                        'label' => 'Id',
                        'default' => SORT_ASC
                    ],
                    'name' => [
                        'asc' => ['name' => SORT_ASC],
                        'desc' => ['name' => SORT_DESC],
                        'label' => 'Name',
                        'default' => SORT_ASC
                    ],
                    'tag' => [
                        'asc' => ['tag' => SORT_ASC],
                        'desc' => ['tag' => SORT_DESC],
                        'label' => 'Tag',
                        'default' => SORT_ASC
                    ],
                ]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(
            [
                'and',
                ['like', 'name', $this->name],
                ['like', 'tag', $this->tag],
            ]
        );

        $dataProvider->query = $query;

        return $dataProvider;
    }
}
