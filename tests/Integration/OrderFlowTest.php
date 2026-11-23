<?php
declare(strict_types=1);

namespace Tests\Integration;

use KD\Database;
use KD\Services\CustomerService;
use KD\Services\OrderService;
use KD\Support\Password;
use Tests\TestCase;

final class OrderFlowTest extends TestCase
{
    private Database $db;
    private OrderService $orders;

    public function setUp(): void
    {
        parent::setUp();
        $this->db = $this->freshDb();
        $now = '2026-01-01 00:00:00';
        $this->db->insert('INSERT INTO categories (title, is_top, image) VALUES (?,?,?)', ['Men', 1, '']);
        $this->db->insert('INSERT INTO product_categories (title, is_top, image) VALUES (?,?,?)', ['Shirts', 1, '']);
        $p = 'INSERT INTO products (product_category_id, category_id, title, image1, price_paise, sale_price_paise, label, keywords, description, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
        $this->db->insert($p, [1, 1, 'Shirt', 'a.png', 100000, null, '', '', '', $now, $now]);
        $this->db->insert($p, [1, 1, 'Sale Jacket', 'b.png', 500000, 350000, 'sale', '', '', $now, $now]);
        $c = 'INSERT INTO customers (name, email, password_hash, contact, city, address, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)';
        $this->db->insert($c, ['A', 'a@example.com', Password::hash('Monsoon-Train-42'), '1', 'x', 'x', $now, $now]);
        $this->db->insert($c, ['B', 'b@example.com', Password::hash('Monsoon-Train-42'), '1', 'x', 'x', $now, $now]);
        $this->orders = new OrderService($this->db);
    }

    private function cart(array ...$lines): array
    {
        $items = [];
        foreach ($lines as [$pid, $qty, $size]) {
            $items["{$pid}|{$size}"] = ['product_id' => $pid, 'qty' => $qty, 'size' => $size];
        }
        return $items;
    }

    public function testPlaceOrderUsesServerSidePrices(): void
    {
        $id = $this->orders->place(1, $this->cart([1, 2, 'M'], [2, 1, 'L']));
        $order = $this->db->fetch('SELECT * FROM orders WHERE id = ?', [$id]);
        $this->assertSame(2 * 100000 + 350000, (int) $order['total_paise'], 'sale price applies, totals computed on server');
        $this->assertSame(OrderService::STATUS_PENDING, $order['status']);
        $this->assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM order_items WHERE order_id = ?', [$id]));
    }

    public function testEmptyCartOrMissingProductFails(): void
    {
        $this->assertThrows(\DomainException::class, fn () => $this->orders->place(1, []));
        $this->assertThrows(\DomainException::class, fn () => $this->orders->place(1, $this->cart([999, 1, 'M'])));
        $this->assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM orders'), 'failed order leaves nothing behind');
    }

    public function testInvoiceNumbersAreUnique(): void
    {
        $seen = [];
        for ($i = 0; $i < 20; $i++) {
            $id = $this->orders->place(1, $this->cart([1, 1, 'M']));
            $seen[] = $this->db->value('SELECT invoice_no FROM orders WHERE id = ?', [$id]);
        }
        $this->assertCount(20, array_unique($seen));
    }

    public function testCustomerCannotSeeOrPayAnotherCustomersOrder(): void
    {
        $id = $this->orders->place(1, $this->cart([1, 1, 'M']));
        $this->assertNull($this->orders->findForCustomer(2, $id));
        $this->assertThrows(\DomainException::class, fn () => $this->orders->submitPayment(2, $id, $this->paymentData()));
        $this->assertSame(OrderService::STATUS_PENDING, $this->db->value('SELECT status FROM orders WHERE id = ?', [$id]));
    }

