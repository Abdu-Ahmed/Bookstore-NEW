<?php
declare(strict_types=1);
/**
 * Partial: success
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
        <h1>Thank you — Payment successful!</h1>
        <?php if (!empty($session_id)): ?>
            <p>Your payment session id: <code><?= htmlspecialchars($session_id) ?></code></p>
        <?php endif; ?>
        <p><a href="<?= $base_url . '/books' ?>" class="btn btn-dark">Continue Shopping</a></p>
    </div>

    <?php require_once APP_ROOT . '/app/views/layout/footer.php'; ?>
</body>
</html>
