<?php
/** @var string $content */
use KD\Http\Request;
$current = Request::path();
$navActive = fn (string $p) => ($p === '/' ? $current === '/' : str_starts_with($current, $p)) ? ' active" aria-current="page' : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? '') !== '' ? $pageTitle . ' · ' . $appName : $appName) ?></title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="d-flex flex-column min-vh-100">
<a class="visually-hidden-focusable" href="#main">Skip to content</a>

<div class="topbar small">
    <div class="container d-flex justify-content-between py-1">
        <span><i class="bi bi-truck"></i> Free delivery on orders over ₹1,999</span>
        <span class="d-none d-sm-inline">
            <?php if ($customer): ?>
                Signed in as <?= e($customer['name']) ?>
            <?php else: ?>
                <a href="<?= e(url('/login')) ?>">Log in</a> · <a href="<?= e(url('/register')) ?>">Register</a>
            <?php endif; ?>
        </span>
    </div>
</div>

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand brand" href="<?= e(url('/')) ?>">
            <span class="brand-mark">KD</span> <span class="brand-word">Limited</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= $navActive('/') ?>" href="<?= e(url('/')) ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link<?= $navActive('/shop') ?>" href="<?= e(url('/shop')) ?>">Shop</a></li>
                <li class="nav-item"><a class="nav-link<?= $navActive('/account') ?>" href="<?= e(url('/account')) ?>">My account</a></li>
                <li class="nav-item"><a class="nav-link<?= $navActive('/contact') ?>" href="<?= e(url('/contact')) ?>">Contact</a></li>
            </ul>
            <form class="d-flex me-lg-3 my-2 my-lg-0" role="search" method="get" action="<?= e(url('/shop')) ?>">
                <input class="form-control form-control-sm" type="search" name="q" placeholder="Search products" aria-label="Search products" maxlength="80" value="<?= e($filters['q'] ?? '') ?>">
            </form>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-dark btn-sm" href="<?= e(url('/cart')) ?>">
                    <i class="bi bi-bag"></i> Cart <span class="badge text-bg-light ms-1"><?= (int) $cartCount ?></span>
                </a>
                <?php if ($customer): ?>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Log out</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/login')) ?>">Log in</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main id="main" class="flex-grow-1">
    <?php if (!empty($flash)): ?>
        <div class="container mt-3">
            <?php foreach ($flash as $f): ?>
                <div class="alert alert-<?= e(in_array($f['type'], ['success', 'danger', 'warning', 'info'], true) ? $f['type'] : 'info') ?> alert-dismissible fade show" role="alert">
                    <?= e($f['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer class="site-footer mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="brand mb-2"><span class="brand-mark">KD</span> <span class="brand-word">Limited</span></div>
                <p class="text-secondary small mb-0">Everyday clothing for men, women and kids, delivered across India.</p>
            </div>
            <div class="col-6 col-md-2">
                <h6>Shop</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('/shop')) ?>">All products</a></li>
                    <li><a href="<?= e(url('/cart')) ?>">Cart</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-2">
                <h6>Account</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('/account/orders')) ?>">My orders</a></li>
                    <li><a href="<?= e(url('/register')) ?>">Register</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Help</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('/contact')) ?>">Contact us</a></li>
                    <li><a href="<?= e(url('/terms')) ?>">Terms, refunds and promotions</a></li>
                </ul>
            </div>
        </div>
        <p class="small text-secondary mt-4 mb-0">© <?= date('Y') ?> KD Limited</p>
    </div>
</footer>

<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
