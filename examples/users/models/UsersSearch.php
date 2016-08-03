<?php

namespace docflow\examples\users\models;

use docflow\behaviors\LinkStructuredBehavior;
use docflow\models\Document;
use yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UnitsSearch represents the model behind the search form about `vekuser\models\units\Units`.
 */
class UsersSearch extends Users
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
                ['idx'],
                'integer',
            ],
            [
                [
                    'short_name',
                    'full_name',
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
     * @param array $params - массив параметров
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Users::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['tag' => SORT_ASC],
                'attributes' => [
                    'idx' => [
                        'asc' => ['idx' => SORT_ASC],
                        'desc' => ['idx' => SORT_DESC],
                        'label' => 'Idx',
                        'default' => SORT_ASC
                    ],
                    'short_name' => [
                        'asc' => ['short_name' => SORT_ASC],
                        'desc' => ['short_name' => SORT_DESC],
                        'label' => 'Short Name',
                        'default' => SORT_ASC
                    ],
                    'full_name' => [
                        'asc' => ['full_name' => SORT_ASC],
                        'desc' => ['full_name' => SORT_DESC],
                        'label' => 'Full Name',
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
                ['like', 'short_name', $this->short_name],
                ['like', 'full_name', $this->full_name],
                ['like', 'tag', $this->tag],
            ]
        );

        $dataProvider->query = $query;

        return $dataProvider;
    }
}
