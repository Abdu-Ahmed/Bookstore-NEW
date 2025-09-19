<?php
declare(strict_types=1);
/**
 * Partial: minicart
 *
 * Expects $minicart array shape:
 *  - count:int
 *  - subtotal:float
 *  - items: array of items with keys: cart_item_id, book_id, book_title, book_image, book_price, quantity, line_total
 *
 * Uses $escape closure from parent view (if present). Provide fallback.
 */


/** base_url fallback */
if (!isset($base_url)) {
    $base_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
    // If still empty, try to get from settings
    if (empty($base_url) && !empty($GLOBALS['container'])) {
        try {
            $settings = $GLOBALS['container']->get('settings');
            $base_url = $settings['app']['base_path'] ?? '';
        } catch (\Throwable $e) {
            $base_url = '';
        }
    }
}

/** @var array{count:int,subtotal:float,items:array<int,array<string,mixed>>> $minicart */
$minicart = $minicart ?? ['count' => 0, 'subtotal' => 0.0, 'items' => []];

/** escape helper (fallback) */
if (!isset($escape) || !is_callable($escape)) {
    $escape = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
}
?>

<div class="minicart-overlay" id="minicart-overlay" aria-hidden="true"></div>

<div class="minicart-panel" id="minicart-panel" role="dialog" aria-label="Mini cart" aria-hidden="true">
    <div class="minicart-header d-flex justify-content-between align-items-center px-3 py-2">
        <strong>Cart (<span id="minicart-count"><?= (int)$minicart['count'] ?></span>)</strong>
        <button type="button" id="minicart-close" class="btn btn-link" aria-label="Close mini cart">&times;</button>
    </div>

    <div class="minicart-body p-2">
        <?php if (empty($minicart['items'])): ?>
            <div class="minicart-empty text-center py-4">
                <p>Your cart is empty.</p>
                <a href="<?= $escape($base_url) . '/books' ?>" class="btn btn-dark">Shop now</a>
            </div>
        <?php else: ?>
            <ul class="list-unstyled mb-0">
                <?php foreach ($minicart['items'] as $item): ?>
                    <li class="minicart-item d-flex align-items-center gap-2 py-2 border-bottom">
                        <div class="minicart-thumb" style="width:56px">
                            <img src="<?= $escape($item['book_image'] ?? '') ?>" alt="<?= $escape($item['book_title'] ?? '') ?>" class="img-fluid">
                        </div>
                        <div class="minicart-meta flex-fill">
                            <div class="minicart-title small mb-1"><?= $escape($item['book_title'] ?? '') ?></div>
                            <div class="minicart-qty small text-muted">Qty: <?= (int)($item['quantity'] ?? 0) ?> &nbsp; • &nbsp; $<?= number_format((float)($item['line_total'] ?? ($item['quantity'] * $item['book_price'])), 2) ?></div>
                        </div>
                        <div class="minicart-actions text-end">
                            <a href="<?= $escape($base_url) . '/cart' ?>" class="btn btn-sm btn-outline-dark">View</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="minicart-footer p-3 border-top">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">Subtotal</div>
            <div class="fw-bold">$<span id="minicart-subtotal"><?= number_format((float)$minicart['subtotal'], 2) ?></span></div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $escape($base_url) . '/cart' ?>" class="btn btn-dark w-50">View Cart</a>
            <a href="<?= $escape($base_url) . '/order/place' ?>" class="btn btn-primary w-50">Checkout</a>
        </div>
    </div>
</div>

<!-- small inline styles to ensure correct overlay position under the icon -->
<style>
/* minicart styles (kept small so you can move to your CSS file) */
.minicart-panel {
    position: absolute;
    min-width: 320px;
    max-width: 420px;
    right: 8px;
    top: calc(100% + 8px); /* will be positioned relative to parent container */
    z-index: 1200;
    background: #fff;
    border: 1px solid #e6e6e6;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-radius: 6px;
    display: none;
}
.minicart-panel[aria-hidden="false"] { display: block; }
.minicart-overlay {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: none;
    background: rgba(0,0,0,0.15);
}
.minicart-overlay[aria-hidden="false"] { display:block; }
@media (max-width: 768px) {
    .minicart-panel {
        right: 10px;
        left: 10px;
        top: auto;
        bottom: 10px;
    }
}
</style>
