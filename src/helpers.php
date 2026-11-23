<?php
declare(strict_types=1);

use KD\Support\Csrf;
use KD\Support\Env;
use KD\Support\Money;

/** Escape any value for safe output in HTML text or a quoted attribute. */
function e(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an app URL from a path and optional query string parameters. */
function url(string $path = '/', array $query = []): string
{
    $base = rtrim((string) Env::get('APP_BASE_PATH', ''), '/');
    $url  = $base . '/' . ltrim($path, '/');
    $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');
    return $query ? $url . '?' . http_build_query($query) : $url;
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

/** URL for an uploaded image, served through the media route (never directly from disk). */
function media(string $folder, ?string $file): string
{
    if ($file === null || $file === '') {
        return asset('img/placeholder.svg');
    }
    return url('/media/' . rawurlencode($folder) . '/' . rawurlencode($file));
}

function csrf_field(): string
{
    return Csrf::field();
}

function money(int|string|null $paise): string
{
    return Money::format((int) $paise);
}

/** Old form input after a validation failure, escaped. */
function old(string $key, mixed $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

/** Bootstrap error message for a form field, escaped. */
function field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . e($errors[$field]) . '</div>' : '';
}

function invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function now(): string
{
    return date('Y-m-d H:i:s');
}
