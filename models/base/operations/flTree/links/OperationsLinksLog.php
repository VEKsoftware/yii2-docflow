<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 09.08.16
 * Time: 16:06
 */

namespace docflow\models\base\operations\flTree\links;

use docflow\models\base\operations\Operations;

class OperationsLinksLog extends OperationsLinks
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%operations_links_log}}';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['from', 'to', 'lvl', 'doc_id'], 'integer'],
            [['tp', 'rtp'], 'string'],
            [['atime'], 'safe'],
            [
                ['from', 'to', 'tp', 'rtp', 'lvl'],
                'unique',
                'targetAttribute' => ['from', 'to', 'tp', 'rtp', 'lvl'],
                'message' => 'The combination of From, To, Tp, Rtp and Lvl has already been taken.'
            ],
            [
                ['from'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Operations::className(),
                'targetAttribute' => ['from' => 'id']
            ],
            [
                ['to'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Operations::className(),
                'targetAttribute' => ['to' => 'id']
            ],
            [
                ['doc_id'],
                'exist',
                'targetClass' => OperationsLinks::className(),
                'targetAttribute' => 'id'
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'from' => 'From',
            'to' => 'To',
            'tp' => 'Tp',
            'rtp' => 'Rtp',
            'lvl' => 'Lvl',
            'atime' => 'At Data',
            'doc_id' => 'Документ'
        ];
    }

    public function behaviors()
    {
        return [];
    }
}
