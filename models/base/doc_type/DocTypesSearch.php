<?php

namespace docflow\models\base\doc_type;

use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;

/**
 * DocTypesSearch represents the model behind the search form about `docflow\models\DocTypes`.
 */
class DocTypesSearch extends DocTypes
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name', 'tag'], 'string'],
        ];
    }

    /**
     * Creates data provider instance with search query applied.
     *
     * @param array $params - параметры
     *
     * @return ActiveDataProvider
     *
     * @throws InvalidParamException
     */
    public function search($params)
    {
        $query = DocTypes::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');

            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'tag', $this->tag]);

        return $dataProvider;
    }
}
