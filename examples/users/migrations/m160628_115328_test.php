<?php

use docflow\Docflow;
use yii\db\Migration;

class m160628_115328_test extends Migration
{
    /**
     * Получаем используемое в модуле подключение к БД
     *
     * @return mixed
     */
    public function getDb()
    {
        return Yii::$app->{Docflow::getInstance()->db};
    }

    public function safeUp()
    {
        /* Создаем свои типы */
        $this->getDb()->createCommand('DROP TYPE IF EXISTS relation_types')->execute();
        $this->getDb()->createCommand('CREATE TYPE relation_types as ENUM (\'Subordination\', \'PartnerProgram\', \'FirmTree\', \'Representatives\', \'Departments\')')->execute();

        $this->getDb()
            ->createCommand('CREATE SEQUENCE "mixed"."test_users_order_idx_main_seq" INCREMENT 1 MINVALUE 1 START 1 CACHE 1;')
            ->execute();

        $this->getDb()
            ->createCommand('CREATE SEQUENCE "mixed"."test_users_order_idx_firmTree_seq" INCREMENT 1 MINVALUE 1 START 1 CACHE 1;')
            ->execute();

        /* Создаем таблицы с индексами */
        $this->createTable(
            'test_users',
            [
                /* TODO поменять потом на нормальное название */
                'idx' => $this->primaryKey()->notNull(),
                'short_name' => $this->string(128)->notNull(),
                'full_name' => $this->string(255)->notNull(),
                'tag' => $this->string(128)->notNull(),
                'status_id' => $this->integer()->notNull(),
                'ref_link' => $this->string(128)->notNull(),
                'created' => $this->integer()->notNull(),
                'user_type_id' => $this->integer()->notNull(),
                'order_idx' => 'jsonb NOT NULL DEFAULT jsonb_object(ARRAY[\'main\'::text, \'firmTree\'::text], ARRAY[(nextval(\'"mixed"."test_users_order_idx_main_seq"\'::regclass))::text, (nextval(\'"mixed"."test_users_order_idx_firmTree_seq"\'::regclass))::text])',
            ],
            null
        );

        $this->getDb()
            ->createCommand('ALTER SEQUENCE "mixed"."test_users_order_idx_main_seq" OWNED BY "mixed"."test_users".order_idx;')
            ->execute();

        $this->getDb()
            ->createCommand('ALTER SEQUENCE "mixed"."test_users_order_idx_firmTree_seq" OWNED BY "mixed"."test_users".order_idx;')
            ->execute();

        $this->createIndex('ux_test_users__ref_link', 'test_users', 'ref_link', true);
        $this->createIndex('ux_test_users__tag', 'test_users', 'tag', true);
        $this->createIndex('ux_test_users__short_name', 'test_users', 'short_name', true);
        $this->createIndex('ux_test_users__full_name', 'test_users', 'full_name', true);
        $this->createIndex('ix_test_users__created', 'test_users', 'created');
        $this->createIndex('ix_test_users__status_id', 'test_users', 'status_id');

        $this->createTable('test_links', [
            'id' => $this->primaryKey()->notNull(),
            /* TODO поменять потом на нормальное название */
            'from' => $this->integer()->notNull(),
            /* TODO поменять потом на нормальное название */
            'to' => $this->integer()->notNull(),
            /* TODO поменять потом на нормальное название */
            'r_tag' => $this->string(128),
            /* TODO поменять потом на нормальное название */
            'tp' => 'link_types DEFAULT \'simple\'::link_types',
            /* TODO поменять потом на нормальное название */
            'rtp' => 'relation_types',
            /* TODO поменять потом на нормальное название */
            'lvl' => $this->integer(),
        ], null);

        $this->createIndex(
            'ux_test_links__from__to__rtp__tp__lvl',
            'test_links',
            ['from', 'to', 'rtp', 'tp', 'lvl'],
            true
        );
        $this->createIndex('ix_test_links__status_from', 'test_links', 'from');
        $this->createIndex('ix_test_links__status_to', 'test_links', 'to');
        $this->createIndex('ix_test_links__right_tag', 'test_links', 'r_tag');
        $this->createIndex('ix_test_links__type', 'test_links', 'tp');
        $this->createIndex('ix_test_links__relation_type', 'test_links', 'rtp');
        $this->createIndex('ix_test_links__level', 'test_links', 'lvl');

        /* Описываем внешние ключи */
        $this->addForeignKey(
            'fk_test_users__status_id____doc_statuses__id',
            'test_users',
            'status_id',
            'doc_statuses',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_test_links__from____doc_statuses__id',
            'test_links',
            'from',
            'test_users',
            'idx',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_test_links__to____doc_statuses__id',
            'test_links',
            'to',
            'test_users',
            'idx',
            'CASCADE',
            'CASCADE'
        );

        /* Добавляем пару фэйковых записей для вида */
        $this->batchInsert('test_users',
            [
                'short_name',
                'full_name',
                'tag',
                'status_id',
                'ref_link',
                'created',
                'user_type_id'
            ],
            [
                ['a', 'aa', 'a', 5, 'cmvkdosldikfmke', time(), 1],
                ['b', 'bb', 'b', 5, 'dfsfewrfdsfsdfd', time(), 1],
                ['c', 'cc', 'c', 5, 'cxvdfdfdseerars', time(), 1],
                ['d', 'dd', 'd', 5, 'xcz drwqesad sd', time(), 1],
                ['e', 'ee', 'e', 5, 'xzswqwqr trer e', time(), 1],
                ['f', 'ff', 'f', 5, 'cddereewesdsazf', time(), 2],
                ['g', 'gg', 'g', 5, '3wqwgre4gds343q', time(), 2],
                ['h', 'hh', 'h', 5, 'xcv gre5w dfs3z', time(), 2],
                ['j', 'jj', 'j', 5, 'xcv gre5wfdsda2', time(), 2],
                ['k', 'kk', 'k', 5, 'xcfdsdfqwe4e21w', time(), 2],
                ['l', 'll', 'l', 5, 'fferdfsdfsdfsfd', time(), 3],
                ['m', 'mm', 'm', 5, 'dfsfewsdfdasdfd', time(), 3],
                ['n', 'nn', 'n', 5, 'cxxcvdrdseerars', time(), 3],
                ['o', 'oo', 'o', 5, 'xcz drwq64566sd', time(), 3],
                ['p', 'pp', 'p', 5, 'xzsewlkwwtrer e', time(), 3],
            ]
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_test_users__status_id____doc_statuses__id', 'test_users');
        $this->dropForeignKey('fk_test_links__from____doc_statuses__id', 'test_links');
        $this->dropForeignKey('fk_test_links__to____doc_statuses__id', 'test_links');

        $this->dropTable('test_users');
        $this->dropTable('test_links');
    }
}
