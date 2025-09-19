<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRatingsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('ratings', ['id' => 'id'])
            ->addColumn('book_id', 'integer')
            ->addColumn('user_id', 'integer')
            ->addColumn('rating', 'integer', ['limit' => 1])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['book_id'])
            ->create();
    }
}
