<?php
declare(strict_types=1);

namespace KD\Support;

/**
 * Renders a PHP template inside a layout. Templates must print variables
 * with e() — the only raw output allowed is the already-rendered $content
 * and helper output such as csrf_field().
 */
final class View
{
    /** @var array<string, mixed> data shared with every template */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = 'store'): string
    {
        $data += self::$shared;
        $data['errors'] ??= Session::pullErrors();
        $content = self::capture($template, $data);
        Session::clearOld();
        if ($layout === null) {
            return $content;
        }
        return self::capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    /** Render a partial template (no layout), for use inside templates. */
    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data + self::$shared);
    }

    private static function capture(string $template, array $data): string
    {
        if (!preg_match('#^[a-z0-9_/-]+$#', $template)) {
            throw new \InvalidArgumentException('Bad template name.');
        }
        $file = APP_ROOT . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Template {$template} not found.");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
