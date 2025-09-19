<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users', ['id' => 'id']);
        $table->addColumn('username', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('role', 'string', ['limit' => 50, 'default' => 'user'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}
