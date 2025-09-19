<?php
declare(strict_types=1);
/**
 * Checkout summary view
 *
 * Expects:
 *  - array $items
 *  - array $minicart
 *  - string $base_url  (optional)
 */

$base = (string) ($base_url ?? (defined('BASE_URL') ? BASE_URL : ''));

// compute total here (fallbacks)
$items = $items ?? [];
$minicart = $minicart ?? ['count' => 0, 'subtotal' => 0.0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once APP_ROOT . '/app/Views/layout/head.php'; ?>
    <!-- Load Stripe.js only on the checkout page (needed for redirectToCheckout fallback) -->
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // expose publishable key picked from settings if controller passed it via view params
        // fallback to a global var that might've be exposed in layout/controller
        window.STRIPE_PUBLISHABLE_KEY = "<?= htmlspecialchars($stripe_publishable ?? '') ?>";
        window.BASE_URL = "<?= htmlspecialchars($base) ?>";
    </script>
</head>
<body>
    <?php require_once APP_ROOT . '/app/Views/layout/nav.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1>Checkout</h1>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <?php if (empty($items)): ?>
                    <p>Your cart is empty. <a href="<?= htmlspecialchars($base . '/books') ?>">Continue shopping</a>.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0; foreach ($items as $it):
                                $qty = (int)($it['quantity'] ?? 1);
                                $price = (float)($it['book_price'] ?? ($it['price'] ?? 0.0));
                                $subtotal = $qty * $price;
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($it['book_image'])): ?>
                                                <img src="<?= htmlspecialchars($it['book_image']) ?>" alt="" style="width:60px;height:auto;margin-right:12px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($it['book_title'] ?? ($it['title'] ?? 'Item')) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?= number_format($price, 2) ?></td>
                                    <td><?= $qty ?></td>
                                    <td>$<?= number_format($subtotal, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong>$<?= number_format($total, 2) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- JS-driven checkout button -->
                    <div class="mb-3">
                        <button id="checkout-btn" class="btn btn-dark">Pay with Card (Stripe Test)</button>
                    </div>

                    <script>
                        document.getElementById('checkout-btn').addEventListener('click', function (e) {
                            e.preventDefault();

                            // call the helper i added in checkout.js
                            // it will POST to /checkout/create and then redirect
                            createStripeSessionAndRedirect({
                                endpoint: "<?= htmlspecialchars($base . '/checkout/create') ?>",
                                publishableKey: window.STRIPE_PUBLISHABLE_KEY
                            });
                        });
                    </script>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="card p-3">
                    <h5>Order summary</h5>
                    <p>Items: <?= $minicart['count'] ?? 0 ?></p>
                   <p>Subtotal: $<?= number_format($minicart['subtotal'] ?? 0, 2) ?></p>
                    <a href="<?= htmlspecialchars($base . '/cart') ?>" class="btn btn-outline-secondary">Edit Cart</a>
                </div>
            </div>
        </div>
    </div>

    <?php require_once APP_ROOT . '/app/Views/layout/footer.php'; ?>
</body>
</html>
