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

        $this->createTable('doc_types', [
            'id' => $this->primaryKey(),
            'tag' => $this->string(128)->notNull()->unique(),
            'name' => $this->string(128)->notNull(),
            'description' => $this->string(512),
            'status_id' => $this->integer(),
            'class' => $this->string(128), // Name of the class handling the document
            'table' => $this->string(128), // Name of the table containging the documents
        ], null);

        $this->createIndex ( 'doc_types_tags', 'doc_types', 'tag', true );

        $this->insert('doc_types', [
            'id' => 1,
            'tag' => 'status',
            'name' => Yii::t('docflow', 'Status'),
            'description' => Yii::t('docflow', 'Status of the document'),
            'status_id' => 1,
            'class' => Statuses::className(),
            'table' => 'doc_statuses',
        ]);

        $this->createTable('doc_statuses', [
            'id' => $this->primaryKey(),
            'doc_type_id' => $this->integer()->notNull(),
            'tag' => $this->string(128)->notNull(),
            'name' => $this->string(128)->notNull(),
            'description' => $this->string(512),
        ], null);

        $this->insert('doc_statuses', [
            'id' => 1,
            'doc_type_id' => 1,
            'tag' => 'active',
            'name' => Yii::t('docflow', 'Active'),
            'description' => Yii::t('docflow', 'Active document'),
        ]);

        $this->createIndex ( 'doc_statuses_tags_key', 'doc_statuses', ['doc_type_id', 'tag'], true );

        $this->addForeignKey('doc_statuses_doc_type_fkey', 'doc_statuses', 'doc_type_id', 'doc_types', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('doc_types_doc_statuses_fkey', 'doc_types', 'status_id', 'doc_statuses', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('doc_statuses_links', [
            'status_from' => $this->integer()->notNull(),
            'status_to' => $this->integer()->notNull(),
            'right_tag' => $this->string(128)->notNull(),
            'type' => 'link_types DEFAULT \'simple\'::link_types',
            'level' => $this->integer(),
        ], null);

        $this->createIndex ( 'doc_statuses_links_from', 'doc_statuses_links', 'status_from', false );
        $this->createIndex ( 'doc_statuses_links_to',   'doc_statuses_links', 'status_to',   false );
        $this->addForeignKey('doc_statuses_links_statuses_id_fk1', 'doc_statuses_links', 'status_from', 'doc_statuses', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('doc_statuses_links_statuses_id_fk2', 'doc_statuses_links', 'status_to', 'doc_statuses', 'id', 'CASCADE', 'CASCADE');

/*
        // ????????
        $this->createTable('doc_dynafields', [
            'id' => $this->primaryKey(),
            'doc_type_id' => $this->integer()->notNull(),
            'tag' => $this->string(128)->notNull(), // field tag
            'name' => $this->string(128)->notNull(), // Human readable name of the property
            'description' => $this->string(512),
        ], null);

        $this->addForeignKey('doc_dynafields_doc_type_fkey', 'doc_dynafields', 'doc_type_id', 'doc_types', 'id', 'CASCADE', 'CASCADE');

        // ????????
        $this->createTable('doc_structure', [
            'id' => $this->primaryKey(),
            'doc_type_id' => $this->integer()->notNull(),
            'tag' => $this->string(128)->notNull(), // category tag
            'name' => $this->string(128)->notNull(), // Human readable name of the category
            'description' => $this->string(512),
        ], null);

        $this->addForeignKey('doc_dynafields_doc_type_fkey', 'doc_dynafields', 'doc_type_id', 'doc_types', 'id', 'CASCADE', 'CASCADE');

        // ????????
        $this->createTable('doc_structure_links', [
            'id' => $this->primaryKey(),
            'doc_type_id' => $this->integer()->notNull(),
            'tag' => $this->string(128)->notNull(), // category tag
            'name' => $this->string(128)->notNull(), // Human readable name of the category
            'description' => $this->string(512),
        ], null);

        $this->addForeignKey('doc_dynafields_doc_type_fkey', 'doc_dynafields', 'doc_type_id', 'doc_types', 'id', 'CASCADE', 'CASCADE');
*/
    }

    public function safeDown()
    {
        $this->dropForeignKey('doc_statuses_doc_type_fkey', 'doc_statuses');
        $this->dropForeignKey('doc_types_doc_statuses_fkey', 'doc_types');
        $this->dropTable('doc_statuses_links');
        $this->dropTable('doc_statuses');
        $this->dropTable('doc_types');
    }
}