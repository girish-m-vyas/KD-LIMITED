<?php
use KD\Services\Cart;
use KD\Services\OrderService;
use KD\Support\View;
$images = array_values(array_filter([$product['image1'], $product['image2'], $product['image3']]));
$onSale = $product['label'] === 'sale' && $product['sale_price_paise'] !== null && (int) $product['sale_price_paise'] > 0;
?>
<div class="container my-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= e(url('/shop')) ?>">Shop</a></li>
        <li class="breadcrumb-item"><a href="<?= e(url('/shop', ['pcat' => (int) $product['product_category_id']])) ?>"><?= e($product['pcat_title']) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($product['title']) ?></li>
    </ol></nav>

    <div class="row g-4 g-lg-5">
        <div class="col-md-6">
            <div class="ratio ratio-4x5 product-main mb-2">
                <img id="mainImage" src="<?= e(media('products', $images[0] ?? '')) ?>" alt="<?= e($product['title']) ?>">
            </div>
            <?php if (count($images) > 1): ?>
                <div class="d-flex gap-2">
                    <?php foreach ($images as $img): ?>
                        <button type="button" class="thumb-btn" data-main-image="<?= e(media('products', $img)) ?>" aria-label="Show image">
                            <img src="<?= e(media('products', $img)) ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <p class="text-secondary small mb-1"><?= e($product['cat_title']) ?> · <?= e($product['pcat_title']) ?></p>
            <h1 class="h2"><?= e($product['title']) ?></h1>
            <p class="fs-4">
                <?php if ($onSale): ?>
                    <del class="text-secondary fs-6"><?= e(money($product['price_paise'])) ?></del>
                    <strong class="text-danger"><?= e(money(OrderService::unitPrice($product))) ?></strong>
                    <span class="badge text-bg-danger align-middle">Sale</span>
                <?php else: ?>
                    <strong><?= e(money($product['price_paise'])) ?></strong>
                <?php endif; ?>
            </p>

            <form method="post" action="<?= e(url('/cart/add')) ?>" class="buy-box">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="size">Size</label>
                        <select class="form-select<?= invalid($errors, 'size') ?>" id="size" name="size" required>
                            <option value="">Choose…</option>
                            <?php foreach ($sizes as $code => $label): ?>
                                <option value="<?= e($code) ?>" <?= ($_SESSION['_old']['size'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'size') ?>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="qty">Quantity</label>
                        <input class="form-control<?= invalid($errors, 'qty') ?>" type="number" id="qty" name="qty" min="1" max="<?= Cart::MAX_QTY ?>" value="<?= old('qty', '1') ?>" required>
                        <?= field_error($errors, 'qty') ?>
                    </div>
                </div>
                <button class="btn btn-dark btn-lg w-100 mt-3" type="submit"><i class="bi bi-bag-plus"></i> Add to cart</button>
            </form>

            <?php if ($product['description'] !== ''): ?>
                <h2 class="h6 mt-4 text-uppercase text-secondary">Details</h2>
                <div class="product-desc"><?= nl2br(e($product['description'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($related): ?>
        <h2 class="section-title mt-5 mb-3">You may also like</h2>
        <div class="row row-cols-2 row-cols-md-3 g-3 g-md-4">
            <?php foreach ($related as $p): ?>
                <div class="col"><?= View::partial('partials/product-card', ['p' => $p]) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
