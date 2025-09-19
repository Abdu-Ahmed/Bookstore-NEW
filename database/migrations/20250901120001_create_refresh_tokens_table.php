<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRefreshTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('refresh_tokens', ['id' => 'id']);
        $table->addColumn('user_id', 'integer')
            ->addColumn('token_hash', 'string', ['limit' => 128])
            ->addColumn('expires_at', 'datetime')
            ->addColumn('revoked', 'boolean', ['default' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['token_hash'], ['unique' => false])
            ->addIndex(['user_id'])
            ->create();
    }
}
