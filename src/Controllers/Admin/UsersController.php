<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Auth\Rbac;
use KD\Controllers\Controller;
use KD\Http\Request;
use KD\Http\Response;
use KD\Support\Password;
use KD\Support\Validator;

/**
 * Admin user lifecycle (joiner, mover, leaver). Accounts are deactivated, not
 * deleted, so the audit trail keeps pointing at real people.
 */
final class UsersController extends Controller
{
    public function index(): string
    {
        return $this->view('admin/users/index', [
            'pageTitle' => 'Admin users',
            'users'     => $this->db()->fetchAll('SELECT id, name, email, role, image, is_active, must_change_password, last_login_at, created_at FROM admins ORDER BY is_active DESC, name'),
        ], 'admin');
    }

    public function create(): string
    {
        return $this->view('admin/users/form', ['pageTitle' => 'Add admin user', 'user' => null, 'roles' => Rbac::roleOptions()], 'admin');
    }

    public function edit(array $params): string
    {
        return $this->view('admin/users/form', [
            'pageTitle' => 'Edit admin user',
            'user'      => $this->findOr404('admins', $params['id']),
            'roles'     => Rbac::roleOptions(),
        ], 'admin');
    }

    public function store(): string
    {
        $old    = Request::only(['name', 'email', 'role', 'job', 'contact']);
        $errors = $this->validate($old);
        if (!isset($errors['email']) && $this->db()->value('SELECT 1 FROM admins WHERE LOWER(email) = ?', [strtolower($old['email'])]) !== null) {
            $errors['email'] = 'An admin with that email already exists.';
        }
        if ($errors) {
            Response::back('/admin/users/new', $errors, $old);
        }
        $temp = self::temporaryPassword();
        $now  = now();
        $id   = $this->db()->insert(
            'INSERT INTO admins (name, email, password_hash, role, job, contact, about, is_active, must_change_password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?)',
            [$old['name'], strtolower($old['email']), Password::hash($temp), $old['role'], $old['job'], $old['contact'], '', $now, $now]
        );
        $this->audit('user.created', 'admin', $id, ['email' => strtolower($old['email']), 'role' => $old['role']]);
        Response::redirect('/admin/users', 'success', "Admin created. Temporary password (shown once, share it privately): {$temp} — they must change it at first login.");
    }

    public function update(array $params): string
    {
        $user   = $this->findOr404('admins', $params['id']);
        $back   = '/admin/users/' . $user['id'] . '/edit';
        $old    = Request::only(['name', 'email', 'role', 'job', 'contact']);
        $active = Request::input('is_active') === '1' ? 1 : 0;
        $errors = $this->validate($old);
        if (!isset($errors['email']) && $this->db()->value('SELECT 1 FROM admins WHERE LOWER(email) = ? AND id <> ?', [strtolower($old['email']), $user['id']]) !== null) {
            $errors['email'] = 'An admin with that email already exists.';
        }
        $self = (int) $user['id'] === $this->adminId();
        if ($self && ($old['role'] !== $user['role'] || $active === 0)) {
            $errors['role'] = "You can't change your own role or deactivate yourself. Ask another super admin.";
        }
        $losingSuper = $user['role'] === 'super_admin' && (int) $user['is_active'] === 1 && ($old['role'] !== 'super_admin' || $active === 0);
        if ($losingSuper && (int) $this->db()->value("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND is_active = 1") <= 1) {
            $errors['role'] = 'There must always be at least one active super admin.';
        }
        if ($errors) {
            Response::back($back, $errors, $old);
        }

        $this->db()->execute(
            'UPDATE admins SET name = ?, email = ?, role = ?, job = ?, contact = ?, is_active = ?, updated_at = ? WHERE id = ?',
            [$old['name'], strtolower($old['email']), $old['role'], $old['job'], $old['contact'], $active, now(), $user['id']]
        );

        $changes = [];
        foreach (['name', 'email', 'role', 'job', 'contact'] as $k) {
            if ((string) $user[$k] !== ($k === 'email' ? strtolower($old[$k]) : $old[$k])) {
                $changes[$k] = [$user[$k], $old[$k]];
            }
        }
        if ((int) $user['is_active'] !== $active) {
            $changes['is_active'] = [(int) $user['is_active'], $active];
        }
        $action = isset($changes['role']) ? 'user.role_changed' : (isset($changes['is_active']) ? ($active ? 'user.reactivated' : 'user.deactivated') : 'user.updated');
        $this->audit($action, 'admin', (int) $user['id'], $changes);
        Response::redirect('/admin/users', 'success', 'Admin user saved.');
    }

    public function resetPassword(array $params): string
    {
        $user = $this->findOr404('admins', $params['id']);
        if ((int) $user['id'] === $this->adminId()) {
            Response::redirect('/admin/profile', 'info', 'Change your own password from your profile.');
        }
        $temp = self::temporaryPassword();
        $this->db()->execute('UPDATE admins SET password_hash = ?, must_change_password = 1, updated_at = ? WHERE id = ?', [Password::hash($temp), now(), $user['id']]);
        $this->audit('user.password_reset', 'admin', (int) $user['id'], ['email' => $user['email']]);
        Response::redirect('/admin/users', 'success', "Password reset for {$user['email']}. Temporary password (shown once): {$temp}");
    }

    /** @return array<string, string> */
    private function validate(array $old): array
    {
        return Validator::validate($old, [
            'name'    => 'required|text|max:120',
            'email'   => 'required|email|max:190',
            'role'    => 'required|in:' . implode(',', array_keys(Rbac::ROLES)),
            'job'     => 'text|max:120',
            'contact' => 'phone',
        ]);
    }

    private static function temporaryPassword(): string
    {
        // e.g. "Kd-7f3a9c-41d2e8" — random, meets the password policy.
        return 'Kd-' . bin2hex(random_bytes(3)) . '-' . bin2hex(random_bytes(3)) . random_int(1, 9);
    }
}
