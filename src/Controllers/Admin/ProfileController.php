<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Auth\Rbac;
use KD\Controllers\Controller;
use KD\Http\Request;
use KD\Http\Response;
use KD\Support\Password;
use KD\Support\Session;
use KD\Support\Validator;

final class ProfileController extends Controller
{
    public function edit(): string
    {
        $me = App::adminAuth()->current();
        return $this->view('admin/profile', [
            'pageTitle' => 'My profile',
            'me'        => $me,
            'roleLabel' => Rbac::label((string) $me['role']),
        ], 'admin');
    }

    public function update(): string
    {
        $me     = App::adminAuth()->current();
        $old    = Request::only(['name', 'job', 'contact', 'about']);
        $errors = Validator::validate($old, [
            'name'    => 'required|text|max:120',
            'job'     => 'text|max:120',
            'contact' => 'phone',
            'about'   => 'max:1000',
        ]);
        if ($errors) {
            Response::back('/admin/profile', $errors, $old);
        }
        $image = $this->optionalImage('image', 'admins', '/admin/profile', $old);
        $sql    = 'UPDATE admins SET name = ?, job = ?, contact = ?, about = ?, updated_at = ?';
        $values = [$old['name'], $old['job'], $old['contact'], $old['about'], now()];
        if ($image !== null) {
            $sql     .= ', image = ?';
            $values[] = $image;
        }
        $values[] = $me['id'];
        $this->db()->execute($sql . ' WHERE id = ?', $values);
        if ($image !== null && $me['image'] !== '') {
            App::uploads()->delete('admins', (string) $me['image']);
        }
        $this->audit('profile.updated', 'admin', (int) $me['id']);
        Response::redirect('/admin/profile', 'success', 'Profile saved.');
    }

    public function password(): string
    {
        $me      = App::adminAuth()->current();
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $hash    = (string) $this->db()->value('SELECT password_hash FROM admins WHERE id = ?', [$me['id']]);

        if (!Password::verify($current, $hash)) {
            Response::back('/admin/profile', ['current_password' => 'Your current password is incorrect.']);
        }
        if ($err = Password::policyError($new, (string) $me['email'])) {
            Response::back('/admin/profile', ['new_password' => $err]);
        }
        if (Password::verify($new, $hash)) {
            Response::back('/admin/profile', ['new_password' => 'Choose a different password from your current one.']);
        }
        if (!hash_equals($new, (string) ($_POST['new_password_confirm'] ?? ''))) {
            Response::back('/admin/profile', ['new_password_confirm' => 'The new passwords do not match.']);
        }
        $this->db()->execute('UPDATE admins SET password_hash = ?, must_change_password = 0, updated_at = ? WHERE id = ?', [Password::hash($new), now(), $me['id']]);
        Session::regenerate();
        App::adminAuth()->refresh();
        $this->audit('profile.password_changed', 'admin', (int) $me['id']);
        Response::redirect('/admin', 'success', 'Your password has been changed.');
    }
}
