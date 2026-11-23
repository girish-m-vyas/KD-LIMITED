<?php use KD\Support\View; ?>
<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-3"><?= View::partial('account/_nav', ['section' => $section, 'customer' => $customer]) ?></div>
        <div class="col-lg-9">
            <h1 class="h3 mb-3">Delete account</h1>
            <div class="auth-card narrow border-danger">
                <p>This removes your personal details and logs you out. It can't be undone.</p>
                <p class="text-secondary small">If you have placed orders, we keep the order and payment records for our accounts, but your name, email, phone and address are erased from them.</p>
                <form method="post" action="<?= e(url('/account/delete')) ?>" data-confirm="Delete your account permanently?" novalidate>
                    <?= csrf_field() ?>
                    <label class="form-label" for="password">Enter your password to confirm</label>
                    <input class="form-control<?= invalid($errors, 'password') ?>" type="password" id="password" name="password" autocomplete="current-password" required>
                    <?= field_error($errors, 'password') ?>
                    <button class="btn btn-danger mt-3" type="submit">Delete my account</button>
                </form>
            </div>
        </div>
    </div>
</div>
