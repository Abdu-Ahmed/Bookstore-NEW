<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\BookService;
use App\Core\Container;
use App\Models\Book;

final class BookDetailController extends Controller
{
    private BookService $service;

    public function __construct(BookService $service, Container $container)
    {
        parent::__construct($container);
        $this->service = $service;
    }

    public function show(Request $request, int $id): Response
    {
        try {
            $book = $this->service->getById($id);
            if ($book === null) {
                return $this->view('404', ['message' => 'Book not found'], 404);
            }

            // convert to array for view compatibility
            $bookArray = $book->toArray();

            return $this->view('books/detail', [
                'book' => $bookArray,
                'isLoggedIn' => isset($_SESSION['user_id']),
                'categories' => $this->service->getCategories(),
            ]);
        } catch (\Exception $e) {
            error_log('BookDetailController error: ' . $e->getMessage());

            return $this->view('500', [
                'message' => 'An error occurred while loading the book details'
            ], 500);
        }
    }
}
