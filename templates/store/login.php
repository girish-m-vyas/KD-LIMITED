<div class="container my-5 narrow-sm">
    <div class="auth-card">
        <h1 class="h4 mb-1">Log in</h1>
        <p class="text-secondary small">New here? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
        <form method="post" action="<?= e(url('/login')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= old('email') ?>" autocomplete="username" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control<?= invalid($errors, 'email') ?>" type="password" id="password" name="password" autocomplete="current-password" required>
                <?= field_error($errors, 'email') ?>
            </div>
            <button class="btn btn-dark w-100" type="submit">Log in</button>
        </form>
    </div>
</div>
