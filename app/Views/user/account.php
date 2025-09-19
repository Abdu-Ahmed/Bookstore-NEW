<?php
declare(strict_types=1);

/**
 * Main account template that includes login & register partials.
 *
 * Expects:
 *  - $errors: array
 *  - $data: array (contains base_url etc)
 *
 * Use $escape($value) to escape values.
 */
$base = $data['base_url'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../layout/head.php'; ?>
</head>
<body>
    <?php require __DIR__ . '/../layout/nav.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12 text-center padding-medium no-padding-bottom">
                <h1>Account</h1>
                <div class="breadcrumbs">
                    <span class="item">
                        <a href="<?= $escape($base) ?>">Home &gt;</a>
                    </span>
                    <span class="item">Account</span>
                </div>
            </div>
        </div>
    </div>

    <section class="login-tabs padding-xlarge">
        <div class="container">
            <div class="row">
                <div class="tabs-listing">
                    <nav>
                        <div class="nav nav-tabs d-flex justify-content-center" id="nav-tab" role="tablist">
                            <button class="nav-link fw-light text-uppercase active" id="nav-sign-in-tab" data-bs-toggle="tab" data-bs-target="#nav-sign-in" type="button" role="tab" aria-controls="nav-sign-in" aria-selected="true">Sign-in</button>
                            <button class="nav-link fw-light text-uppercase" id="nav-register-tab" data-bs-toggle="tab" data-bs-target="#nav-register" type="button" role="tab" aria-controls="nav-register" aria-selected="false">Register</button>
                        </div>
                    </nav>

                    <div class="bg-gray tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-sign-in" role="tabpanel" aria-labelledby="nav-sign-in-tab">
                            <?php require __DIR__ . '/partials/Login.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="nav-register" role="tabpanel" aria-labelledby="nav-register-tab">
                            <?php require __DIR__ . '/partials/Register.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <?php require __DIR__ . '/../layout/footer.php'; ?>
    </footer>

    <script src="<?= $escape($base) . '/assets/js/jquery-1.11.0.min.js' ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= $escape($base) . '/assets/js/plugins.js' ?>"></script>
    <script type="text/javascript" src="<?= $escape($base) . '/assets/js/script.js' ?>"></script>
</body>
</html>
