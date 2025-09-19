<?php
declare(strict_types=1);

/**
 * app/Views/layout/footer.php
 *
 * Expected variables (optional):
 *  - string $base_url
 *  - bool   $isLoggedIn
 */

$base = (string) ($base_url ?? (defined('BASE_URL') ? BASE_URL : ''));
$isLoggedIn = (bool) ($isLoggedIn ?? isset($_SESSION['user_id']));
?>
<footer id="footer" class="overflow-hidden padding-xlarge pb-0">
    <div class="container">
        <div class="row">
            <div class="footer-top-area pb-5">
                <div class="row d-flex flex-wrap justify-content-between">
                    <div class="col-lg-3 col-sm-6 pb-3">
                        <div class="footer-menu">
                            <img src="<?= $escape($base) . '/assets/images/logo2.png' ?>" alt="logo" class="logo"/>
                        </div>
                    </div>

                    <div class="col-lg-2 col-sm-6 pb-3">
                        <div class="footer-menu">
                            <h4 class="widget-title pb-2">Quick Links</h4>
                            <ul class="menu-list list-unstyled">
                                <li class="menu-item text-uppercase pb-2"><a href="<?= $escape($base) . '/about' ?>">About</a></li>
                                <li class="menu-item text-uppercase pb-2"><a href="<?= $escape($base) . '/books' ?>">Shop</a></li>
                                <li class="menu-item text-uppercase pb-2"><a href="<?= $escape($base) . '/contact' ?>">Contact</a></li>
                                <li class="menu-item text-uppercase pb-2">
                                    <?php if ($isLoggedIn): ?>
                                        <a href="<?= $escape($base) . '/logout' ?>">Logout</a>
                                    <?php else: ?>
                                        <a href="<?= $escape($base) . '/login' ?>">Sign-in</a>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-3 col-sm-6 pb-3">
                        <div class="footer-menu contact-item">
                            <h4 class="widget-title pb-2">Contact info</h4>
                            <ul class="menu-list list-unstyled">
                                <li class="menu-item pb-2"><a href="#">Abdulrahman Ahmed, Cairo, EGYPT</a></li>
                                <li class="menu-item pb-2"><a href="#">abdulrahmanahmed.github.io</a></li>
                                <li class="menu-item pb-2"><a href="mailto:iamabduahmed@gmail.com">iamabduahmed@gmail.com</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-3 col-sm-6 pb-3">
                        <div class="footer-menu">
                            <h4 class="widget-title pb-2">Social info</h4>
                            <p>You can follow us on our social platforms to get updates.</p>
                            <div class="social-links">
                                <ul class="d-flex list-unstyled">
                                    <li><a href="#"><svg class="facebook"><use xlink:href="#facebook"></use></svg></a></li>
                                    <li><a href="#"><svg class="instagram"><use xlink:href="#instagram"></use></svg></a></li>
                                    <li><a href="#"><svg class="twitter"><use xlink:href="#twitter"></use></svg></a></li>
                                    <li><a href="#"><svg class="linkedin"><use xlink:href="#linkedin"></use></svg></a></li>
                                    <li><a href="#"><svg class="youtube"><use xlink:href="#youtube"></use></svg></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <hr/>
    </div>

<div id="footer-bottom">
    <div class="container">
        <div class="row d-flex flex-wrap justify-content-between">
            <div class="col-12">
                <div class="copyright footer text-center text-dark mt-4">
                    <p>All rights reserved &#169; Designed &amp; Created by Abdulrahman Ahmed 2024</p>
                </div>
            </div>
        </div>
    </div>
</div>

</footer>


