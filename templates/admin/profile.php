<?php use KD\Support\Password; ?>
<?php if ((int) $me['must_change_password'] === 1): ?>
    <div class="alert alert-warning">You're using a temporary password. Set your own password below to continue.</div>
<?php endif; ?>
<div class="row g-4">
    <div class="col-xl-7">
        <form method="post" action="<?= e(url('/admin/profile')) ?>" enctype="multipart/form-data" class="admin-card p-3 p-lg-4" novalidate>
            <?= csrf_field() ?>
            <h2 class="h6 mb-3">Profile</h2>
            <div class="d-flex align-items-center gap-3 mb-3">
                <img class="avatar" src="<?= e(media('admins', $me['image'])) ?>" alt="">
                <div class="small"><?= e($me['email']) ?><br><span class="text-secondary"><?= e($roleLabel) ?></span></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control<?= invalid($errors, 'name') ?>" id="name" name="name" maxlength="120" value="<?= old('name', $me['name']) ?>" required>
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="job">Job title</label>
                    <input class="form-control<?= invalid($errors, 'job') ?>" id="job" name="job" maxlength="120" value="<?= old('job', $me['job']) ?>">
                    <?= field_error($errors, 'job') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contact">Phone</label>
                    <input class="form-control<?= invalid($errors, 'contact') ?>" type="tel" id="contact" name="contact" maxlength="20" value="<?= old('contact', $me['contact']) ?>">
                    <?= field_error($errors, 'contact') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="image">Photo</label>
                    <input class="form-control<?= invalid($errors, 'image') ?>" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                    <?= field_error($errors, 'image') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="about">About</label>
                    <textarea class="form-control<?= invalid($errors, 'about') ?>" id="about" name="about" rows="3" maxlength="1000"><?= old('about', $me['about']) ?></textarea>
                    <?= field_error($errors, 'about') ?>
                </div>
            </div>
            <button class="btn btn-dark mt-3" type="submit">Save profile</button>
        </form>
    </div>
    <div class="col-xl-5">
        <form method="post" action="<?= e(url('/admin/profile/password')) ?>" class="admin-card p-3 p-lg-4" novalidate>
            <?= csrf_field() ?>
            <h2 class="h6 mb-3">Change password</h2>
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
