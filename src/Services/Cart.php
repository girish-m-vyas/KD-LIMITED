<?php
declare(strict_types=1);

namespace KD\Services;

/**
 * Shopping cart kept in the visitor's session. It stores only product IDs,
 * sizes and quantities, never prices: prices are always read from the
 * database, so a visitor can't change what they pay.
 */
final class Cart
{
    public const SIZES   = ['S' => 'Small', 'M' => 'Medium', 'L' => 'Large', 'XL' => 'Extra large'];
    public const MAX_QTY = 10;
    public const MAX_LINES = 30;

    public static function add(int $productId, int $qty, string $size): void
    {
        self::guard($productId, $qty, $size);
        $key   = $productId . '|' . $size;
        $items = self::items();
        if (!isset($items[$key]) && count($items) >= self::MAX_LINES) {
            throw new \InvalidArgumentException('Your cart is full.');
        }
        $current = $items[$key]['qty'] ?? 0;
        $items[$key] = ['product_id' => $productId, 'qty' => min(self::MAX_QTY, $current + $qty), 'size' => $size];
        $_SESSION['cart'] = $items;
    }

    public static function update(string $key, int $qty): void
    {
        $items = self::items();
        if (!isset($items[$key])) {
            return;
        }
        if ($qty <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['qty'] = min(self::MAX_QTY, $qty);
        }
        $_SESSION['cart'] = $items;
    }

    public static function remove(string $key): void
    {
        self::update($key, 0);
    }

    /** @return array<string, array{product_id: int, qty: int, size: string}> */
    public static function items(): array
    {
        $items = $_SESSION['cart'] ?? [];
        return is_array($items) ? $items : [];
    }

    public static function count(): int
    {
        return array_sum(array_column(self::items(), 'qty'));
    }

    public static function clear(): void
    {
        unset($_SESSION['cart']);
    }

    private static function guard(int $productId, int $qty, string $size): void
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('Unknown product.');
        }
        if ($qty < 1 || $qty > self::MAX_QTY) {
            throw new \InvalidArgumentException('Quantity must be between 1 and ' . self::MAX_QTY . '.');
        }
        if (!isset(self::SIZES[$size])) {
            throw new \InvalidArgumentException('Please choose a valid size.');
        }
    }
}
