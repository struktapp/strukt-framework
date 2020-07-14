<?php

namespace Schema\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class VersionAuth extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        if($schema->hasTable("permission"))
            $schema->dropTable("permission");

        $permission = $schema->createTable('permission');
        $permission->addColumn('id', 'integer', array('autoincrement' => true));
        $permission->addColumn('name', 'string');
        $permission->addColumn('descr', 'text', array('notnull' => false));
        $permission->addUniqueIndex(array("name"));
        $permission->setPrimaryKey(array('id'));

        if($schema->hasTable("role"))
            $schema->dropTable("role");

        $role = $schema->createTable('role');
        $role->addColumn('id', 'integer', array('autoincrement' => true));
        $role->addColumn('name', 'string');
        $role->addColumn('descr', 'text', array('notnull' => false));
        $role->addUniqueIndex(array("name"));
        $role->setPrimaryKey(array('id'));

        if($schema->hasTable("role_permission"))
            $schema->dropTable("role_permission");

        $role_permission = $schema->createTable('role_permission');
        $role_permission->addColumn('id', 'integer', array('autoincrement' => true));
        $role_permission->addColumn('role_id', 'integer');
        $role_permission->addColumn('permission_id', 'integer');
        $role_permission->addUniqueIndex(array("role_id", "permission_id"));
        $role_permission->addForeignKeyConstraint($role, array("role_id"), array("id"), array(

            "onUpdate" => "CASCADE"
        ));

        $role_permission->addForeignKeyConstraint($permission, array("permission_id"), array("id"), array(

            "onUpdate" => "CASCADE"
        ));
        
        $role_permission->setPrimaryKey(array('id'));

        if($schema->hasTable("user"))
            $schema->dropTable("user");

        $user = $schema->createTable('user');
        $user->addColumn('id', 'integer', array('autoincrement' => true));
        $user->addColumn('username', 'string');
        $user->addColumn('password', 'string');
        $user->addColumn('role_id', 'integer');
        $user->addUniqueIndex(array("username"));
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
        $schema->dropTable("permission");
        $schema->dropTable("role");
        $schema->dropTable("role_permission");
        $schema->dropTable("user");
    }
}
