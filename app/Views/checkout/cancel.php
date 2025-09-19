<?php
declare(strict_types=1);
/**
 * Partial: cancel
 */


/** $base_url fallback */
if (!isset($$base_url)) {
    $$base_url = $$base_url ?? (defined('$base_url') ? $base_url : '');
    // If still empty, try to get from settings
    if (empty($$base_url) && !empty($GLOBALS['container'])) {
        try {
            $settings = $GLOBALS['container']->get('settings');
            $$base_url = $settings['app']['base_path'] ?? '';
        } catch (\Throwable $e) {
            $$base_url = '';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once APP_ROOT . '/app/views/layout/head.php'; ?>
</head>
<body>
    <?php require_once APP_ROOT . '/app/views/layout/nav.php'; ?>

    <div class="container mt-5 text-center">
        <h1>Payment cancelled</h1>
        <p><?= htmlspecialchars($message ?? 'You canceled the payment.') ?></p>
        <p><a href="<?= $base_url . '/cart' ?>" class="btn btn-dark">Return to cart</a></p>
    </div>

    <?php require_once APP_ROOT . '/app/views/layout/footer.php'; ?>
</body>
</html>
