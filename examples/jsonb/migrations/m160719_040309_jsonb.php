<?php

use yii\db\Migration;

class m160719_040309_jsonb extends Migration
{
    public function safeUp()
    {
        $this->createTable(
            '{{%jsonbtest}}',
            [
                'id' => $this->primaryKey()->notNull(),
                'contacts' => 'jsonb',
                'payment_cards' => 'jsonb'
            ]
        );

        $this->batchInsert(
            '{{%jsonbtest}}',
            ['contacts', 'payment_cards'],
            [
                [
                    json_encode(['телефон' => 89890343546, 'почта' => 'tutiok@tit.com']),
                    json_encode(['дебетовая карта' => 123, 'кредитная карта' => 456])
                ],
                [
                    json_encode(['телефон' => '89546321346', 'почта' => 'ffdssd@fre.com']),
                    json_encode(['дебетовая карта' => 1231, 'кредитная карта' => 45622])
                ],
                [
                    json_encode([
                        'телефон' => '89546345234',
                        'почта' => 'lopiyt@nmk.com',
                        'соц сети' => [
                            'вконтакте' => ['логин' => 'qwe', 'пароль' => 'ssssss'],
                            'лицокнига' => ['логин' => 'ssss', 'пароль' => 'sdfasdas'],
                            'мой мир' => 'ололо'
                        ]
                    ]),
                    json_encode(['дебетовая карта' => 12355, 'кредитная карта' => 45678900])
                ],
            ]
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%jsonbtest}}');
    }
}
