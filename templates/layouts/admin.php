<?php
use KD\App;
use KD\Auth\Rbac;
use KD\Http\Request;
$path = Request::path();
$auth = App::adminAuth();
$nav = [
    'Overview' => [
        ['/admin', 'bi-speedometer2', 'Dashboard', 'dashboard.view'],
    ],
    'Catalogue' => [
        ['/admin/products', 'bi-box-seam', 'Products', 'catalog.manage'],
        ['/admin/product-categories', 'bi-diagram-3', 'Product types', 'catalog.manage'],
        ['/admin/categories', 'bi-tags', 'Categories', 'catalog.manage'],
    ],
    'Site content' => [
        ['/admin/slides', 'bi-images', 'Slides', 'content.manage'],
        ['/admin/boxes', 'bi-grid-3x2-gap', 'Home boxes', 'content.manage'],
        ['/admin/terms', 'bi-file-text', 'Terms', 'content.manage'],
    ],
    'Sales' => [
        ['/admin/orders', 'bi-receipt', 'Orders', 'orders.manage'],
        ['/admin/payments', 'bi-cash-coin', 'Payments', 'payments.view'],
        ['/admin/customers', 'bi-people', 'Customers', 'customers.manage'],
        ['/admin/messages', 'bi-envelope', 'Messages', 'messages.view'],
    ],
    'Access' => [
        ['/admin/users', 'bi-shield-lock', 'Admin users', 'users.manage'],
        ['/admin/audit', 'bi-journal-text', 'Audit log', 'audit.view'],
    ],
];
$isActive = fn (string $href) => $href === '/admin' ? $path === '/admin' : str_starts_with($path, $href);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(($pageTitle ?? 'Admin') . ' · ' . $appName . ' admin') ?></title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="admin-body">
<div class="d-lg-flex">
    <aside class="admin-sidebar p-3">
        <div class="d-flex justify-content-between align-items-center">
            <a class="brand text-decoration-none mb-2" href="<?= e(url('/admin')) ?>"><span class="brand-mark">KD</span> <span class="brand-word">Admin</span></a>
            <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-expanded="false" aria-controls="adminNav"><i class="bi bi-list"></i></button>
        </div>
        <nav id="adminNav" class="collapse d-lg-block">
            <?php foreach ($nav as $section => $links): ?>
                <?php $visible = array_filter($links, fn ($l) => $auth->can($l[3])); ?>
                <?php if (!$visible) continue; ?>
                <div class="nav-section"><?= e($section) ?></div>
                <ul class="nav flex-column">
                    <?php foreach ($visible as [$href, $icon, $label]): ?>
                        <li class="nav-item"><a class="nav-link<?= $isActive($href) ? ' active' : '' ?>" href="<?= e(url($href)) ?>"><i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
            <div class="nav-section">Shop</div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><i class="bi bi-shop me-2"></i>View shop</a></li>
            </ul>
        </nav>
    </aside>

    <div class="admin-main flex-grow-1">
        <header class="admin-topbar px-3 px-lg-4 py-2 d-flex justify-content-between align-items-center">
            <h1 class="h5 mb-0"><?= e($pageTitle ?? '') ?></h1>
            <div class="d-flex align-items-center gap-2">
                <a class="text-decoration-none text-reset d-flex align-items-center gap-2" href="<?= e(url('/admin/profile')) ?>">
                    <img class="avatar" src="<?= e(media('admins', $admin['image'] ?? '')) ?>" alt="">
                    <span class="d-none d-sm-inline small">
                        <?= e($admin['name']) ?><br><span class="text-secondary"><?= e(Rbac::label((string) $admin['role'])) ?></span>
                    </span>
                </a>
                <form method="post" action="<?= e(url('/admin/logout')) ?>" class="m-0 ms-2">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline"> Log out</span></button>
                </form>
            </div>
        </header>
        <main class="p-3 p-lg-4">
            <?php foreach ($flash ?? [] as $f): ?>
                <div class="alert alert-<?= e(in_array($f['type'], ['success', 'danger', 'warning', 'info'], true) ? $f['type'] : 'info') ?> alert-dismissible fade show" role="alert">
                    <?= e($f['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
