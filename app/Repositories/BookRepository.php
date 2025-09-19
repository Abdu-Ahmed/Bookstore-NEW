<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Interfaces\BookRepositoryInterface;
use App\Models\Book;
use PDO;

/**
 * PDO-backed repository for books. Returns Book objects.
 */
final class BookRepository implements BookRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** (same buildFilters as before) */
    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['search'])) {
            $clauses[] = '('
                . 'book_title LIKE :q_title OR '
                . 'book_description LIKE :q_description OR '
                . 'book_author LIKE :q_author'
                . ')';
            $q = '%' . $filters['search'] . '%';
            $params[':q_title']       = $q;
            $params[':q_description'] = $q;
            $params[':q_author']      = $q;
        }

        if (!empty($filters['category'])) {
            $clauses[] = 'book_genre = :category';
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['author'])) {
            $clauses[] = 'book_author = :author';
            $params[':author'] = $filters['author'];
        }

        if (isset($filters['minPrice']) && $filters['minPrice'] !== '') {
            $clauses[] = 'book_price >= :minPrice';
            $params[':minPrice'] = (float) $filters['minPrice'];
        }

        if (isset($filters['maxPrice']) && $filters['maxPrice'] !== '') {
            $clauses[] = 'book_price <= :maxPrice';
            $params[':maxPrice'] = (float) $filters['maxPrice'];
        }

        $sql = '';
        if ($clauses !== []) {
            $sql = ' WHERE ' . implode(' AND ', $clauses);
        }

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Fetch paginated books with optional filters and sorting.
     * Returns array of Book objects.
     *
     * @return Book[]
     */
    public function fetch(array $filters, ?string $sort, int $limit, int $offset): array
    {
        $bf = $this->buildFilters($filters);

        $order = ' ORDER BY book_id DESC';
        switch ($sort) {
            case 'price_asc':
                $order = ' ORDER BY CAST(book_price AS DECIMAL(10,2)) ASC';
                break;
            case 'price_desc':
                $order = ' ORDER BY CAST(book_price AS DECIMAL(10,2)) DESC';
                break;
            case 'title_asc':
                $order = ' ORDER BY book_title ASC';
                break;
            case 'newest':
                $order = ' ORDER BY created_at DESC';
                break;
        }

        $sql = 'SELECT book_id, book_title, book_description, book_author, book_price, book_genre, book_image, created_at, updated_at, status FROM books'
            . $bf['sql']
            . $order
            . ' LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        foreach ($bf['params'] as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $books = [];
        foreach ($rows as $r) {
            $books[] = Book::fromArray($r);
        }

        return $books;
    }

    public function count(array $filters): int
    {
        $bf = $this->buildFilters($filters);

        $sql = 'SELECT COUNT(*) AS c FROM books' . $bf['sql'];
        $stmt = $this->pdo->prepare($sql);

        foreach ($bf['params'] as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['c'] ?? 0);
    }

    public function findById(int $id): ?Book
    {
        $stmt = $this->pdo->prepare('SELECT book_id, book_title, book_description, book_author, book_price, book_genre, book_image, created_at, updated_at, status FROM books WHERE book_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : Book::fromArray($row);
    }

    public function fetchCategories(): array
    {
        $stmt = $this->pdo->query('SELECT DISTINCT book_genre FROM books WHERE book_genre IS NOT NULL AND book_genre != \'\' ORDER BY book_genre ASC');
        return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchAuthors(): array
    {
        $stmt = $this->pdo->query('SELECT DISTINCT book_author FROM books WHERE book_author IS NOT NULL AND book_author != \'\' ORDER BY book_author ASC');
        return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch random books - returns Book objects.
     *
     * @return Book[]
     */
    public function fetchRandom(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare('SELECT book_id, book_title, book_description, book_author, book_price, book_genre, book_image FROM books ORDER BY RAND() LIMIT :n');
        $stmt->bindValue(':n', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $books = [];
        foreach ($rows as $r) {
            $books[] = Book::fromArray($r);
        }
        return $books;
    }
}
