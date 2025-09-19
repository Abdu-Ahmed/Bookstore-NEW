<?php
declare(strict_types=1);

/**
 * app/Views/home.php
 *
 * Expects the following variables (extracted by the View renderer):
 *  - array  $books        (may be empty)
 *  - array  $randomBooks  (may be empty)
 *  - string $keyword
 *  - bool   $isLoggedIn
 *  - string $base_url
 *
 * Use $escape($value) for escaping (closure injected by the View renderer).
 */

/** @var array<int,array<string,mixed>> $books */
$books = $books ?? [];

/** @var array<int,array<string,mixed>> $randomBooks */
$randomBooks = $randomBooks ?? [];

/** @var string $keyword */
$keyword = (string) ($keyword ?? '');

/** @var bool $isLoggedIn */
$isLoggedIn = (bool) ($isLoggedIn ?? false);

/** @var string $base_url */
$base = (string) ($base_url ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/layout/head.php'; ?>
</head>
<body>
    <!-- navigation -->
    <?php require __DIR__ . '/layout/nav.php'; ?>

    <div class="container mt-5">
        <h2 class="display-5">Welcome to Abdu's Bookstore!</h2>

        <?php if ($isLoggedIn): ?>
            <p class="display-6">
                Hello, <strong><?= $escape($_SESSION['username'] ?? ''); ?></strong>! Welcome back.
                Explore our collection of books and add them to your cart.
            </p>
            <div class="d-flex gap-3">
                <a href="<?= $escape($base) . '/cart' ?>" class="btn btn-dark rounded-pill">View Cart</a>
                <a href="<?= $escape($base) . '/admin' ?>" class="btn btn-dark rounded-pill">Admin Panel</a>
            </div>
        <?php else: ?>
            <p class="display-6">
                Please
                <a href="<?= $escape($base) . '/login' ?>" class="btn-lg btn btn-dark">Sign-in</a>
                or
                <a href="<?= $escape($base) . '/register' ?>" class="btn-lg btn btn-dark">register</a>
                to start shopping.
            </p>
        <?php endif; ?>
    </div>

    <br>

    <h1 class="d-flex justify-content-center mt-5">Recommended reads</h1>

    <section id="billboard" class="bg-gray padding-large">
        <div class="swiper main-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($randomBooks as $book): ?>
                    <?php
                    $img = $book['book_image'] ?? ($base . '/assets/images/placeholder.png');
                    $title = $book['book_title'] ?? 'Untitled';
                    $desc = $book['book_description'] ?? '';
                    $id = $book['book_id'] ?? '';
                    ?>
                    <div class="swiper-slide">
                        <div class="container">
                            <div class="row">
                                <div class="offset-md-1 col-md-5">
                                    <img src="<?= $escape($img) ?>" alt="product-img" class="img-fluid mb-3">
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="banner-content">
                                        <h2><?= $escape($title) ?></h2>
                                        <p class="fs-3"><?= $escape($desc) ?></p>
                                        <a href="<?= $escape($base) . '/book-detail/' . $escape((string)$id) ?>" class="btn">Shop now →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="main-slider-pagination text-center mt-3"></div>
    </section>

    <!-- Books Carousel Section -->
    <section id="products" class="product-store position-relative padding-xlarge pb-0">
        <div class="container display-header d-flex flex-wrap justify-content-between pb-4">
            <h3 class="">Best selling Items</h3>
            <div class="d-flex justify-content-between w-100 align-items-center">
                <a href="<?= $escape($base) . '/books' ?>" class="btn me-auto">View all items →</a>
                <div class="swiper-buttons d-flex align-items-center">
                    <button class="swiper-prev product-carousel-prev me-2">
                        <svg width="41" height="41"><use xlink:href="#angle-left"></use></svg>
                    </button>
                    <button class="swiper-next product-carousel-next">
                        <svg width="41" height="41"><use xlink:href="#angle-right"></use></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="swiper product-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($books as $book): ?>
                    <?php
                    $img = $book['book_image'] ?? ($base . '/assets/images/placeholder.png');
                    $title = $book['book_title'] ?? 'Untitled';
                    $price = $book['book_price'] ?? '0.00';
                    $id = $book['book_id'] ?? '';
                    ?>
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="offset-md-1 col-md-5">
                                <img src="<?= $escape($img) ?>" alt="<?= $escape($title) ?>" class="img-fluid book-prdct mx-auto">
                            </div>
                            <div class="card-detail text-center pt-3 pb-2">
                                <h5 class="card-title fs-4 text-uppercase m-0">
                                    <a href="<?= $escape($base) . '/book-detail/' . $escape((string)$id) ?>"><?= $escape($title) ?></a>
                                </h5>
                                <span class="item-price text-primary fs-4">$<?= $escape((string)$price) ?></span>
                                <div class="cart-button mt-1">
                                    <?php if ($isLoggedIn): ?>
                                        <form action="<?= $escape($base) . '/cart/add/' . $escape((string)$id) ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn">Add to cart</button>
                                        </form>
                                    <?php else: ?>
                                        <p><a href="<?= $escape($base) . '/login' ?>" class="btn btn-dark">Log in</a> to add this book to your cart.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <?php require __DIR__ . '/layout/footer.php'; ?>
    </footer>

    <script src="<?= $escape($base) . '/assets/js/jquery-1.11.0.min.js' ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= $escape($base) . '/assets/js/plugins.js' ?>"></script>
    <script type="text/javascript" src="<?= $escape($base) . '/assets/js/script.js' ?>"></script>
</body>
</html>
