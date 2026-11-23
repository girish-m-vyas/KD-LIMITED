<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Controllers\Controller;
use KD\Http\Response;

final class CustomersController extends Controller
{
    public function index(): string
    {
        return $this->view('admin/customers/index', [
            'pageTitle' => 'Customers',
            'customers' => $this->db()->fetchAll(
                'SELECT c.id, c.name, c.email, c.contact, c.city, c.image, c.created_at, COUNT(o.id) AS order_count
                 FROM customers c LEFT JOIN orders o ON o.customer_id = c.id
                 GROUP BY c.id, c.name, c.email, c.contact, c.city, c.image, c.created_at ORDER BY c.id DESC'
            ),
        ], 'admin');
    }

    public function delete(array $params): string
    {
        $customer = $this->findOr404('customers', $params['id']);
        $image = App::customers()->delete((int) $customer['id']);
        if ($image) {
            App::uploads()->delete('customers', $image);
        }
        $this->audit('customer.deleted', 'customer', (int) $customer['id'], ['email' => $customer['email']]);
        Response::redirect('/admin/customers', 'success', 'Customer removed. Customers with orders are anonymised so order records stay intact.');
    }

    public function messages(): string
    {
        return $this->view('admin/customers/messages', [
            'pageTitle' => 'Messages',
            'messages'  => $this->db()->fetchAll('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 200'),
        ], 'admin');
    }
}
