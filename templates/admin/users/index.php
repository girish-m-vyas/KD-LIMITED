<?php use KD\Auth\Rbac; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-secondary small mb-0">Admins are deactivated rather than deleted, so the audit log always points at a real person.</p>
    <a class="btn btn-dark btn-sm" href="<?= e(url('/admin/users/new')) ?>"><i class="bi bi-person-plus"></i> Add admin</a>
</div>
<div class="admin-card mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr<?= (int) $u['is_active'] === 1 ? '' : ' class="text-secondary"' ?>>
                    <td><img class="avatar" src="<?= e(media('admins', $u['image'])) ?>" alt=""></td>
                    <td><?= e($u['name']) ?><?= (int) $u['id'] === (int) $admin['id'] ? ' <span class="badge text-bg-light">you</span>' : '' ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge role-badge text-bg-<?= $u['role'] === 'super_admin' ? 'dark' : 'secondary' ?>"><?= e(Rbac::label($u['role'])) ?></span></td>
                    <td>
                        <?php if ((int) $u['is_active'] !== 1): ?>
                            <span class="badge text-bg-light">Deactivated</span>
                        <?php elseif ((int) $u['must_change_password'] === 1): ?>
                            <span class="badge text-bg-warning">Must set password</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= $u['last_login_at'] ? e(date('d M Y H:i', strtotime($u['last_login_at']))) : 'Never' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/users/' . (int) $u['id'] . '/edit')) ?>">Edit</a>
                        <?php if ((int) $u['id'] !== (int) $admin['id'] && (int) $u['is_active'] === 1): ?>
                            <form class="d-inline" method="post" action="<?= e(url('/admin/users/' . (int) $u['id'] . '/reset')) ?>" data-confirm="Reset the password for <?= e($u['email']) ?>? They will get a temporary password.">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-warning" type="submit">Reset password</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<h2 class="h6">What each role can do</h2>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-sm mb-0 text-center">
            <thead><tr><th class="text-start">Permission</th><?php foreach (Rbac::ROLES as $r): ?><th><?= e($r['label']) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach (Rbac::PERMISSIONS as $perm => $label): ?>
                <tr>
                    <td class="text-start small"><?= e($label) ?></td>
                    <?php foreach (array_keys(Rbac::ROLES) as $role): ?>
                        <td><?= Rbac::can($role, $perm) ? '<i class="bi bi-check-lg text-success" aria-label="Yes"></i>' : '<span class="text-secondary" aria-label="No">–</span>' ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
