<?php

use yii\db\Migration;
use docflow\Docflow;

use docflow\models\DocTypes;
use docflow\models\Statuses;

class m160401_000000_init extends Migration
{
    /*
        public function init()
        {
            $db = Docflow::getInstance()->db;
            $this->db = $db;
            parent::init();
        }
    //*/

    public function getDb()
    {
        $db = Docflow::getInstance()->db;

        return Yii::$app->$db;
    }

    public function safeUp()
    {
        $this->getDb()->createCommand('DROP TYPE IF EXISTS link_types')->execute();
        $this->getDb()->createCommand('CREATE TYPE link_types as ENUM (\'simple\', \'fltree\')')->execute();

        $this->createTable(
            'doc_types',
            [
                'id' => $this->primaryKey(),
                'tag' => $this->string(128)->notNull()->unique(),
                'name' => $this->string(128)->notNull(),
                'description' => $this->string(512),
                //'status_id' => $this->integer(),
                'class' => $this->string(128), // Name of the class handling the document
                'table' => $this->string(128), // Name of the table containging the documents
            ],
            null
        );

        $this->createIndex('doc_types_tags', 'doc_types', 'tag', true);

        $this->batchInsert(
            'doc_types',
            ['tag', 'name', 'description', 'class', 'table'],
            [
                [
                    'status',
                    Yii::t('docflow', 'Status'),
                    Yii::t('docflow', 'Status of the document'),
                    Statuses::className(),
                    'doc_statuses'
                ],
                ['vid', 'Very Important Document', '', '', '']
            ]
        );

        $this->createTable(
            'doc_statuses',
            [
                'id' => $this->primaryKey(),
                'doc_type_id' => $this->integer()->notNull(),
                'tag' => $this->string(128)->notNull(),
                'name' => $this->string(128)->notNull(),
                'description' => $this->string(512),
                'order_idx' => 'serial NOT NULL',
            ],
            null
        );


        $this->batchInsert(
            'doc_statuses',
            ['doc_type_id', 'tag', 'name', 'description'],
            [
                [
                    1,
                    'active',
                    Yii::t('docflow', 'Active'),
                    Yii::t('docflow', 'Active document')
                ],
                [2, 1, 1, 1],
                [2, 2, 2, 2],
                [2, 3, 3, 3],
                [2, 4, 4, 4],
                [2, 5, 5, 5],
                [2, 6, 6, 6],
                [2, 7, 7, 7],
                [2, 8, 8, 8],
                [2, 9, 9, 9],
                [2, 10, 10, 10],
            ]
        );

        $this->createIndex(
            'doc_statuses_tags_key',
            'doc_statuses',
            ['doc_type_id', 'tag'],
            true
        );

        //$this->addForeignKey('doc_statuses_doc_type_fkey', 'doc_statuses', 'doc_type_id', 'doc_types', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(
            'doc_types_doc_statuses_fkey',
            'doc_statuses',
            'doc_type_id',
            'doc_types',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable(
            'doc_statuses_links',
            [
                'id' => $this->primaryKey(),
                'status_from' => $this->integer()->notNull(),
                'status_to' => $this->integer()->notNull(),
                'right_tag' => $this->string(128),
                'type' => 'link_types DEFAULT \'simple\'::link_types',
                'level' => $this->integer(),
            ],
            null);

        $this->createIndex(
            'doc_statuses_links_from',
            'doc_statuses_links',
            'status_from',
            false
        );
        $this->createIndex(
            'doc_statuses_links_to',
            'doc_statuses_links',
            'status_to',
            false
        );
        $this->addForeignKey(
            'doc_statuses_links_statuses_id_fk1',
            'doc_statuses_links',
            'status_from',
            'doc_statuses',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'doc_statuses_links_statuses_id_fk2',
            'doc_statuses_links',
            'status_to',
            'doc_statuses',
            'id',
            'CASCADE',
            'CASCADE'
        );

        /* Определяем тип данных для operation_types */
        $this->getDb()
            ->createCommand('DROP TYPE IF EXISTS "operation_types"')
            ->execute();

        $this->getDb()
            ->createCommand('CREATE TYPE operation_types as ENUM (\'Nope\')')
            ->execute();

        /* Таблица операций */
        $this->createTable(
            '{{%operations}}',
            [
                'id' => $this->primaryKey()->notNull(),
                'operation_type' => 'operation_types DEFAULT \'Nope\'::operation_types',
                'status_id' => $this->integer()->notNull(),
                /* Связь с контр агнетами */
                'unit_real_id' => $this->integer(),
                'unit_resp_id' => $this->integer(),
                'field' => 'jsonb',
                'comment' => $this->text(),
                'version' => $this->bigInteger(),
                'atime' => $this->timestamp()->notNull() . ' default current_timestamp'
            ]
        );

        $this->createIndex(
            'ix_operations_operation_type',
            '{{%operations}}',
            'operation_type'
        );

        $this->createIndex(
            'ix_operations_status_id',
            '{{%operations}}',
            'status_id'
        );

        $this->createIndex(
            'ix_operations_unit_real_id',
            '{{%operations}}',
            'unit_real_id'
        );

        $this->createIndex(
            'ix_operations_unit_resp_id',
            '{{%operations}}',
            'unit_resp_id'
        );

        $this->createIndex(
            'ix_operations_version',
            '{{%operations}}',
            'version'
        );

        $this->createIndex(
            'ix_operations_atime',
            '{{%operations}}',
            'atime'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('doc_types_doc_statuses_fkey', 'doc_statuses');
        $this->dropForeignKey('doc_statuses_links_statuses_id_fk1', 'doc_statuses_links');
        $this->dropForeignKey('doc_statuses_links_statuses_id_fk2', 'doc_statuses_links');
        $this->dropTable('doc_statuses_links');
        $this->dropTable('doc_statuses');
        $this->dropTable('doc_types');
        $this->dropTable('{{%operations}}');
    }
}