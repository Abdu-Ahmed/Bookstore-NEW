<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrders extends AbstractMigration
{
    public function change(): void
    {
        // orders table
        $orders = $this->table('orders', ['id' => 'order_id']);
        $orders->addColumn('user_id', 'integer', ['null' => true])
               ->addColumn('session_id', 'string', ['limit' => 128, 'null' => false])
               ->addColumn('amount', 'integer', ['null' => false, 'comment' => 'total in cents'])
               ->addColumn('currency', 'string', ['limit' => 10, 'null' => false, 'default' => 'usd'])
               ->addColumn('status', 'string', ['limit' => 50, 'null' => false, 'default' => 'pending'])
               ->addColumn('metadata', 'text', ['null' => true])
               ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
               ->addIndex(['session_id'], ['unique' => true, 'name' => 'idx_orders_session'])
               ->create();

        // order_items table
        $items = $this->table('order_items', ['id' => 'order_item_id']);
        $items->addColumn('order_id', 'integer', ['null' => false])
              ->addColumn('book_id', 'integer', ['null' => true])
              ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('unit_price', 'integer', ['null' => false, 'comment' => 'price in cents'])
              ->addColumn('quantity', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['order_id'], ['name' => 'idx_order_items_order'])
              ->create();
    }
}
