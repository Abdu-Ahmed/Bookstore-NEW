<?php
declare(strict_types=1);

/**
 * app/Views/layout/head.php
 *
 * Expected variables (optional):
 *  - string $base_url  (preferred)
 *  - string $title     (optional page title)
 *
 * Use $escape($value) to safely escape output.
 */

/** @var string $base_url */
$base = (string) ($base_url ?? (defined('BASE_URL') ? BASE_URL : ''));

/** @var string $title */
$title = (string) ($title ?? "Abdu's Bookstore");
?>
<meta charset="utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="format-detection" content="telephone=no"/>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="author" content=""/>
<meta name="keywords" content=""/>
<meta name="description" content=""/>
<title><?= $escape($title) ?></title>

<link rel="stylesheet" type="text/css" href="<?= $escape($base) . '/assets/css/vendor.css' ?>"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous"/>
<link rel="stylesheet" href="<?= $escape($base) . '/assets/css/styles.css' ?>"/>
<link rel="icon" type="image/x-icon" href="<?= $escape($base) . '/assets/images/logo2.png' ?>"/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="<?= $escape($base) . '/assets/js/modernizr.js' ?>" defer></script>
<script src="<?= $escape($base) . '/assets/js/minicart.js' ?>" defer></script>
<script src="<?= $escape($base) . '/assets/js/checkout.js' ?>" defer></script>
<script src="<?= $escape($base) . '/assets/js/offcanvas.js' ?>" defer></script>
