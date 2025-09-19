<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\BookService;
use App\Core\Container;

/**
 * BooksController - listing and filtering
 */
final class BooksController extends Controller
{
    private BookService $service;
    protected Container $container;

    public function __construct(BookService $service, Container $container)
    {
        parent::__construct($container);
        $this->service = $service;
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $params = $this->service->prepareListParams(
            method_exists($request, 'getQueryParams') ? $request->getQueryParams() : []
        );
        $result = $this->service->list($params);

        $categories = $this->service->getCategories();
        $authors = $this->service->getAuthors();

        $totalPages = (int)ceil($result['total'] / $params['limit']);

        // Normalize books for view (handles Book objects)
        $books = $this->normalizeBooks($result['items'] ?? []);

        return $this->view('books/index', [
            'books' => $books,
            'totalPages' => $totalPages,
            'currentPage' => $params['page'],
            'keyword' => $params['filters']['search'],
            'author' => $params['filters']['author'],
            'category' => $params['filters']['category'],
            'minPrice' => $params['filters']['minPrice'],
            'maxPrice' => $params['filters']['maxPrice'],
            'sort' => $params['sort'],
            'categories' => $categories,
            'authors' => $authors,
            'isLoggedIn' => isset($_SESSION['user_id']),
        ]);
    }

    public function filterByCategory(Request $request, string $category): Response
    {
        // optional: map route parameter into GET params and reuse index
        $query = method_exists($request, 'getQueryParams') ? $request->getQueryParams() : [];
        $query['category'] = urldecode($category);
        $newRequest = method_exists($request, 'withQueryParams') ? $request->withQueryParams($query) : $request;

        return $this->index($newRequest);
    }

    /**
     * Normalize an iterable/list of books into arrays for the views.
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
            if (method_exists($b, 'toArray')) {
                return (array) $b->toArray();
            }

            $vars = get_object_vars($b);
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

        return [];
    }
}
