<?php
$u = $user ?? [];
$action = $user ? '/admin/users/' . (int) $user['id'] : '/admin/users';
$self = $user && (int) $user['id'] === (int) $admin['id'];
?>
<a class="small" href="<?= e(url('/admin/users')) ?>"><i class="bi bi-chevron-left"></i> Admin users</a>
<form method="post" action="<?= e(url($action)) ?>" class="admin-card p-3 p-lg-4 mt-2 narrow" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Name</label>
            <input class="form-control<?= invalid($errors, 'name') ?>" id="name" name="name" maxlength="120" value="<?= old('name', $u['name'] ?? '') ?>" required>
            <?= field_error($errors, 'name') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" maxlength="190" value="<?= old('email', $u['email'] ?? '') ?>" required>
            <?= field_error($errors, 'email') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="role">Role</label>
            <select class="form-select<?= invalid($errors, 'role') ?>" id="role" name="role" <?= $self ? 'disabled' : '' ?>>
                <?php foreach ($roles as $code => $label): ?>
                    <option value="<?= e($code) ?>" <?= ($_SESSION['_old']['role'] ?? $u['role'] ?? 'catalog_manager') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($self): ?><input type="hidden" name="role" value="<?= e($u['role']) ?>"><div class="form-text">You can't change your own role.</div><?php endif; ?>
            <?= field_error($errors, 'role') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="job">Job title <span class="text-secondary">(optional)</span></label>
            <input class="form-control<?= invalid($errors, 'job') ?>" id="job" name="job" maxlength="120" value="<?= old('job', $u['job'] ?? '') ?>">
            <?= field_error($errors, 'job') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="contact">Phone <span class="text-secondary">(optional)</span></label>
            <input class="form-control<?= invalid($errors, 'contact') ?>" type="tel" id="contact" name="contact" maxlength="20" value="<?= old('contact', $u['contact'] ?? '') ?>">
            <?= field_error($errors, 'contact') ?>
        </div>
        <?php if ($user): ?>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <?php if ($self): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
                    <input class="form-check-input" type="checkbox" role="switch" value="1" id="is_active" name="is_active" <?= (int) $u['is_active'] === 1 ? 'checked' : '' ?> <?= $self ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="is_active">Account active</label>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!$user): ?>
        <p class="form-text mt-3 mb-0">A temporary password is generated and shown once after saving. The new admin must change it when they first log in.</p>
    <?php endif; ?>
    <button class="btn btn-dark mt-3" type="submit"><?= $user ? 'Save changes' : 'Create admin' ?></button>
</form>
