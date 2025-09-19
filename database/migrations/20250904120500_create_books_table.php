<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBooksTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('books', ['id' => 'book_id']);
        $table
            ->addColumn('book_title', 'string', ['limit' => 255])
            ->addColumn('book_description', 'text', ['null' => true])
            ->addColumn('book_author', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('book_price', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => '0.00'])
            ->addColumn('book_genre', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('book_image', 'string', ['limit' => 1024, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 100, 'default' => 'active'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['book_genre'], ['name' => 'idx_book_genre'])
            ->addIndex(['book_author'], ['name' => 'idx_book_author'])
            ->addIndex(['book_price'], ['name' => 'idx_book_price'])
            ->create();
    }
}
