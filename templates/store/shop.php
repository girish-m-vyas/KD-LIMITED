<?php use KD\Support\View; ?>
<div class="container my-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Shop</li>
    </ol></nav>

    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="filter-panel">
                <h2 class="h6 text-uppercase text-secondary">Product type</h2>
                <ul class="list-unstyled filter-list">
                    <li><a class="<?= $filters['pcat'] === null ? 'active' : '' ?>" href="<?= e(url('/shop', ['cat' => $filters['cat'], 'q' => $filters['q']])) ?>">All</a></li>
                    <?php foreach ($pcats as $pc): ?>
                        <li><a class="<?= $filters['pcat'] === (int) $pc['id'] ? 'active' : '' ?>" href="<?= e(url('/shop', ['pcat' => (int) $pc['id'], 'cat' => $filters['cat'], 'q' => $filters['q']])) ?>"><?= e($pc['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <h2 class="h6 text-uppercase text-secondary mt-4">Category</h2>
                <ul class="list-unstyled filter-list">
                    <li><a class="<?= $filters['cat'] === null ? 'active' : '' ?>" href="<?= e(url('/shop', ['pcat' => $filters['pcat'], 'q' => $filters['q']])) ?>">All</a></li>
                    <?php foreach ($cats as $c): ?>
                        <li><a class="<?= $filters['cat'] === (int) $c['id'] ? 'active' : '' ?>" href="<?= e(url('/shop', ['cat' => (int) $c['id'], 'pcat' => $filters['pcat'], 'q' => $filters['q']])) ?>"><?= e($c['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <h1 class="h3 mb-0">Shop</h1>
                <span class="text-secondary small">
                    <?= (int) $total ?> product<?= $total === 1 ? '' : 's' ?>
                    <?php if ($filters['q'] !== ''): ?> matching “<?= e($filters['q']) ?>”<?php endif; ?>
                </span>
            </div>

            <?php if (!$products): ?>
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <p class="mb-2">No products match these filters.</p>
                    <a href="<?= e(url('/shop')) ?>" class="btn btn-outline-dark btn-sm">Clear filters</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 g-3 g-md-4">
                    <?php foreach ($products as $p): ?>
                        <div class="col"><?= View::partial('partials/product-card', ['p' => $p]) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($pages > 1): ?>
                <nav class="mt-4" aria-label="Pages">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                                <a class="page-link" href="<?= e(url('/shop', ['pcat' => $filters['pcat'], 'cat' => $filters['cat'], 'q' => $filters['q'], 'page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
