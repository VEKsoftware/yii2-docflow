<?php

namespace docflow\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * StatusesLinksSearch represents the model behind the search form about `docflow\models\Statuses`.
 */
class StatusesLinksSearch extends StatusesLinks
{
    public $statusName;
    public $rightName;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status_from', 'status_to'], 'integer'],
            [['statusName', 'rightName', 'right_tag'], 'string'],
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
     * @param $statusesId
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($statusesId, $params)
    {
        $query = StatusesLinks::find()
            ->with('statusFrom')
            ->where(['status_from' => $statusesId]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        /* TODO  здесь нужно создать условия для фильтрации элементов единиц StatusLink по именам связанных статусов и прав */
/*
        $query
            ->joinWith(['baseTo' => function ($query) {
                $query->where([
                    'or',
                    ['like','doc_statuses.name', $this->statusName],
                    ['like','doc_statuses.tag', $this->statusName],
                ]);
            }]);
//            ->joinWith(['right' => function ($query) {
//                $query->where('"ref_rights"."name" LIKE ' . "'%" . $this->rightName . "%'");
//            }]);

*/

        $dataProvider->setSort([
            'attributes' => [
                'statusName' => [
                    'asc' => [
                        'doc_statuses.name' => SORT_ASC,
                    ],
                    'desc' => [
                        'doc_statuses.name' => SORT_DESC,
                    ],
                    'default' => SORT_ASC,
                ],
                'right_tag' => [
                    'asc' => [
                        'right_tag' => SORT_ASC,
                    ],
                    'desc' => [
                        'right_tag' => SORT_DESC,
                    ],
                    'default' => SORT_ASC,
                ],
            ],
        ]);

        return $dataProvider;
    }
}
