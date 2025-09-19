<?php
/** @var array<int,array<string,mixed>> $items */
$items = $items ?? [];

/** @var array<int,array<string,mixed>> $categories */
$categories = $categories ?? [];

/** @var string $keyword */
$keyword = (string) ($keyword ?? '');

/** @var bool $isLoggedIn */
$isLoggedIn = (bool) ($is_logged_in ?? false);

/** base url helper (View may provide) */
$base = defined('BASE_URL') ? BASE_URL : ($base_url ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php /* head.php uses $base or BASE_URL inside */ ?>
    <?= $this->render('layout/head') ?>
</head>
<body>
    <?= $this->render('layout/nav', ['categories' => $categories, 'keyword' => $keyword, 'isLoggedIn' => $is_logged_in]) ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12 text-center padding-medium no-padding-bottom">
                <h1>Your Cart</h1>
                <div class="breadcrumbs">
                    <span class="item"><a href="<?= $escape($base) ?>">Home &gt;</a></span>
                    <span class="item">Your Cart</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$isLoggedIn): ?>
        <div class="container mt-5 text-center">
            <h3>Please log in or register to view your cart</h3>
            <a href="<?= $escape($base . '/login') ?>" class="btn btn-dark">Log In</a>
            <a href="<?= $escape($base . '/register') ?>" class="btn btn-dark">Register</a>
        </div>
    <?php else: ?>
        <section class="shopify-cart padding-large">
            <div class="container">
                <div class="row">
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="text-uppercase">
                                <tr>
                                    <th scope="col" class="fw-light">Product</th>
                                    <th scope="col" class="fw-light">Quantity</th>
                                    <th scope="col" class="fw-light">Subtotal</th>
                                    <th scope="col" class="fw-light"></th>
                                </tr>
                            </thead>
                            <tbody class="border-top border-gray">
                                <?php foreach ($items as $item): ?>
                                    <tr class="border-bottom border-gray">
                                        <td class="align-middle border-0" scope="row">
                                            <div class="cart-product-detail d-flex align-items-center">
                                                <div class="card-image">
                                                    <img src="<?= $escape($item['book_image']) ?>" alt="<?= $escape($item['book_title']) ?>" class="img-fluid">
                                                </div>
                                                <div class="card-detail ps-3">
                                                    <h5 class="card-title fs-4 text-uppercase">
                                                        <a href="<?= $escape($base . '/book-detail/' . (int)$item['book_id']) ?>"><?= $escape($item['book_title']) ?></a>
                                                    </h5>
                                                    <span class="item-price text-primary fs-4">$<?= $escape((string)$item['book_price']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle border-0">
                                            <form action="<?= $escape($base . '/cart/update/' . (int)$item['cart_item_id']) ?>" method="POST" class="d-inline-block">
                                                <div class="input-group product-qty" style="max-width: 150px;">
                                                    <input type="number" name="quantity" class="form-control input-number text-center" value="<?= $escape((string)$item['quantity']) ?>" min="1" required>
                                                </div>
                                                <button type="submit" class="btn btn-dark mt-2">Update</button>
                                            </form>
                                        </td>
                                        <td class="align-middle border-0">
                                            <span class="item-price text-primary fs-3 fw-medium">$<?= $escape((string)($item['quantity'] * $item['book_price'])) ?></span>
                                        </td>
                                        <td class="align-middle border-0 cart-remove">
                                            <a href="<?= $escape($base . '/cart/remove/' . (int)$item['cart_item_id']) ?>" aria-label="Remove item">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($items)): ?>
                                    <tr><td colspan="4" class="text-center">Your cart is empty.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="cart-bottom d-flex flex-wrap justify-content-between align-items-center">
                            <a href="<?= $escape($base . '/books') ?>" class="btn btn-dark">Continue Shopping</a>
                            <a href="<?= $escape($base . '/checkout') ?>" class="btn btn-dark">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?= $this->render('layout/footer') ?>
</body>
</html>
