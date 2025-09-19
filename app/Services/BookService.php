<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\Interfaces\BookRepositoryInterface;
use App\Models\Book;

/**
 * BookService - keeps controllers thin; returns Book objects.
 */
final class BookService
{
    private BookRepositoryInterface $repo;

    public function __construct(BookRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function prepareListParams(array $query): array
    {
        $page = max(1, (int)($query['page'] ?? 1));
        $limit = (int)($query['limit'] ?? 12);
        if ($limit <= 0 || $limit > 100) {
            $limit = 12;
        }
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => isset($query['search']) ? trim((string)$query['search']) : '',
            'category' => isset($query['category']) ? trim((string)$query['category']) : '',
            'author' => isset($query['author']) ? trim((string)$query['author']) : '',
            'minPrice' => isset($query['minPrice']) && $query['minPrice'] !== '' ? (int)$query['minPrice'] : '',
            'maxPrice' => isset($query['maxPrice']) && $query['maxPrice'] !== '' ? (int)$query['maxPrice'] : '',
        ];

        $allowedSorts = ['price_asc', 'price_desc', 'newest', 'title_asc'];
        $sort = isset($query['sort']) && in_array((string)$query['sort'], $allowedSorts, true) ? (string)$query['sort'] : null;

        return [
            'filters' => $filters,
            'sort' => $sort,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
        ];
    }

    /**
     * Return array with 'items' => Book[] and 'total' => int
     *
     * @param array $params
     * @return array{items:Book[], total:int}
     */
    public function list(array $params): array
    {
        $filters = $params['filters'];
        $sort = $params['sort'];
        $limit = $params['limit'];
        $offset = $params['offset'];

        $items = $this->repo->fetch($filters, $sort, $limit, $offset);
        $total = $this->repo->count($filters);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /** @return array<int,array<string,string>> */
    public function getCategories(): array
    {
        return $this->repo->fetchCategories();
    }

    /** @return array<int,array<string,string>> */
    public function getAuthors(): array
    {
        return $this->repo->fetchAuthors();
    }

    /** @return Book[] */
    public function getRandom(int $n = 5): array
    {
        return $this->repo->fetchRandom($n);
    }

    public function getById(int $id): ?Book
    {
        return $this->repo->findById($id);
    }
}
