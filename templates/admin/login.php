<div class="text-center mb-4">
    <span class="brand fs-4"><span class="brand-mark">KD</span> <span class="brand-word">Admin</span></span>
</div>
<div class="auth-card">
    <h1 class="h5 mb-3">Sign in to the admin panel</h1>
    <form method="post" action="<?= e(url('/admin/login')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= old('email') ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control<?= invalid($errors, 'email') ?>" type="password" id="password" name="password" autocomplete="current-password" required>
            <?= field_error($errors, 'email') ?>
        </div>
        <button class="btn btn-dark w-100" type="submit">Sign in</button>
    </form>
</div>
<p class="text-center small text-secondary mt-3">Every sign-in and admin action is recorded in the audit log.</p>
