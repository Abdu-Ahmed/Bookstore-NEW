<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Interfaces\BookRepositoryInterface;
use App\Repositories\BookRepository;
use App\Core\Container;
use App\Core\Controller;

/**
 * HomeController — thin controller for the landing page.
 */
final class HomeController extends Controller
{
    private BookRepositoryInterface $books;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        // Prefer repository from container (registered in services.php).
        if ($this->containerHas(BookRepository::class)) {
            $this->books = $this->container->get(BookRepository::class);
        } elseif ($this->containerHas(BookRepositoryInterface::class)) {
            $this->books = $this->container->get(BookRepositoryInterface::class);
        } else {
            // No repository — create an inert repo that returns empty arrays
            $this->books = new class implements BookRepositoryInterface {
                public function fetch(array $filters, ?string $sort, int $limit, int $offset): array { return []; }
                public function count(array $filters): int { return 0; }
                public function findById(int $id): ?\App\Models\Book { return null; }
                public function fetchCategories(): array { return []; }
                public function fetchAuthors(): array { return []; }
                public function fetchRandom(int $limit = 5): array { return []; }
            };
        }
    }

    /**
     * Show the landing page.
     */
    public function index(Request $request): Response
    {
        // NOTE: some Request implementations use getQuery/getQueryParams/getQueryParam;
        // original code used getQuery('search','') — keep that but fall back to getQueryParams()
        $keyword = '';
        if (method_exists($request, 'getQuery')) {
            $keyword = (string) $request->getQuery('search', '');
        } elseif (method_exists($request, 'getQueryParams')) {
            $keyword = (string) ($request->getQueryParams()['search'] ?? '');
        }

        $isLoggedIn = isset($_SESSION['user_id']);

        // Build filters array for the new repository method
        $filters = [];
        if ($keyword !== '') {
            $filters['search'] = $keyword;
        }

        // Use the new fetch method instead of getAllBooks/searchBooks
        // For homepage, we'll show recent books (limit to reasonable number)
        $booksRaw = $this->books->fetch($filters, 'newest', 12, 0);

        // Use the new fetchRandom method
        $randomRaw = $this->books->fetchRandom(5);

        // Normalize book objects -> arrays for views
        $books = $this->normalizeBooks($booksRaw);
        $randomBooks = $this->normalizeBooks($randomRaw);

        // Fetch categories for the navigation dropdown
        $categories = $this->books->fetchCategories();

        // Provide data for the view. Your view templates expect these keys.
        $viewParams = [
            'books' => $books,
            'randomBooks' => $randomBooks,
            'categories' => $categories,
            'keyword' => $keyword,
            'isLoggedIn' => $isLoggedIn,
            'base_url' => $this->container->get('settings')['app']['base_path'] ?? '',
        ];

        return $this->view('home', $viewParams);
    }

    /**
     * Helper: check whether the container has a service without throwing.
     */
    private function containerHas(string $service): bool
    {
        try {
            $this->container->get($service);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Normalize an iterable/list of books into arrays for the views.
     *
     * Accepts arrays, Book objects (with toArray), or plain objects/arrays from repo.
     *
     * @param iterable|array $list
     * @return array<int,array<string,mixed>>
     */
    private function normalizeBooks($list): array
    {
        $out = [];
        if ($list === null) {
            return $out;
        }

        foreach ($list as $b) {
            $out[] = $this->normalizeBook($b);
        }

        return $out;
    }

    /**
     * Normalize single book value to an array.
     *
     * @param mixed $b
     * @return array<string,mixed>
     */
    private function normalizeBook($b): array
    {
        if (is_array($b)) {
            return $b;
        }

        if (is_object($b)) {
            // Prefer a toArray method on model objects
            if (method_exists($b, 'toArray')) {
                return (array) $b->toArray();
            }

            // Try common property names mapping
            $vars = get_object_vars($b);

            // If model uses id/title/price keys instead of book_id/book_title/book_price,
            // map those to what views expect.
            if (!isset($vars['book_id']) && isset($vars['id'])) {
                $vars['book_id'] = $vars['id'];
            }
            if (!isset($vars['book_title']) && isset($vars['title'])) {
                $vars['book_title'] = $vars['title'];
            }
            if (!isset($vars['book_price']) && isset($vars['price'])) {
                $vars['book_price'] = $vars['price'];
            }

            return $vars;
        }

        // otherwise return empty array
        return [];
    }
}
