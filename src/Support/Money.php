<?php
declare(strict_types=1);

namespace KD\Support;

/** Money is stored as whole paise (integers) to avoid floating-point errors. */
final class Money
{
    public static function format(int $paise): string
    {
        return '₹' . number_format($paise / 100, 2, '.', ',');
    }

    /** "1499.50" → 149950. Returns null for anything that isn't a non-negative amount. */
    public static function parse(string $value): ?int
    {
        $value = trim($value);
        if (!preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/', $value, $m)) {
            return null;
        }
        $paise = str_pad($m[2] ?? '', 2, '0');
        return (int) $m[1] * 100 + (int) $paise;
    }

    public static function toInput(?int $paise): string
    {
        if ($paise === null) {
            return '';
        }
        return number_format($paise / 100, 2, '.', '');
    }
}
