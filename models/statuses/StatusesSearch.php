<?php

namespace docflow\models\base\statuses;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * StatusesSearch represents the model behind the search form about `statuses\models\Statuses`.
 */
class StatusesSearch extends Statuses
{

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'doc_type_id'], 'integer'],
            [['name', 'description', 'tag'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied.
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Statuses::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'doc_type_id' => $this->doc_type_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
        ->andFilterWhere(['like', 'tag', $this->tag]);

        return $dataProvider;
    }

    /**
     * @param $model
     * @param $params
     * @return ActiveDataProvider
     */
    public function searchUnlink($model, $params)
    {
        $query = Statuses::find()
            ->where([
                'and',
                ['not', ['id' => $model->id]],
                ['doc_type_id' => $model->doc_type_id],
            ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'name', $this->name])
        ->andFilterWhere(['like', 'tag', $this->tag]);

        return $dataProvider;
    }
}
