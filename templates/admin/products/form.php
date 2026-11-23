<?php
use KD\Support\Money;
$p = $product ?? [];
$val = fn (string $field, string $fallback = '') => old($field, $fallback);
$action = $product ? '/admin/products/' . (int) $product['id'] : '/admin/products';
?>
<a class="small" href="<?= e(url('/admin/products')) ?>"><i class="bi bi-chevron-left"></i> Products</a>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="admin-card p-3 p-lg-4 mt-2 narrow-lg" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="title">Title</label>
            <input class="form-control<?= invalid($errors, 'title') ?>" id="title" name="title" maxlength="200" value="<?= $val('title', $p['title'] ?? '') ?>" required>
            <?= field_error($errors, 'title') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="product_category_id">Product type</label>
            <select class="form-select<?= invalid($errors, 'product_category_id') ?>" id="product_category_id" name="product_category_id" required>
                <option value="">Choose…</option>
                <?php foreach ($pcats as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string) ($_SESSION['_old']['product_category_id'] ?? $p['product_category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'product_category_id') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="category_id">Category</label>
            <select class="form-select<?= invalid($errors, 'category_id') ?>" id="category_id" name="category_id" required>
                <option value="">Choose…</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string) ($_SESSION['_old']['category_id'] ?? $p['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'category_id') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="price">Price (₹)</label>
            <input class="form-control<?= invalid($errors, 'price') ?>" id="price" name="price" inputmode="decimal" value="<?= $val('price', Money::toInput(isset($p['price_paise']) ? (int) $p['price_paise'] : null)) ?>" required>
            <?= field_error($errors, 'price') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="label">Label</label>
            <select class="form-select<?= invalid($errors, 'label') ?>" id="label" name="label">
                <?php foreach ($labels as $code => $text): ?>
                    <option value="<?= e($code) ?>" <?= ($_SESSION['_old']['label'] ?? $p['label'] ?? '') === $code ? 'selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'label') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="sale_price">Sale price (₹)</label>
            <input class="form-control<?= invalid($errors, 'sale_price') ?>" id="sale_price" name="sale_price" inputmode="decimal" value="<?= $val('sale_price', Money::toInput(isset($p['sale_price_paise']) ? (int) $p['sale_price_paise'] : null)) ?>">
            <div class="form-text">Used when the label is Sale.</div>
            <?= field_error($errors, 'sale_price') ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="keywords">Search keywords</label>
            <input class="form-control<?= invalid($errors, 'keywords') ?>" id="keywords" name="keywords" maxlength="255" value="<?= $val('keywords', $p['keywords'] ?? '') ?>">
            <?= field_error($errors, 'keywords') ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control<?= invalid($errors, 'description') ?>" id="description" name="description" rows="5" maxlength="5000"><?= $val('description', $p['description'] ?? '') ?></textarea>
            <div class="form-text">Plain text. Line breaks are kept; HTML is shown as text.</div>
            <?= field_error($errors, 'description') ?>
        </div>
        <?php foreach (['image1' => 'Main image', 'image2' => 'Image 2', 'image3' => 'Image 3'] as $field => $label): ?>
            <div class="col-md-4">
                <label class="form-label" for="<?= $field ?>"><?= e($label) ?><?= $field === 'image1' && !$product ? '' : ' <span class="text-secondary">(optional)</span>' ?></label>
                <?php if (!empty($p[$field])): ?>
                    <div class="mb-2"><img class="admin-thumb" src="<?= e(media('products', $p[$field])) ?>" alt=""></div>
                <?php endif; ?>
                <input class="form-control form-control-sm<?= invalid($errors, $field) ?>" type="file" id="<?= $field ?>" name="<?= $field ?>" accept="image/jpeg,image/png,image/webp">
                <?= field_error($errors, $field) ?>
                <?php if ($field !== 'image1' && !empty($p[$field])): ?>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" value="1" id="remove_<?= $field ?>" name="remove_<?= $field ?>">
                        <label class="form-check-label small" for="remove_<?= $field ?>">Remove this image</label>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="form-text mt-2">JPEG, PNG or WebP, up to 2 MB each. Images are checked and re-saved on upload.</div>
    <button class="btn btn-dark mt-3" type="submit"><?= $product ? 'Save product' : 'Add product' ?></button>
</form>
