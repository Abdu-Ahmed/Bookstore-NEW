<?php
declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Book;

interface BookRepositoryInterface
{
    /**
     * Fetch books according to filters, sort, limit and offset.
     *
     * Returns an array of Book objects.
     *
     * @param array $filters
     * @param string|null $sort
     * @param int $limit
     * @param int $offset
     * @return Book[]
     */
    public function fetch(array $filters, ?string $sort, int $limit, int $offset): array;

    /**
     * Count total rows matching filters.
     */
    public function count(array $filters): int;

    /**
     * Find one book by id.
     */
    public function findById(int $id): ?Book;

    /**
     * Fetch distinct categories.
     *
     * @return array<int,array<string,string>>
     */
    public function fetchCategories(): array;

    /**
     * Fetch distinct authors.
     *
     * @return array<int,array<string,string>>
     */
    public function fetchAuthors(): array;

    /**
     * Fetch random books.
     *
     * @return Book[]
     */
    public function fetchRandom(int $limit = 5): array;
}
