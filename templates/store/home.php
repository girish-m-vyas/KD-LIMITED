<?php use KD\Support\View; ?>
<?php if ($slides): ?>
<div id="heroCarousel" class="carousel slide hero" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php foreach ($slides as $i => $s): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= (int) $i ?>" <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="<?= e($s['name']) ?>"></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach ($slides as $i => $s): ?>
            <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                <?php if ($s['url'] !== ''): ?><a href="<?= e($s['url']) ?>"><?php endif; ?>
                    <img src="<?= e(media('slides', $s['image'])) ?>" class="d-block w-100" alt="<?= e($s['name']) ?>">
                <?php if ($s['url'] !== ''): ?></a><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span>
    </button>
</div>
<?php endif; ?>

<?php if ($boxes): ?>
<section class="container my-5">
    <div class="row g-4">
        <?php foreach ($boxes as $b): ?>
            <div class="col-md-4">
                <div class="feature-box h-100">
                    <i class="bi bi-stars"></i>
                    <h2 class="h5 mt-2"><?= e($b['title']) ?></h2>
                    <p class="text-secondary mb-0"><?= e($b['description']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="container my-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <h2 class="section-title mb-0">Latest products</h2>
        <a href="<?= e(url('/shop')) ?>" class="small">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php if (!$products): ?>
        <p class="text-secondary">No products yet.</p>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <?php foreach ($products as $p): ?>
                <div class="col"><?= View::partial('partials/product-card', ['p' => $p]) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
