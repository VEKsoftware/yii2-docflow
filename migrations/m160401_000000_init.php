<?php

use yii\db\Migration;
use docflow\Docflow;

use docflow\models\base\statuses\Statuses;

class m160401_000000_init extends Migration
{
    /**
     * Получаем соединение с БД, используемое модулем.
     *
     * @return mixed
     */
    public function getDb()
    {
        return Yii::$app->{Docflow::getInstance()->db};
    }

    /**
     * Безопасно накатываем миграции
     *
     * @return void
     *
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $this->getDb()->createCommand('DROP TYPE IF EXISTS link_types')->execute();
        $this->getDb()->createCommand('CREATE TYPE link_types as ENUM (\'simple\', \'fltree\')')->execute();

        $this->createTable(
            '{{%doc_types}}',
            [
                'id' => $this->primaryKey(),
                'tag' => $this->string(128)->notNull()->unique(),
                'name' => $this->string(128)->notNull(),
                'description' => $this->string(512),
                'class' => $this->string(128), // Name of the class handling the document
                'table' => $this->string(128), // Name of the table containging the documents
            ]
        );

        $this->createIndex('ux_doc_types__tags', '{{%doc_types}}', 'tag', true);
        $this->createIndex('ix_doc_types__name', '{{%doc_types}}', 'name');
        $this->createIndex('ix_doc_types__description', '{{%doc_types}}', 'description');

        $this->batchInsert(
            '{{%doc_types}}',
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
            '{{%doc_statuses}}',
            [
                'id' => $this->primaryKey(),
                'doc_type_id' => $this->integer()->notNull(),
                'tag' => $this->string(128)->notNull(),
                'name' => $this->string(128)->notNull(),
                'description' => $this->string(512),
                'order_idx' => 'serial NOT NULL',
                'operations_ids' => $this->integer() . '[]',
                'version' => $this->bigInteger(),
                'atime' => $this->timestamp()->notNull() . ' default current_timestamp'
            ]
        );


        $this->batchInsert(
            '{{%doc_statuses}}',
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
            'ix_doc_statuses__doc_type_id',
            '{{%doc_statuses}}',
            'doc_type_id'
        );
        $this->createIndex(
            'ux_doc_statuses__tag',
            '{{%doc_statuses}}',
            'tag',
            true
        );
        $this->createIndex(
            'ix_doc_statuses__name',
            '{{%doc_statuses}}',
            'name'
        );
        $this->createIndex(
            'ix_doc_statuses__description',
            '{{%doc_statuses}}',
            'description'
        );
        $this->createIndex(
            'ix_doc_statuses__order_idx',
            '{{%doc_statuses}}',
            'order_idx'
        );
        $this->createIndex(
            'ix_doc_statuses__version',
            '{{%doc_statuses}}',
            'version'
        );
        $this->createIndex(
            'ix_doc_statuses__atime',
            '{{%doc_statuses}}',
            'atime'
        );

        /* Индекс для полнотекстового поиска */
        $this->getDb()
            ->createCommand('CREATE INDEX "ix_doc_statuses__operations_ids" ON "doc_statuses" USING gin ("operations_ids");')
            ->execute();

        $this->addForeignKey(
            'fk_doc_statuses__doc_type_id-doc_types__id',
            '{{%doc_statuses}}',
            'doc_type_id',
            '{{%doc_types}}',
            'id',
            'CASCADE',
            'CASCADE'
        );


        $this->createTable(
            '{{%doc_statuses_links}}',
            [
                'id' => $this->primaryKey(),
                'status_from' => $this->integer()->notNull(),
                'status_to' => $this->integer()->notNull(),
                'right_tag' => $this->string(128),
                'type' => 'link_types DEFAULT \'simple\'::link_types',
                'level' => $this->integer(),
                'version' => $this->bigInteger(),
                'atime' => $this->timestamp() . ' default current_timestamp'
            ]
        );

