<div class="container my-4 narrow-lg">
    <h1 class="h3 mb-4">Terms and conditions</h1>
    <?php if (!$terms): ?>
        <p class="text-secondary">No terms have been published yet.</p>
    <?php else: ?>
        <nav class="mb-4 d-flex flex-wrap gap-2">
            <?php foreach ($terms as $t): ?>
                <a class="btn btn-outline-secondary btn-sm" href="#<?= e($t['slug']) ?>"><?= e($t['title']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php foreach ($terms as $t): ?>
            <section id="<?= e($t['slug']) ?>" class="mb-4">
                <h2 class="h5"><?= e($t['title']) ?></h2>
                <div class="text-secondary"><?= nl2br(e($t['description'])) ?></div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
