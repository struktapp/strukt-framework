<?php

namespace Schema\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170630235421 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        if($schema->hasTable("role"))
            $schema->dropTable("role");

        $role = $schema->createTable('role');
        $role->addColumn('id', 'integer', array('autoincrement' => true));
        $role->addColumn('name', 'string');
        $role->addColumn('descr', 'string');
        $role->setPrimaryKey(array('id'));

        if($schema->hasTable("user"))
            $schema->dropTable("user");

        $user = $schema->createTable('user');
        $user->addColumn('id', 'integer', array('autoincrement' => true));
        $user->addColumn('username', 'string');
        $user->addColumn('password', 'string');
        $user->addColumn('role_id', 'integer');
        $user->setPrimaryKey(array('id'));

        $user->addForeignKeyConstraint($role, array("role_id"), array("id"), array(

            "onUpdate" => "CASCADE"
        ));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable("user");
        $schema->dropTable("role");
    }
}
