<?php

use yii\db\Migration;

class m160401_000000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('doc_types', [
            'id' => $this->primaryKey(),
            'tag' => $this->string(128)->notNull()->unique(),
            'name' => $this->string(128)->notNull(),
            'description' => $this->string(512),
            'class' => $this->string(128), // Name of the class handling the document
            'table' => $this->string(128), // Name of the table containging the documents
        ], null);

        $this->createTable('doc_statuses', [
            'id' => $this->primaryKey(),
            'doc_type' => $this->integer()->notNull(),
            'tag' => $this->string(128)->notNull()->unique(),
            'name' => $this->string(128)->notNull()->unique(),
            'description' => $this->string(512),
        ], null);

        $this->addForeignKey('doc_statuses_doc_type_fkey', 'doc_statuses', 'doc_type', 'doc_types', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('doc_statuses_links', [
            'status_from' => $this->integer()->notNull(),
            'status_to' => $this->integer()->notNull(),
            'right_tag' => $this->string(128)->notNull(),
        ], null);

        $this->addForeignKey('doc_statuses_links_statuses_id_fk1', 'doc_statuses_links', 'status_from', 'doc_statuses', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('doc_statuses_links_statuses_id_fk2', 'doc_statuses_links', 'status_to', 'doc_statuses', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('doc_types');
        $this->dropTable('doc_statuses');
        $this->dropTable('doc_statuses_links');
    }
}