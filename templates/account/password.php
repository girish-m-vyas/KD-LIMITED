<?php use KD\Support\Password; use KD\Support\View; ?>
<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-3"><?= View::partial('account/_nav', ['section' => $section, 'customer' => $customer]) ?></div>
        <div class="col-lg-9">
            <h1 class="h3 mb-3">Change password</h1>
            <form method="post" action="<?= e(url('/account/password')) ?>" class="auth-card narrow" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input class="form-control<?= invalid($errors, 'current_password') ?>" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    <?= field_error($errors, 'current_password') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New password</label>
                    <input class="form-control<?= invalid($errors, 'new_password') ?>" type="password" id="new_password" name="new_password" minlength="<?= Password::MIN_LENGTH ?>" autocomplete="new-password" required>
                    <div class="form-text">At least <?= Password::MIN_LENGTH ?> characters, with letters and a number.</div>
                    <?= field_error($errors, 'new_password') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password_confirm">Confirm new password</label>
                    <input class="form-control<?= invalid($errors, 'new_password_confirm') ?>" type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" required>
                    <?= field_error($errors, 'new_password_confirm') ?>
                </div>
                <button class="btn btn-dark" type="submit">Change password</button>
            </form>
        </div>
    </div>
</div>
