<?php
declare(strict_types=1);

/**
 * app/Views/books/detail.php
 *
 * Variables expected:
 *  - array $book
 *  - bool $isLoggedIn
 *  - array $categories
 *  - string $base_url (optional)
 */

$book = $book ?? [];
$isLoggedIn = (bool)($isLoggedIn ?? false);
$categories = $categories ?? [];
$base = (string) ($base_url ?? (defined('BASE_URL') ? BASE_URL : ''));
$escape = $escape ?? static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$title = (string) ($book['book_title'] ?? 'Untitled');
$img = $book['book_image'] ?? ($base . '/assets/images/placeholder.png');
$price = $book['book_price'] ?? '0.00';
$description = (string)($book['book_description'] ?? '');
$genre = (string)($book['book_genre'] ?? '');
$id = (int)($book['book_id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once APP_ROOT . '/app/Views/layout/head.php'; ?>
</head>
<body>
    <?php require_once APP_ROOT . '/app/Views/layout/nav.php'; ?>

    <section class="breadcrumbs-section mt-5">
        <div class="container text-center">
            <h1><?= $escape($title) ?></h1>
            <div class="breadcrumbs">
                <span class="item"><a href="<?= $escape((defined('BASE_URL') ? BASE_URL : '/')) ?>">Home &gt;</a></span>
                <span class="item">Book Details</span>
            </div>
        </div>
    </section>

    <section class="single-product padding-large">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 text-center">
                    <img src="<?= $escape($img) ?>" alt="<?= $escape($title) ?>" class="img-fluid" style="max-height:480px; object-fit:contain;">
                </div>

                <div class="col-lg-6">
                    <h2><?= $escape($title) ?></h2>
                    <div class="mb-3">
                        <span class="fs-2 text-primary">$<?= $escape((string)$price) ?></span>
                    </div>
                    <p><?= nl2br($escape($description)) ?></p>

                    <?php if ($isLoggedIn): ?>
                        <form action="<?= $escape(rtrim($base, '/') . '/cart/add/' . (string)$id) ?>" method="POST" class="mb-3">
                            <div class="mb-2" style="max-width:140px">
                                <label for="qty" class="form-label">Quantity</label>
                                <input id="qty" name="quantity" type="number" min="1" value="1" class="form-control">
                            </div>
                            <button class="btn btn-dark" type="submit">Add to cart</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= $escape(rtrim($base, '/') . '/login') ?>" class="btn btn-dark">Log in to buy</a>
                    <?php endif; ?>

                    <hr>

                    <div class="meta">
                        <div><strong>Genre:</strong> <?= $escape($genre) ?></div>
                        <div><strong>SKU:</strong> <?= $escape((string)$id) ?></div>
                    </div>
                </div>
            </div>

            <!-- Tabs (Description / Reviews) -->
            <div class="row mt-5">
                <div class="col-12">
                    <ul class="nav nav-tabs" id="productTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button" role="tab">Description</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Reviews</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 bg-light">
                        <div class="tab-pane fade show active" id="desc" role="tabpanel">
                            <p><?= nl2br($escape($description)) ?></p>
                        </div>
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <p>No reviews yet.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php require_once APP_ROOT . '/app/Views/layout/footer.php'; ?>

    <script src="<?= $escape(rtrim($base, '/') . '/assets/js/jquery-1.11.0.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
