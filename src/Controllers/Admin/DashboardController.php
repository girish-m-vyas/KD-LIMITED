<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Controllers\Controller;
use KD\Services\OrderService;

final class DashboardController extends Controller
{
    public function index(): string
    {
        $db   = $this->db();
        $auth = App::adminAuth();

        $stats = [
            'products'  => (int) $db->value('SELECT COUNT(*) FROM products'),
            'customers' => (int) $db->value("SELECT COUNT(*) FROM customers WHERE email NOT LIKE 'deleted-%'"),
            'to_verify' => (int) $db->value('SELECT COUNT(*) FROM orders WHERE status = ?', [OrderService::STATUS_PAYMENT_SUBMITTED]),
            'awaiting'  => (int) $db->value('SELECT COUNT(*) FROM orders WHERE status = ?', [OrderService::STATUS_PENDING]),
            'revenue'   => (int) $db->value('SELECT COALESCE(SUM(total_paise), 0) FROM orders WHERE status IN (?, ?, ?)', [OrderService::STATUS_PAID, OrderService::STATUS_SHIPPED, OrderService::STATUS_DELIVERED]),
        ];

        return $this->view('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'stats'     => $stats,
            'orders'    => $auth->can('orders.manage') ? $db->fetchAll(
                'SELECT o.*, c.name AS customer_name FROM orders o JOIN customers c ON c.id = o.customer_id ORDER BY o.id DESC LIMIT 6'
            ) : [],
            'activity'  => $auth->can('audit.view') ? App::audit()->recent(8, 0) : [],
        ], 'admin');
    }
}
