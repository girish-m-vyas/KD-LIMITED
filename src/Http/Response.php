<?php
declare(strict_types=1);

namespace KD\Http;

use KD\Support\Env;

final class Response
{
    /** Thrown to stop the request after a redirect has been sent. */
    public static function redirect(string $path, ?string $flashType = null, ?string $flashMessage = null): never
    {
        if ($flashType !== null && $flashMessage !== null) {
            self::flash($flashType, $flashMessage);
        }
        $target = self::safeTarget($path, '/');
        header('Location: ' . url($target), true, 303);
        throw new RedirectSignal();
    }

    /** Redirect back to the form with errors and the submitted values. */
    public static function back(string $path, array $errors, array $old = []): never
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old']    = $old;
        self::redirect($path);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Only allow local paths like "/account". Absolute URLs, protocol-relative
     * URLs and header-splitting characters all fall back to $fallback, which
     * stops open-redirect attacks via ?next=.
     */
    public static function safeTarget(?string $target, string $fallback): string
    {
        if ($target === null || $target === '') {
            return $fallback;
        }
        if (!preg_match('#^/(?![/\\\\])[^\s\\\\]*$#', $target)) {
            return $fallback;
        }
        return $target;
    }

    public static function securityHeaders(bool $https): void
    {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        header_remove('X-Powered-By');
    }

    public static function noStore(): void
    {
        header('Cache-Control: no-store, max-age=0');
        header('Pragma: no-cache');
    }
}
