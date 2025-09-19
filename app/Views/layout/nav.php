<?php declare(strict_types=1); 

/** 
 * 
 * Expected variables (optional):
 * - array $categories
 * - string $keyword
 * - bool $isLoggedIn
 * - string $base_url
 * - array $minicart (contains count, subtotal, items)
 * 
 * Using $escape($value) to escape values.
 */

/** @var array<int,array<string,mixed>> $categories */
$categories = $categories ?? [];

/** @var string $keyword */
$keyword = (string) ($keyword ?? '');

/** @var bool $isLoggedIn */
$isLoggedIn = (bool) ($isLoggedIn ?? false);

/** @var string $base_url */
$base = (string) ($base_url ?? (defined('BASE_URL') ? BASE_URL : ''));

/** @var array $minicart */
// Load minicart data if not provided
if (!isset($minicart)) {
    // Include the minicart helper if it exists
    $minicartHelperPath = dirname(__DIR__, 2) . '/Support/minicart.php';
    if (file_exists($minicartHelperPath)) {
        require_once $minicartHelperPath;
        if (function_exists('get_minicart_data')) {
            $minicartData = get_minicart_data();
            $minicart = [
                'count' => $minicartData['count'] ?? 0,
                'subtotal' => $minicartData['total'] ?? 0.0,
                'items' => $minicartData['items'] ?? []
            ];
        } else {
            $minicart = ['count' => 0, 'subtotal' => 0.0, 'items' => []];
        }
    } else {
        $minicart = ['count' => 0, 'subtotal' => 0.0, 'items' => []];
    }
}
?>

<!-- SVG sprite used site-wide -->
<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="angle-right" viewBox="0 0 32 32"><path fill="currentColor" d="M12.969 4.281L11.53 5.72L21.812 16l-10.28 10.281l1.437 1.438l11-11l.687-.719l-.687-.719z"/></symbol>
    <symbol id="angle-left" viewBox="0 0 32 32"><path fill="currentColor" d="m19.031 4.281l-11 11l-.687.719l.687.719l11 11l1.438-1.438L10.187 16L20.47 5.719z"/></symbol>
    <symbol id="chevron-down" viewBox="0 0 24 24"><path fill="currentColor" d="M7.41 8.58L12 13.17l4.59-4.59L18 10l-6 6l-6-6l1.41-1.42Z"/></symbol>
    <symbol id="facebook" viewBox="0 0 24 24"><path fill="currentColor" d="M9.198 21.5h4v-8.01h3.604l.396-3.98h-4V7.5a1 1 0 0 1 1-1h3v-4h-3a5 5 0 0 0-5 5v2.01h-2l-.396 3.98h2.396v8.01Z"/></symbol>
    <symbol id="instagram" viewBox="0 0 256 256"><path fill="currentColor" d="..."/></symbol>
</svg>

