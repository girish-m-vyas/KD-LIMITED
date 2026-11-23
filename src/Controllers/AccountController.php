<?php
declare(strict_types=1);

namespace KD\Controllers;

use KD\App;
use KD\Http\HttpException;
use KD\Http\Request;
use KD\Http\Response;
use KD\Services\OrderService;
use KD\Support\Money;
use KD\Support\Validator;

/**
 * Customer account pages. Every query is scoped to the logged-in customer's
 * ID from the session; IDs in the URL are only ever used together with it.
 */
final class AccountController extends Controller
{
    private function customerId(): int
    {
        return (int) App::customerAuth()->id();
    }

    public function index(): string
    {
        Response::redirect('/account/orders');
    }

    public function orders(): string
    {
        return $this->view('account/orders', [
            'pageTitle' => 'My orders',
            'section'   => 'orders',
            'orders'    => App::orders()->forCustomer($this->customerId()),
        ]);
    }

    public function order(array $params): string
    {
        $order = App::orders()->findForCustomer($this->customerId(), $params['id']);
        if ($order === null) {
            throw HttpException::notFound();
        }
        return $this->view('account/order', [
            'pageTitle' => 'Order ' . $order['invoice_no'],
            'section'   => 'orders',
            'order'     => $order,
            'items'     => App::orders()->items((int) $order['id']),
            'payments'  => $this->db()->fetchAll('SELECT * FROM payments WHERE order_id = ? ORDER BY id', [$order['id']]),
            'modes'     => OrderService::PAYMENT_MODES,
        ]);
    }

    public function pay(array $params): string
    {
        $back = '/account/orders/' . $params['id'];
        $old  = Request::only(['amount', 'mode', 'ref_no', 'code', 'payment_date']);
        $errors = Validator::validate($_POST, [
            'amount'       => 'required|money',
            'mode'         => 'required|in:' . implode(',', OrderService::PAYMENT_MODES),
            'ref_no'       => 'required|text|max:60',
            'code'         => 'text|max:60',
            'payment_date' => 'required|date',
        ]);
        if (!isset($errors['payment_date']) && $old['payment_date'] > date('Y-m-d')) {
            $errors['payment_date'] = 'Payment date cannot be in the future.';
        }
        if ($errors) {
            Response::back($back, $errors, $old);
        }
        try {
            App::orders()->submitPayment($this->customerId(), $params['id'], [
                'amount_paise' => (int) Money::parse($old['amount']),
                'mode'         => $old['mode'],
                'ref_no'       => $old['ref_no'],
                'code'         => $old['code'],
                'payment_date' => $old['payment_date'],
            ]);
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'Order not found.') {
                throw HttpException::notFound();
            }
            Response::back($back, ['amount' => $e->getMessage()], $old);
        }
        Response::redirect($back, 'success', 'Payment details submitted. We will confirm once we have checked them.');
    }

    public function editForm(): string
    {
        return $this->view('account/edit', [
            'pageTitle' => 'Edit account',
            'section'   => 'edit',
            'me'        => App::customerAuth()->current(),
        ]);
    }

    public function edit(): string
    {
        $old = Request::only(['name', 'email', 'contact', 'city', 'address']);
        $errors = Validator::validate($_POST, [
            'name'    => 'required|text|max:120',
            'email'   => 'required|email|max:190',
            'contact' => 'required|phone',
            'city'    => 'required|text|max:120',
            'address' => 'required|max:500',
        ]);
        if ($errors) {
            Response::back('/account/edit', $errors, $old);
        }
        $me       = App::customerAuth()->current();
        $newImage = $this->optionalImage('image', 'customers', '/account/edit', $old);
        try {
            App::customers()->update($this->customerId(), $old, $newImage);
        } catch (\DomainException $e) {
            if ($newImage) {
                App::uploads()->delete('customers', $newImage);
            }
            Response::back('/account/edit', ['email' => $e->getMessage()], $old);
        }
        if ($newImage && !empty($me['image'])) {
            App::uploads()->delete('customers', (string) $me['image']);
        }
        Response::redirect('/account/edit', 'success', 'Your details have been saved.');
    }

    public function passwordForm(): string
    {
        return $this->view('account/password', ['pageTitle' => 'Change password', 'section' => 'password']);
    }

    public function password(): string
    {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!hash_equals($new, (string) ($_POST['new_password_confirm'] ?? ''))) {
            Response::back('/account/password', ['new_password_confirm' => 'The new passwords do not match.']);
        }
        $error = App::customers()->changePassword($this->customerId(), (string) ($_POST['current_password'] ?? ''), $new);
        if ($error !== null) {
            $field = str_contains($error, 'current') ? 'current_password' : 'new_password';
            Response::back('/account/password', [$field => $error]);
        }
        App::customerAuth()->login($this->customerId()); // new session ID after a credential change
        Response::redirect('/account/password', 'success', 'Your password has been changed.');
    }

    public function deleteForm(): string
    {
        return $this->view('account/delete', ['pageTitle' => 'Delete account', 'section' => 'delete']);
    }

    public function delete(): string
    {
        if (!App::customers()->verifyPassword($this->customerId(), (string) ($_POST['password'] ?? ''))) {
            Response::back('/account/delete', ['password' => 'Your password is incorrect.']);
        }
        $image = App::customers()->delete($this->customerId());
        if ($image) {
            App::uploads()->delete('customers', $image);
        }
        App::customerAuth()->logout();
        Response::redirect('/', 'success', 'Your account has been deleted.');
    }
}
