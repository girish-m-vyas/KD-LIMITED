<?php
/** @var array $p */
use KD\Services\OrderService;
$onSale = $p['label'] === 'sale' && $p['sale_price_paise'] !== null && (int) $p['sale_price_paise'] > 0;
?>
<div class="card product-card h-100">
    <a href="<?= e(url('/product/' . (int) $p['id'])) ?>" class="ratio ratio-4x5 product-thumb">
        <img src="<?= e(media('products', $p['image1'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
    </a>
    <?php if ($p['label'] === 'sale'): ?>
        <span class="product-label sale">Sale</span>
    <?php elseif ($p['label'] === 'new'): ?>
        <span class="product-label new">New</span>
    <?php endif; ?>
    <div class="card-body d-flex flex-column">
        <h3 class="h6 card-title mb-1">
            <a class="stretched-link text-reset text-decoration-none" href="<?= e(url('/product/' . (int) $p['id'])) ?>"><?= e($p['title']) ?></a>
        </h3>
        <div class="mt-auto price">
            <?php if ($onSale): ?>
                <del class="text-secondary small"><?= e(money($p['price_paise'])) ?></del>
                <strong class="text-danger"><?= e(money(OrderService::unitPrice($p))) ?></strong>
            <?php else: ?>
                <strong><?= e(money($p['price_paise'])) ?></strong>
            <?php endif; ?>
        </div>
    </div>
</div>