<header id="header" class="site-header bg-white">
    <nav id="header-nav" class="navbar navbar-expand-lg px-3">
        <div class="container">
            <!-- LOGO -->
            <a class="navbar-brand" href="<?= $escape($base) . '/home' ?>">
                <img src="<?= $escape($base) . '/assets/images/logo2.png' ?>" alt="logo" class="logo"/>
            </a>

            <!-- DESKTOP NAVIGATION - Visible on large screens -->
            <div class="collapse navbar-collapse d-none d-lg-block" id="navbarNav">
                <ul class="navbar-nav w-100 d-flex justify-content-between align-items-center">
                    <li class="list-unstyled d-flex justify-content-md-between align-items-center">
                        <ul class="list-unstyled d-flex mb-0">
                            <li class="nav-item">
                                <a class="nav-link text-uppercase ms-0 shop" href="<?= $escape($base) . '/books' ?>">Shop</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link text-uppercase dropdown-toggle category" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                    Categories
                                    <svg class="bi" width="18" height="18"><use xlink:href="#chevron-down"></use></svg>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item text-dark link-danger" href="<?= $escape($base) . '/books' ?>">All Categories</a></li>
                                    <?php foreach ($categories as $category): ?>
                                        <?php $genre = (string) ($category['book_genre'] ?? ''); ?>
                                        <li>
                                            <a class="dropdown-item text-dark link-danger" href="<?= $escape($base) . '/category/' . rawurlencode($genre) ?>">
                                                <?= $escape($genre) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="list-unstyled d-flex justify-content-between align-items-center mb-0">
                        <li class="nav-item search-item search">
                            <div id="search-bar" class="glow-red rounded-2">
                                <form method="GET" action="<?= $escape($base) . '/books' ?>" class="position-relative d-flex justify-content-between align-items-center py-1">
                                    <input class="search-field link-danger" name="search" placeholder="Search" type="search" value="<?= $escape($keyword) ?>"/>
                                    <button type="submit" class="btn btn-link p-0 border-0" aria-label="Search">
                                        <i class="fas fa-search" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                        
                        <li class="nav-item account">
                            <?php if ($isLoggedIn): ?>
                                <a class="nav-link text-uppercase me-0" href="<?= $escape($base) . '/logout' ?>">Logout</a>
                            <?php else: ?>
                                <a class="nav-link text-uppercase me-0" href="<?= $escape($base) . '/login' ?>">Sign-in / Register</a>
                            <?php endif; ?>
                        </li>
                        
                        <!-- Cart Section with Minicart -->
                        <li class="nav-item cart position-relative">
                            <a class="nav-link text-uppercase me-0 position-relative" 
                               href="#" 
                               id="minicart-toggle" 
                               aria-label="Cart" 
                               role="button"
                               onclick="event.preventDefault();">
                                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                                <?php if ($minicart['count'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                          id="mini-cart-count">
                                        <?= (int)$minicart['count'] ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            
                            <!-- Include the minicart partial -->
                            <?php 
                            $minicartPath = __DIR__ . '/../cart/partials/minicart.php';
                            if (file_exists($minicartPath)) {
                                include $minicartPath;
                            }
                            ?>
                        </li>
                        
                        <?php if ($isLoggedIn): ?>
                            <li class="nav-item">
                                <a class="nav-link text-uppercase me-0 orders" href="<?= $escape($base) . '/orders' ?>">My Orders</a>
                            </li>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
            
            <!-- HAMBURGER BUTTON - Mobile only, custom animated -->
            <button class="navbar-toggler d-flex d-lg-none ms-auto p-2" 
                    type="button" 
                    data-bs-toggle="offcanvas" 
                    data-bs-target="#bdNavbar" 
                    aria-controls="bdNavbar" 
                    aria-expanded="false" 
                    aria-label="Toggle navigation">
                <!-- Empty - CSS creates the hamburger lines -->
            </button>
            
            <!-- OFFCANVAS MENU - Mobile sidebar -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="bdNavbar" aria-labelledby="bdNavbarOffcanvasLabel">
                <div class="offcanvas-header px-4 pb-0">
                    <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                
                <div class="offcanvas-body">
                    <ul id="navbar" class="navbar-nav w-100 d-flex justify-content-between align-items-center">
                        <li class="list-unstyled d-lg-flex justify-content-md-between align-items-center">
                            <ul class="list-unstyled d-lg-flex mb-0">
                                <li class="nav-item">
                                    <a class="nav-link text-uppercase ms-0 shop" href="<?= $escape($base) . '/books' ?>">Shop</a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link text-uppercase dropdown-toggle category" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                        Categories
                                        <svg class="bi" width="18" height="18"><use xlink:href="#chevron-down"></use></svg>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item text-dark link-danger" href="<?= $escape($base) . '/books' ?>">All Categories</a></li>
                                        <?php foreach ($categories as $category): ?>
                                            <?php $genre = (string) ($category['book_genre'] ?? ''); ?>
                                            <li>
                                                <a class="dropdown-item text-dark link-danger" href="<?= $escape($base) . '/category/' . rawurlencode($genre) ?>">
                                                    <?= $escape($genre) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="list-unstyled d-lg-flex justify-content-between align-items-center mb-0">
                            <li class="nav-item search-item search">
                                <div id="search-bar-mobile" class="glow-red rounded-2">
                                    <form method="GET" action="<?= $escape($base) . '/books' ?>" class="position-relative d-flex justify-content-between align-items-center py-1">
                                        <input class="search-field link-danger" name="search" placeholder="Search" type="search" value="<?= $escape($keyword) ?>"/>
                                        <button type="submit" class="btn btn-link p-0 border-0" aria-label="Search">
                                            <i class="fas fa-search" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                            
                            <li class="nav-item account">
                                <?php if ($isLoggedIn): ?>
                                    <a class="nav-link text-uppercase me-0" href="<?= $escape($base) . '/logout' ?>">Logout</a>
                                <?php else: ?>
                                    <a class="nav-link text-uppercase me-0" href="<?= $escape($base) . '/login' ?>">Sign-in / Register</a>
                                <?php endif; ?>
                            </li>
                            
                            <!-- Cart Section with Minicart -->
                            <li class="nav-item cart position-relative">
                                <a class="nav-link text-uppercase me-0 position-relative" 
                                   href="#" 
                                   id="minicart-toggle-mobile" 
                                   aria-label="Cart" 
                                   role="button"
                                   onclick="event.preventDefault();">
                                    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                                    <?php if ($minicart['count'] > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                              id="mini-cart-count-mobile">
                                            <?= (int)$minicart['count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Include the minicart partial -->
                                <?php 
                                $minicartPath = __DIR__ . '/../cart/partials/minicart.php';
                                if (file_exists($minicartPath)) {
                                    include $minicartPath;
                                }
                                ?>
                            </li>
                            
                            <?php if ($isLoggedIn): ?>
                                <li class="nav-item">
                                    <a class="nav-link text-uppercase me-0 orders" href="<?= $escape($base) . '/orders' ?>">My Orders</a>
                                </li>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- CSS for cart badge and icon fallback -->
<style>
.nav-item.cart .badge {
    font-size: 0.7rem;
    min-width: 1.2rem;
    height: 1.2rem;
    line-height: 1.2rem;
    padding: 0;
}

.nav-item.cart {
    position: relative;
}

/* Cart icon fallback - show if Font Awesome fails to load */
.nav-item.cart .fa-cart-shopping:empty + .cart-fallback-icon,
.nav-item.cart .fa-cart-shopping:not(:before) + .cart-fallback-icon {
    display: inline !important;
}

.nav-item.cart .cart-fallback-icon {
    font-size: 1.2rem;
    line-height: 1;
}

/* FALLBACK CSS for cart icon if Font Awesome isn't available */
.nav-item.cart .cart-icon-css {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid currentColor;
    border-radius: 2px;
    position: relative;
}

.nav-item.cart .cart-icon-css::before {
    content: '';
    position: absolute;
    top: -8px;
    left: 2px;
    width: 12px;
    height: 6px;
    border: 2px solid currentColor;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
}

/* Ensure minicart panel is positioned relative to cart icon */
.nav-item.cart .minicart-panel {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 8px;
}

@media (max-width: 768px) {
    .nav-item.cart .minicart-panel {
        position: fixed;
        right: 10px;
        left: 10px;
        top: auto;
        bottom: 10px;
    }
}

/* cart link is clickable and visible */
.nav-item.cart .nav-link {
    min-width: 40px;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.nav-item.cart .nav-link:hover {
    background-color: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.1);
}

/* Mobile search bar styling */
#search-bar-mobile .search-field {
    width: 100% !important;
    position: static !important;
    border-bottom: 1px solid var(--black-color) !important;
}

@media (min-width: 992px) {
    .offcanvas.offcanvas-end {
        display: none !important;
    }
}
.offcanvas-header .btn-close-custom {
    border: none;
    padding: 8px;
    position: relative;
    width: 40px;
    height: 40px;
    background: transparent !important;
    box-shadow: none !important;
    opacity: 1;
}

.offcanvas-header .btn-close-custom::before,
.offcanvas-header .btn-close-custom::after {
    content: '';
    position: absolute;
    width: 25px;
    height: 2px;
    background-color: var(--primary-color);
    left: 50%;
    transform: translateX(-50%);
    transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
}

.offcanvas-header .btn-close-custom::before {
    top: 20px;
    transform: translateX(-50%) rotate(45deg);
}

.offcanvas-header .btn-close-custom::after {
    top: 20px;
    opacity: 1;
    transform: translateX(-50%) rotate(-45deg);
}

.offcanvas-header .btn-close-custom:hover::before,
.offcanvas-header .btn-close-custom:hover::after {
    background-color: var(--black-color);
}
</style>