        $this->createIndex(
            'ix_doc_statuses_links__from',
            '{{%doc_statuses_links}}',
            'status_from'
        );
        $this->createIndex(
            'ix_doc_statuses_links__to',
            '{{%doc_statuses_links}}',
            'status_to'
        );
        $this->createIndex(
            'ix_doc_statuses_links__version',
            '{{%doc_statuses_links}}',
            'version'
        );
        $this->createIndex(
            'ix_doc_statuses_links__atime',
            '{{%doc_statuses_links}}',
            'atime'
        );
        $this->createIndex(
            'ix_doc_statuses_links__right_tag',
            '{{%doc_statuses_links}}',
            'right_tag'
        );
        $this->createIndex(
            'ix_doc_statuses_links__type',
            '{{%doc_statuses_links}}',
            'type'
        );
        $this->createIndex(
            'ix_doc_statuses_links__level',
            '{{%doc_statuses_links}}',
            'level'
        );
        $this->addForeignKey(
            'fk_doc_statuses_links__status_from-doc_statuses__id',
            '{{%doc_statuses_links}}',
            'status_from',
            '{{%doc_statuses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_doc_statuses_links__status_to-doc_statuses__id',
            '{{%doc_statuses_links}}',
            'status_to',
            '{{%doc_statuses}}',
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
                'unit_real_id' => $this->integer()->notNull(),
                'unit_resp_id' => $this->integer()->notNull(),
                'field' => 'jsonb',
                'comment' => $this->text(),
                'version' => $this->bigInteger(),
                'atime' => $this->timestamp()->notNull() . ' default current_timestamp'
            ]
        );

        $this->createIndex(
            'ix_operations__operation_type',
            '{{%operations}}',
            'operation_type'
        );
        $this->createIndex(
            'ix_operations__status_id',
            '{{%operations}}',
            'status_id'
        );
        $this->createIndex(
            'ix_operations__unit_real_id',
            '{{%operations}}',
            'unit_real_id'
        );
        $this->createIndex(
            'ix_operations__unit_resp_id',
            '{{%operations}}',
            'unit_resp_id'
        );
        $this->createIndex(
            'ix_operations__comment',
            '{{%operations}}',
            'comment'
        );
        $this->createIndex(
            'ix_operations__version',
            '{{%operations}}',
            'version'
        );
        $this->createIndex(
            'ix_operations__atime',
            '{{%operations}}',
            'atime'
        );

        $this->addForeignKey(
            'fk_operations__status_id-doc_statuses__id',
            '{{%operations}}',
            'status_id',
            '{{%doc_statuses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );


        $this->getDb()
            ->createCommand('DROP TYPE IF EXISTS operation_relation_types')
            ->execute();

        $this->getDb()
            ->createCommand('CREATE TYPE operation_relation_types as ENUM (\'Nope\')')
            ->execute();

        $this->createTable(
            '{{%operations_links}}',
            [
                'id' => $this->primaryKey()->notNull(),
                'from' => $this->integer()->notNull(),
                'to' => $this->integer()->notNull(),
                'tp' => 'link_types DEFAULT \'simple\'::link_types',
                'rtp' => 'operation_relation_types',
                'lvl' => $this->integer(),
                'version' => $this->bigInteger(),
                'atime' => $this->timestamp()->notNull() . ' default current_timestamp'
            ]
        );

        $this->createIndex(
            'ix_operations_links__version',
            '{{%operations_links}}',
            'version'
        );
        $this->createIndex(
            'ix_operations_links__atime',
            '{{%operations_links}}',
            'atime'
        );
        $this->createIndex(
            'ux_operations_links__from__to__rtp',
            '{{%operations_links}}',
            ['from', 'to', 'rtp'],
            true
        );
        $this->createIndex(
            'ix_operations_links__from',
            '{{%operations_links}}',
            'from'
        );
        $this->createIndex(
            'ix_operations_links__to',
            '{{%operations_links}}',
            'to'
        );
        $this->createIndex(
            'ix_operations_links__type',
            '{{%operations_links}}',
            'tp'
        );
        $this->createIndex(
            'ix_operations_links__relation_type',
            '{{%operations_links}}',
            'rtp'
        );
        $this->createIndex(
            'ix_operations_links__level',
            '{{%operations_links}}',
            'lvl'
        );

        $this->addForeignKey(
            'fk_operations_links__from-operations__id',
            '{{%operations_links}}',
            'from',
            '{{%operations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_operations_links__to-operations__id',
            '{{%operations_links}}',
            'to',
            '{{%operations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );


        /* Таблица операций */
        $this->createTable(
            '{{%operations_log}}',
            [
                'id' => $this->bigPrimaryKey()->notNull(),
                'operation_type' => 'operation_types DEFAULT \'Nope\'::operation_types',
                'status_id' => $this->integer()->notNull(),
                /* Связь с контр агнетами */
                'unit_real_id' => $this->integer()->notNull(),
                'unit_resp_id' => $this->integer()->notNull(),
                'field' => 'jsonb',
                'comment' => $this->text(),
                'changed_attributes' => $this->string(255) . '[]',
                'doc_id' => $this->integer()->notNull(),
                'atime' => $this->timestamp()->notNull() . ' default current_timestamp'
            ]
        );

        $this->createIndex(
            'ix_operations_log__doc_id',
            '{{%operations_log}}',
            'doc_id'
        );
        $this->createIndex(
            'ix_operations_log__operation_type',
            '{{%operations_log}}',
            'operation_type'
        );
        $this->createIndex(
            'ix_operations_log__status_id',
            '{{%operations_log}}',
            'status_id'
        );
        $this->createIndex(
            'ix_operations_log__unit_real_id',
            '{{%operations_log}}',
            'unit_real_id'
        );
        $this->createIndex(
            'ix_operations_log__unit_resp_id',
            '{{%operations_log}}',
            'unit_resp_id'
        );
        $this->createIndex(
            'ix_operations_log__comment',
            '{{%operations_log}}',
            'comment'
        );
        $this->createIndex(
            'ix_operations_log__atime',
            '{{%operations_log}}',
            'atime'
        );
        /* Индекс для полнотекстового поиска */
        $this->getDb()
            ->createCommand('CREATE INDEX "ix_operations_log__changed_attributes" ON "operations_log" USING gin ("changed_attributes");')
            ->execute();

        $this->addForeignKey(
            'fk_operations_log__doc_id-operations__id',
            '{{%operations_log}}',
            'doc_id',
            '{{%operations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_operations_log__status_id-doc_statuses__id',
            '{{%operations_log}}',
            'status_id',
            '{{%doc_statuses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );


        $this->createTable(
            '{{%doc_statuses_log}}',
            [
                'id' => $this->bigPrimaryKey(),
                'doc_type_id' => $this->integer()->notNull(),
                'tag' => $this->string(128)->notNull(),
                'name' => $this->string(128)->notNull(),
                'description' => $this->string(512),
                'order_idx' => $this->integer(),
                'operations_ids' => $this->integer() . '[]',
                'changed_attributes' => $this->string(255) . '[]',
                'doc_id' => $this->integer()->notNull(),
                'operation_log_id' => $this->bigInteger(),
                'atime' => $this->timestamp()->notNull() . ' default current_timestamp'
            ]
        );


        $this->createIndex(
            'ix_doc_statuses_log__doc_type_id',
            '{{%doc_statuses_log}}',
            'doc_type_id'
        );
        $this->createIndex(
            'ix_doc_statuses_log__doc_id',
            '{{%doc_statuses_log}}',
            'doc_id'
        );
        $this->createIndex(
            'ux_doc_statuses_log__tag',
            '{{%doc_statuses_log}}',
            'tag',
            true
        );
        $this->createIndex(
            'ix_doc_statuses_log__name',
            '{{%doc_statuses_log}}',
            'name'
        );
        $this->createIndex(
            'ix_doc_statuses_log__description',
            '{{%doc_statuses_log}}',
            'description'
        );
        $this->createIndex(
            'ix_doc_statuses_log__order_idx',
            '{{%doc_statuses_log}}',
            'order_idx'
        );
        $this->createIndex(
            'ix_doc_statuses_log__atime',
            '{{%doc_statuses_log}}',
            'atime'
        );

        /* Индекс для полнотекстового поиска */
        $this->getDb()
            ->createCommand('CREATE INDEX "ix_doc_statuses_log__operations_ids" ON "doc_statuses_log" USING gin ("operations_ids");')
            ->execute();

        /* Индекс для полнотекстового поиска */
        $this->getDb()
            ->createCommand('CREATE INDEX "ix_doc_statuses_log__changed_attributes" ON "doc_statuses_log" USING gin ("changed_attributes");')
            ->execute();

        $this->addForeignKey(
            'fk_doc_statuses_log__doc_type_id-doc_types__id',
            '{{%doc_statuses_log}}',
            'doc_type_id',
            '{{%doc_types}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_doc_statuses_log__doc_id-doc_statuses__id',
            '{{%doc_statuses_log}}',
            'doc_id',
            '{{%doc_statuses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_doc_statuses_log__operation_log_id-operations__id',
            '{{%doc_statuses_log}}',
            'operation_log_id',
            '{{%operations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );


        $this->createTable(
            '{{%doc_statuses_links_log}}',
            [
                'id' => $this->bigPrimaryKey(),
                'status_from' => $this->integer()->notNull(),
                'status_to' => $this->integer()->notNull(),
                'right_tag' => $this->string(128),
                'type' => 'link_types DEFAULT \'simple\'::link_types',
                'level' => $this->integer(),
                'changed_attributes' => $this->string(255) . '[]',
                'doc_id' => $this->integer()->notNull(),
                'operation_log_id' => $this->bigInteger(),
                'atime' => $this->timestamp() . ' default current_timestamp'
            ]
        );

        $this->createIndex(
            'ix_doc_statuses_links_log__doc_id',
            '{{%doc_statuses_links_log}}',
            'doc_id'
        );
        $this->createIndex(
            'ix_doc_statuses_links_log__from',
            '{{%doc_statuses_links_log}}',
            'status_from'
        );
        $this->createIndex(
            'ix_doc_statuses_links_log__to',
            '{{%doc_statuses_links_log}}',
            'status_to'
        );
        $this->createIndex(
            'ix_doc_statuses_links_log__atime',
            '{{%doc_statuses_links_log}}',
            'atime'
        );
        $this->createIndex(
            'ix_doc_statuses_links_log__right_tag',
            '{{%doc_statuses_links_log}}',
            'right_tag'
        );
        $this->createIndex(
            'ix_doc_statuses_links_log__type',
            '{{%doc_statuses_links_log}}',
            'type'
        );
        $this->createIndex(
            'ix_doc_statuses_links_log__level',
            '{{%doc_statuses_links_log}}',
            'level'
        );

        /* Индекс для полнотекстового поиска */
        $this->getDb()
            ->createCommand('CREATE INDEX "ix_doc_statuses_links_log__changed_attributes" ON "doc_statuses_links_log" USING gin ("changed_attributes");')
            ->execute();

        $this->addForeignKey(
            'fk_doc_statuses_links_log__status_from-doc_statuses__id',
            '{{%doc_statuses_links_log}}',
            'status_from',
            '{{%doc_statuses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_doc_statuses_links_log__status_to-doc_statuses__id',
            '{{%doc_statuses_links_log}}',
            'status_to',
            '{{%doc_statuses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_doc_statuses_links_log__doc_id-doc_statuses-links__id',
            '{{%doc_statuses_links_log}}',
            'doc_id',
            '{{%doc_statuses_links}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_doc_statuses_links_log__operation_log_id-operations__id',
            '{{%doc_statuses_links_log}}',
            'operation_log_id',
            '{{%operations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );


        $this->createTable(
            '{{%operations_links_log}}',
            [
                'id' => $this->bigPrimaryKey()->notNull(),
                'from' => $this->integer()->notNull(),
                'to' => $this->integer()->notNull(),
                'tp' => 'link_types DEFAULT \'simple\'::link_types',
                'rtp' => 'operation_relation_types',
                'lvl' => $this->integer(),
                'changed_attributes' => $this->string(255) . '[]',
                'doc_id' => $this->integer()->notNull(),
                'atime' => $this->timestamp() . ' default current_timestamp'
            ]
        );

        $this->createIndex(
            'ix_operations_links_log__doc_id',
            '{{%operations_links_log}}',
            'doc_id'
        );
        $this->createIndex(
            'ix_operations_links_log__from',
            '{{%operations_links_log}}',
            'from'
        );
        $this->createIndex(
            'ix_operations_links_log__to',
            '{{%operations_links_log}}',
            'to'
        );
        $this->createIndex(
            'ix_operations_links_log__atime',
            '{{%operations_links_log}}',
            'atime'
        );
        $this->createIndex(
            'ix_operations_links_log__relation_type',
            '{{%operations_links_log}}',
            'rtp'
        );
        $this->createIndex(
            'ix_operations_links_log__type',
            '{{%operations_links_log}}',
            'tp'
        );
        $this->createIndex(
            'ix_operations_links_log__level',
            '{{%operations_links_log}}',
            'lvl'
        );

        /* Индекс для полнотекстового поиска */
        $this->getDb()
            ->createCommand('CREATE INDEX "ix_operations_links_log__changed_attributes" ON "operations_links_log" USING gin ("changed_attributes");')
            ->execute();

        $this->addForeignKey(
            'fk_operations_links_log__from-doc_operations__id',
            '{{%operations_links_log}}',
            'from',
            '{{%operations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_operations_links_log__to-operations__id',
            '{{%operations_links_log}}',
            'to',
            '{{%operations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_operations_links_log__doc_id-operations-links__id',
            '{{%operations_links_log}}',
            'doc_id',
            '{{%operations_links}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Безопасно откатываем миграции
     *
     * @return void
     */
    public function safeDown()
    {
        $this->dropTable('{{%operations_links_log}}');
        $this->dropTable('{{%operations_log}}');
        $this->dropTable('{{%doc_statuses_links_log}}');
        $this->dropTable('{{%doc_statuses_log}}');
        $this->dropTable('{{%operations_links}}');
        $this->dropTable('{{%operations}}');
        $this->dropTable('{{%doc_statuses_links}}');
        $this->dropTable('{{%doc_statuses}}');
        $this->dropTable('{{%doc_types}}');
    }
}
