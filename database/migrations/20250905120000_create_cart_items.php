<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCartItems extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('cart_items', ['id' => 'cart_item_id', 'signed' => false]);
        $table
            ->addColumn('user_id', 'integer', ['limit' => 11, 'null' => false])
            ->addColumn('book_id', 'integer', ['limit' => 11, 'null' => false])
            ->addColumn('quantity', 'integer', ['limit' => 11, 'default' => 1, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id'])
            ->addIndex(['book_id'])
            ->addIndex(['user_id', 'book_id'], ['unique' => true, 'name' => 'uq_user_book'])
            ->create();
    }
}
