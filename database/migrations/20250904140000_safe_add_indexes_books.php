<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * SafeAddIndexesBooks
 *
 * This migration attempts to add indexes to the existing `books` table.
 * It catches duplicate-key errors and continues, so it is safe to run repeatedly.
 */
final class SafeAddIndexesBooks extends AbstractMigration
{
    public function up(): void
    {
        $indexes = [
            'idx_book_genre' => ['book_genre'],
            'idx_book_author' => ['book_author'],
            'idx_book_price' => ['book_price'],
            'idx_book_title' => ['book_title'],
        ];

        foreach ($indexes as $name => $cols) {
            $colsSql = implode(', ', array_map(static function ($c) {
                return "`{$c}`";
            }, $cols));

            $sql = "ALTER TABLE `books` ADD INDEX `{$name}` ({$colsSql})";
            try {
                $this->execute($sql);
            } catch (\Throwable $e) {
                // MySQL duplicate-index error code is 1061 (SQLSTATE 42000).
                // If it's a duplicate-key error, ignore it; otherwise rethrow.
                $msg = (string) $e->getMessage();
                if (strpos($msg, 'Duplicate key name') !== false || strpos($msg, '1061') !== false) {
                    // index already exists — ignore
                    continue;
                }

                // rethrow unexpected exceptions so migration fails loudly
                throw $e;
            }
        }
    }

    public function down(): void
    {
        $names = [
            'idx_book_genre',
            'idx_book_author',
            'idx_book_price',
            'idx_book_title',
        ];

        foreach ($names as $name) {
            try {
                $this->execute("ALTER TABLE `books` DROP INDEX `{$name}`");
            } catch (\Throwable $e) {
                // ignore errors when dropping missing indexes
                $msg = (string) $e->getMessage();
                if (strpos($msg, 'check that it exists') !== false || strpos($msg, 'doesn\'t exist') !== false) {
                    continue;
                }
                throw $e;
            }
        }
    }
}
