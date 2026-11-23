<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <form class="d-flex gap-2" method="get" action="<?= e(url('/admin/products')) ?>">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by title" aria-label="Search products">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
    </form>
    <a class="btn btn-dark btn-sm" href="<?= e(url('/admin/products/new')) ?>"><i class="bi bi-plus-lg"></i> Add product</a>
</div>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Title</th><th>Type / category</th><th class="text-end">Price</th><th>Label</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if (!$products): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No products found.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><img class="admin-thumb" src="<?= e(media('products', $p['image1'])) ?>" alt=""></td>
                    <td><?= e($p['title']) ?></td>
                    <td class="small text-secondary"><?= e($p['pcat_title']) ?> · <?= e($p['cat_title']) ?></td>
                    <td class="text-end">
                        <?php if ($p['sale_price_paise'] !== null): ?>
                            <del class="small text-secondary"><?= e(money($p['price_paise'])) ?></del><br><?= e(money($p['sale_price_paise'])) ?>
                        <?php else: ?>
                            <?= e(money($p['price_paise'])) ?>
                        <?php endif; ?>
                    </td>
                    <td><?php if ($p['label'] !== ''): ?><span class="badge text-bg-<?= $p['label'] === 'sale' ? 'danger' : 'dark' ?>"><?= e(ucfirst($p['label'])) ?></span><?php endif; ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/products/' . (int) $p['id'] . '/edit')) ?>">Edit</a>
                        <form class="d-inline" method="post" action="<?= e(url('/admin/products/' . (int) $p['id'] . '/delete')) ?>" data-confirm="Delete “<?= e($p['title']) ?>”?">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
