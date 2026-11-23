<div class="container my-5 text-center narrow">
    <p class="display-4 fw-bold text-secondary mb-0"><?= (int) $code ?></p>
    <h1 class="h3"><?= e($title) ?></h1>
    <p class="text-secondary"><?= e($message) ?></p>
    <a class="btn btn-dark" href="<?= e(url(!empty($admin) ? '/admin' : '/')) ?>">Go to the home page</a>
</div>
