<?php
declare(strict_types=1);

/**
 * app/Views/books/index.php
 *
 * Variables expected from controller:
 *  - array  $books
 *  - int    $totalPages
 *  - int    $currentPage
 *  - string $keyword
 *  - string $author
 *  - string $category
 *  - string|int $minPrice
 *  - string|int $maxPrice
 *  - string|null $sort
 *  - array  $categories
 *  - array  $authors
 *  - bool   $isLoggedIn
 *  - string $base_url (optional)
 *
 * This view uses a safe $escape closure and builds pagination links that preserve current GET params.
 */

/** Defensive defaults */
$books = $books ?? [];
$totalPages = (int)($totalPages ?? 1);
$currentPage = max(1, (int)($currentPage ?? 1));
$keyword = (string)($keyword ?? '');
$author = (string)($author ?? '');
$category = (string)($category ?? '');
$minPrice = $minPrice ?? '';
$maxPrice = $maxPrice ?? '';
$sort = $sort ?? null;
$categories = $categories ?? [];
$authors = $authors ?? [];
$isLoggedIn = (bool)($isLoggedIn ?? false);
$base = (string) ($base_url ?? (defined('BASE_URL') ? BASE_URL : ''));

/** Escape helper (if the renderer didn't inject one) */
$escape = $escape ?? static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/** Build a URL for /books preserving current GET parameters and applying overrides */
$buildUrl = static function (array $overrides = []) use ($escape, $base) : string {
    $qs = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($qs[$k]);
        } else {
            $qs[$k] = (string)$v;
        }
    }
    $query = http_build_query($qs);
    $url = rtrim($base, '/') . '/books' . ($query !== '' ? ('?' . $query) : '');
    return $escape($url);
};
?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once APP_ROOT . '/app/Views/layout/head.php'; ?>
</head>
<body>
    <?php require_once APP_ROOT . '/app/Views/layout/nav.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12 text-center">
                <h1><?= $escape($keyword !== '' ? 'Search Results' : 'Shop') ?></h1>
                <div class="breadcrumbs">
                    <span class="item"><a href="<?= $escape((defined('BASE_URL') ? BASE_URL : '/')) ?>">Home &gt;</a></span>
                    <span class="item"><?= $escape($keyword !== '' ? 'Search Results' : 'Shop') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container p-5">
        <div class="row">
            <!-- Sidebar / Filters -->
            <aside class="col-md-3 border-end">
                <div class="sidebar">
                    <div class="widget-search-bar mb-4">
                        <form method="GET" action="<?= $escape(rtrim($base, '/') . '/books') ?>">
                            <div class="input-group">
                                <input name="search" class="form-control" type="search" placeholder="Search"
                                       value="<?= $escape($keyword) ?>">
                                                                    <button type="submit" class="btn btn-link p-0 border-0" aria-label="Search">
                                        <i class="fas fa-search" aria-hidden="true"></i>
                                    </button>
                            </div>
                        </form>
                    </div>

                    <form method="GET" action="<?= $escape(rtrim($base, '/') . '/books') ?>">
                        <!-- Keep existing GET values so switching filters doesn't clear others -->
                        <input type="hidden" name="sort" value="<?= $escape($sort ?? '') ?>">

                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select id="category" name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): 
                                    $g = (string)($cat['book_genre'] ?? $cat['genre'] ?? '');
                                ?>
                                    <option value="<?= $escape($g) ?>" <?= $g === $category ? 'selected' : '' ?>>
                                        <?= $escape($g) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author</label>
                            <select id="author" name="author" class="form-select" onchange="this.form.submit()">
                                <option value="">All Authors</option>
                                <?php foreach ($authors as $auth):
                                    $aName = (string)($auth['book_author'] ?? $auth['author'] ?? '');
                                ?>
                                    <option value="<?= $escape($aName) ?>" <?= $aName === $author ? 'selected' : '' ?>>
                                        <?= $escape($aName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price range</label>
                            <div class="d-flex gap-2">
                                <input name="minPrice" type="number" class="form-control" placeholder="Min" value="<?= $escape($minPrice) ?>">
                                <input name="maxPrice" type="number" class="form-control" placeholder="Max" value="<?= $escape($maxPrice) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sort" class="form-label">Sort by</label>
                            <select id="sort" name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="">Default</option>
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low → high</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high → low</option>
                                <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Title A → Z</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Filter</button>
                            <a class="btn btn-primary" href="<?= $escape(rtrim($base, '/') . '/books') ?>">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Listing -->
            <div class="col-md-9">
                <div class="row">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): 
                            $id = $book['book_id'] ?? '';
                            $title = $book['book_title'] ?? '';
                            $img = $book['book_image'] ?? ($base . '/assets/images/placeholder.png');
                            $price = $book['book_price'] ?? '0.00';
                        ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="product-card h-100">
                                    <div class="image-holder text-center p-3">
                                        <a href="<?= $escape(rtrim($base, '/') . '/book-detail/' . (string)$id) ?>">
                                            <img src="<?= $escape($img) ?>" alt="<?= $escape($title) ?>" class="img-fluid" style="max-height:240px; object-fit:contain;">
                                        </a>
                                    </div>
                                    <div class="card-detail text-center p-3">
                                        <h5 class="card-title fs-6 text-uppercase">
                                            <a href="<?= $escape(rtrim($base, '/') . '/book-detail/' . (string)$id) ?>"><?= $escape($title) ?></a>
                                        </h5>
                                        <div class="mb-2"><span class="item-price text-primary fs-5">$<?= $escape((string)$price) ?></span></div>
                                        <div class="cart-button">
                                            <?php if ($isLoggedIn): ?>
                                                <form action="<?= $escape(rtrim($base, '/') . '/cart/add/' . (string)$id) ?>" method="POST" class="d-inline">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="btn">Add to cart</button>
                                                </form>
                                            <?php else: ?>
                                                <a href="<?= $escape(rtrim($base, '/') . '/login') ?>" class="btn btn-dark btn-sm">Log in to buy</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12"><p>No books found for your query.</p></div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center align-items-center mt-4">
                    <?php if ($currentPage > 1): ?>
                        <a class="me-3" href="<?= $buildUrl(['page' => $currentPage - 1]) ?>" aria-label="prev">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php
                    // Show a compact pagination window
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $currentPage + 2);
                    for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i === $currentPage): ?>
                            <span class="mx-1 fw-bold"><?= $i ?></span>
                        <?php else: ?>
                            <a class="mx-1" href="<?= $buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="ms-3" href="<?= $buildUrl(['page' => $currentPage + 1]) ?>" aria-label="next">Next &raquo;</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <?php require_once APP_ROOT . '/app/Views/layout/footer.php'; ?>
</body>
</html>
