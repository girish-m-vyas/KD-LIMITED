<?php
declare(strict_types=1);

namespace KD\Services;

use KD\Database;

/**
 * Orders and offline payments.
 *
 * Flow: pending → payment_submitted (customer) → paid → shipped → delivered (admin).
 * A customer can only *submit* payment details; an admin has to verify them
 * before an order counts as paid. The old site let customers mark their own
 * orders complete.
 */
final class OrderService
{
    public const STATUS_PENDING           = 'pending';
    public const STATUS_PAYMENT_SUBMITTED = 'payment_submitted';
    public const STATUS_PAID              = 'paid';
    public const STATUS_SHIPPED           = 'shipped';
    public const STATUS_DELIVERED         = 'delivered';
    public const STATUS_CANCELLED         = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_PENDING           => 'Awaiting payment',
        self::STATUS_PAYMENT_SUBMITTED => 'Payment submitted',
        self::STATUS_PAID              => 'Paid',
        self::STATUS_SHIPPED           => 'Shipped',
        self::STATUS_DELIVERED         => 'Delivered',
        self::STATUS_CANCELLED         => 'Cancelled',
    ];

    /** Allowed admin status changes. */
    private const TRANSITIONS = [
        self::STATUS_PENDING           => [self::STATUS_PAID, self::STATUS_CANCELLED],
        self::STATUS_PAYMENT_SUBMITTED => [self::STATUS_PAID, self::STATUS_PENDING, self::STATUS_CANCELLED],
        self::STATUS_PAID              => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED           => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED         => [],
        self::STATUS_CANCELLED         => [],
    ];

    public const PAYMENT_MODES = ['UPI', 'Bank transfer', 'Cheque', 'Cash deposit'];

    public function __construct(private readonly Database $db) {}

    /**
     * @param array<string, array{product_id: int, qty: int, size: string}> $cartItems
     * @return int new order id
     */
    public function place(int $customerId, array $cartItems): int
    {
        if ($cartItems === []) {
            throw new \DomainException('Your cart is empty.');
        }

        return $this->db->transaction(function (Database $db) use ($customerId, $cartItems): int {
            $lines = [];
            $total = 0;
            foreach ($cartItems as $item) {
                $product = $db->fetch('SELECT id, title, price_paise, sale_price_paise, label FROM products WHERE id = ?', [(int) $item['product_id']]);
                if ($product === null) {
                    throw new \DomainException('A product in your cart is no longer available. Please review your cart.');
                }
                $unit   = self::unitPrice($product);
                $qty    = (int) $item['qty'];
                $total += $unit * $qty;
                $lines[] = [$product, $unit, $qty, (string) $item['size']];
            }

            $now     = now();
            $orderId = $db->insert(
                'INSERT INTO orders (customer_id, invoice_no, total_paise, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$customerId, $this->newInvoiceNo($db), $total, self::STATUS_PENDING, $now, $now]
            );
            foreach ($lines as [$product, $unit, $qty, $size]) {
                $db->insert(
                    'INSERT INTO order_items (order_id, product_id, product_title, unit_price_paise, qty, size) VALUES (?, ?, ?, ?, ?, ?)',
                    [$orderId, (int) $product['id'], (string) $product['title'], $unit, $qty, $size]
                );
            }
            return $orderId;
        });
    }

    /** @param array<string, mixed> $product */
    public static function unitPrice(array $product): int
    {
        $sale = $product['sale_price_paise'];
        if ($product['label'] === 'sale' && $sale !== null && (int) $sale > 0) {
            return (int) $sale;
        }
        return (int) $product['price_paise'];
    }

    private function newInvoiceNo(Database $db): string
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'KD' . date('ymd') . random_int(100000, 999999);
            if ($db->value('SELECT 1 FROM orders WHERE invoice_no = ?', [$candidate]) === null) {
                return $candidate;
            }
        }
        throw new \RuntimeException('Could not create an invoice number.');
    }

    /** The order, only if it belongs to this customer (stops IDOR). */
    public function findForCustomer(int $customerId, int $orderId): ?array
    {
        return $this->db->fetch('SELECT * FROM orders WHERE id = ? AND customer_id = ?', [$orderId, $customerId]);
    }

    /** @return list<array<string, mixed>> */
    public function forCustomer(int $customerId): array
    {
        return $this->db->fetchAll(
            'SELECT o.*, (SELECT SUM(qty) FROM order_items i WHERE i.order_id = o.id) AS item_count FROM orders o WHERE o.customer_id = ? ORDER BY o.id DESC',
            [$customerId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function items(int $orderId): array
    {
        return $this->db->fetchAll('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    /**
     * @param array{amount_paise: int, mode: string, ref_no: string, code: string, payment_date: string} $data
     */
    public function submitPayment(int $customerId, int $orderId, array $data): void
    {
        $this->db->transaction(function (Database $db) use ($customerId, $orderId, $data): void {
            $order = $db->fetch('SELECT * FROM orders WHERE id = ? AND customer_id = ?', [$orderId, $customerId]);
            if ($order === null) {
                throw new \DomainException('Order not found.');
            }
            if ($order['status'] !== self::STATUS_PENDING) {
                throw new \DomainException('Payment for this order has already been submitted.');
            }
            if ((int) $data['amount_paise'] !== (int) $order['total_paise']) {
                throw new \DomainException('The amount must match the order total of ' . money((int) $order['total_paise']) . '.');
            }
            if (!in_array($data['mode'], self::PAYMENT_MODES, true)) {
                throw new \DomainException('Choose a valid payment mode.');
            }
            $db->insert(
                'INSERT INTO payments (order_id, amount_paise, mode, ref_no, code, payment_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$orderId, (int) $data['amount_paise'], $data['mode'], $data['ref_no'], $data['code'], $data['payment_date'], now()]
            );
            // The status check above plus this conditional update stops double submission races.
            $changed = $db->execute(
                'UPDATE orders SET status = ?, updated_at = ? WHERE id = ? AND status = ?',
                [self::STATUS_PAYMENT_SUBMITTED, now(), $orderId, self::STATUS_PENDING]
            );
            if ($changed !== 1) {
                throw new \DomainException('Payment for this order has already been submitted.');
            }
        });
    }

    /** Admin status change, following the allowed transitions only. */
    public function setStatus(int $orderId, string $status): string
    {
        if (!isset(self::STATUS_LABELS[$status])) {
            throw new \DomainException('Unknown status.');
        }
        $current = $this->db->value('SELECT status FROM orders WHERE id = ?', [$orderId]);
        if ($current === null) {
            throw new \DomainException('Order not found.');
        }
        if (!in_array($status, self::TRANSITIONS[$current] ?? [], true)) {
            throw new \DomainException('An order that is "' . self::STATUS_LABELS[$current] . '" can\'t be changed to "' . self::STATUS_LABELS[$status] . '".');
        }
        $this->db->execute('UPDATE orders SET status = ?, updated_at = ? WHERE id = ? AND status = ?', [$status, now(), $orderId, $current]);
        return (string) $current;
    }

    /** @return list<string> */
    public static function nextStatuses(string $current): array
    {
        return self::TRANSITIONS[$current] ?? [];
    }

    public static function label(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }
}
