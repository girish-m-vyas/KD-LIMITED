<?php use KD\Support\Password; ?>
<div class="container my-5 narrow">
    <div class="auth-card">
        <h1 class="h4 mb-1">Create an account</h1>
        <p class="text-secondary small">Already registered? <a href="<?= e(url('/login')) ?>">Log in</a></p>
        <form method="post" action="<?= e(url('/register')) ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Full name</label>
                    <input class="form-control<?= invalid($errors, 'name') ?>" id="name" name="name" value="<?= old('name') ?>" maxlength="120" autocomplete="name" required>
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= old('email') ?>" maxlength="190" autocomplete="email" required>
                    <?= field_error($errors, 'email') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control<?= invalid($errors, 'password') ?>" type="password" id="password" name="password" minlength="<?= Password::MIN_LENGTH ?>" autocomplete="new-password" aria-describedby="pwHelp" required>
                    <div id="pwHelp" class="form-text">At least <?= Password::MIN_LENGTH ?> characters, with letters and a number.</div>
                    <?= field_error($errors, 'password') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirm">Confirm password</label>
                    <input class="form-control<?= invalid($errors, 'password_confirm') ?>" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                    <?= field_error($errors, 'password_confirm') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contact">Phone</label>
                    <input class="form-control<?= invalid($errors, 'contact') ?>" type="tel" id="contact" name="contact" value="<?= old('contact') ?>" maxlength="20" autocomplete="tel" required>
                    <?= field_error($errors, 'contact') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="city">City</label>
                    <input class="form-control<?= invalid($errors, 'city') ?>" id="city" name="city" value="<?= old('city') ?>" maxlength="120" autocomplete="address-level2" required>
                    <?= field_error($errors, 'city') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="address">Delivery address</label>
                    <textarea class="form-control<?= invalid($errors, 'address') ?>" id="address" name="address" rows="2" maxlength="500" autocomplete="street-address" required><?= old('address') ?></textarea>
                    <?= field_error($errors, 'address') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="image">Profile photo <span class="text-secondary">(optional)</span></label>
                    <input class="form-control<?= invalid($errors, 'image') ?>" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">JPEG, PNG or WebP, up to 2 MB.</div>
                    <?= field_error($errors, 'image') ?>
                </div>
            </div>
            <button class="btn btn-dark w-100 mt-4" type="submit">Create account</button>
        </form>
    </div>
</div>
