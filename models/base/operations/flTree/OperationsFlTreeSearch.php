<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 10.08.16
 * Time: 10:44
 */

namespace docflow\models\base\operations\flTree;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class OperationsFlTreeSearch extends OperationsFlTree
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied.
     *
     * @param array $params - параметры
     *
     * @return ActiveDataProvider
     *
     * @throws \yii\base\InvalidParamException
     */
    public function search($params)
    {
        $query = OperationsFlTree::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query
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

        $dataProvider->query = $query;

        return $dataProvider;
    }
}
