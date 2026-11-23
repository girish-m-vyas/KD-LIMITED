<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Controllers\Controller;
use KD\Http\HttpException;
use KD\Http\Request;
use KD\Http\Response;
use KD\Services\OrderService;

final class OrdersController extends Controller
{
    public function index(): string
    {
        $status = Request::query('status');
        $q      = mb_substr(Request::query('q'), 0, 40);
        $where  = [];
        $params = [];
        if (isset(OrderService::STATUS_LABELS[$status])) {
            $where[]  = 'o.status = ?';
            $params[] = $status;
        } else {
            $status = '';
        }
        if ($q !== '') {
            $where[]  = '(o.invoice_no = ? OR c.email = ?)';
            $params[] = strtoupper($q);
            $params[] = strtolower($q);
        }
        $orders = $this->db()->fetchAll(
            'SELECT o.*, c.name AS customer_name, c.email AS customer_email FROM orders o JOIN customers c ON c.id = o.customer_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.id DESC LIMIT 200',
            $params
        );
        return $this->view('admin/orders/index', ['pageTitle' => 'Orders', 'orders' => $orders, 'status' => $status, 'q' => $q], 'admin');
    }

    public function show(array $params): string
    {
        $order = $this->db()->fetch(
            'SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.contact AS customer_contact, c.address AS customer_address, c.city AS customer_city
             FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ?',
            [$params['id']]
        );
        if ($order === null) {
            throw HttpException::notFound();
        }
        return $this->view('admin/orders/show', [
            'pageTitle' => 'Order ' . $order['invoice_no'],
            'order'     => $order,
            'items'     => App::orders()->items((int) $order['id']),
            'payments'  => $this->db()->fetchAll('SELECT * FROM payments WHERE order_id = ? ORDER BY id', [$order['id']]),
            'next'      => OrderService::nextStatuses((string) $order['status']),
        ], 'admin');
    }

    public function status(array $params): string
    {
        $status = Request::input('status');
        try {
            $from = App::orders()->setStatus($params['id'], $status);
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'Order not found.') {
                throw HttpException::notFound();
            }
            Response::redirect('/admin/orders/' . $params['id'], 'danger', $e->getMessage());
        }
        $this->audit('order.status_changed', 'order', $params['id'], ['from' => $from, 'to' => $status]);
        Response::redirect('/admin/orders/' . $params['id'], 'success', 'Order marked as ' . strtolower(OrderService::label($status)) . '.');
    }

    public function payments(): string
    {
        return $this->view('admin/orders/payments', [
            'pageTitle' => 'Payments',
            'payments'  => $this->db()->fetchAll(
                'SELECT p.*, o.invoice_no, o.status AS order_status, o.total_paise FROM payments p JOIN orders o ON o.id = p.order_id ORDER BY p.id DESC LIMIT 200'
            ),
        ], 'admin');
    }
}