    public function testPaymentSubmissionNeedsAdminVerification(): void
    {
        $id = $this->orders->place(1, $this->cart([1, 1, 'M']));
        $this->orders->submitPayment(1, $id, $this->paymentData());
        $this->assertSame(OrderService::STATUS_PAYMENT_SUBMITTED, $this->db->value('SELECT status FROM orders WHERE id = ?', [$id]));

        // Can't submit twice.
        $this->assertThrows(\DomainException::class, fn () => $this->orders->submitPayment(1, $id, $this->paymentData()));

        $this->orders->setStatus($id, OrderService::STATUS_PAID);
        $this->assertSame(OrderService::STATUS_PAID, $this->db->value('SELECT status FROM orders WHERE id = ?', [$id]));
    }

    public function testInvalidStatusTransitionsAreRejected(): void
    {
        $id = $this->orders->place(1, $this->cart([1, 1, 'M']));
        $this->assertThrows(\DomainException::class, fn () => $this->orders->setStatus($id, 'free'));
        $this->assertThrows(\DomainException::class, fn () => $this->orders->setStatus($id, OrderService::STATUS_SHIPPED), 'cannot ship unpaid order');
        $this->orders->setStatus($id, OrderService::STATUS_CANCELLED);
        $this->assertThrows(\DomainException::class, fn () => $this->orders->setStatus($id, OrderService::STATUS_PAID), 'cancelled is final');
    }

    public function testPaymentAmountMustMatchOrderTotal(): void
    {
        $id = $this->orders->place(1, $this->cart([1, 1, 'M']));
        $data = $this->paymentData();
        $data['amount_paise'] = 1;
        $this->assertThrows(\DomainException::class, fn () => $this->orders->submitPayment(1, $id, $data));
    }

    private function paymentData(): array
    {
        return ['amount_paise' => 100000, 'mode' => 'UPI', 'ref_no' => 'UTR123456', 'code' => '', 'payment_date' => '2026-01-02'];
    }

    public function testDeletingCustomerWithOrdersAnonymisesInsteadOfLosingOrders(): void
    {
        $id = $this->orders->place(1, $this->cart([1, 1, 'M']));
        (new CustomerService($this->db))->delete(1);
        $row = $this->db->fetch('SELECT * FROM customers WHERE id = 1');
        $this->assertNotNull($row);
        $this->assertStringNotContains('a@example.com', $row['email']);
        $this->assertSame('Deleted customer', $row['name']);
        $this->assertNotNull($this->db->fetch('SELECT * FROM orders WHERE id = ?', [$id]));

        (new CustomerService($this->db))->delete(2);
        $this->assertNull($this->db->fetch('SELECT * FROM customers WHERE id = 2'), 'no orders: hard delete');
    }

    public function testRegisterRejectsDuplicateEmailCaseInsensitive(): void
    {
        $svc = new CustomerService($this->db);
        $this->assertThrows(\DomainException::class, fn () => $svc->register([
            'name' => 'X', 'email' => 'A@Example.com', 'password' => 'Monsoon-Train-42',
            'contact' => '1', 'city' => 'x', 'address' => 'x',
        ], null));
    }

    public function testRegisterStoresHashNotPassword(): void
    {
        $svc = new CustomerService($this->db);
        $id = $svc->register([
            'name' => 'New', 'email' => 'new@example.com', 'password' => 'Monsoon-Train-42',
            'contact' => '1', 'city' => 'x', 'address' => 'x',
        ], null);
        $hash = $this->db->value('SELECT password_hash FROM customers WHERE id = ?', [$id]);
        $this->assertNotSame('Monsoon-Train-42', $hash);
        $this->assertTrue(Password::verify('Monsoon-Train-42', $hash));
    }

    public function testChangePasswordNeedsCurrentPassword(): void
    {
        $svc = new CustomerService($this->db);
        $this->assertNotNull($svc->changePassword(1, 'wrong', 'Brand-New-Pass-7'));
        $this->assertNull($svc->changePassword(1, 'Monsoon-Train-42', 'Brand-New-Pass-7'));
        $hash = $this->db->value('SELECT password_hash FROM customers WHERE id = 1');
        $this->assertTrue(Password::verify('Brand-New-Pass-7', $hash));
    }
}
