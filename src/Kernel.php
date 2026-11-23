<?php
declare(strict_types=1);

namespace KD;

use KD\Controllers\MediaController;
use KD\Http\HttpException;
use KD\Http\RedirectSignal;
use KD\Http\Request;
use KD\Http\Response;
use KD\Http\Router;
use KD\Services\Cart;
use KD\Support\Csrf;
use KD\Support\Env;
use KD\Support\Session;
use KD\Support\View;

/**
 * Handles one web request. Security checks that must never be skipped live
 * here, not in individual pages:
 *  - security headers on every response
 *  - CSRF check on every POST
 *  - login and permission guards declared on each route
 */
final class Kernel
{
    public static function boot(): void
    {
        Env::load(APP_ROOT . '/.env');
        date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'Asia/Kolkata'));

        $debug = Env::get('APP_ENV', 'production') === 'local' && Env::bool('APP_DEBUG');
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');
        error_reporting(E_ALL);
    }

    public static function handle(): void
    {
        $https = Request::isHttps();
        Response::securityHeaders($https);

        $path   = Request::path();
        $method = Request::method();

        // Uploaded images are served by a dedicated handler (string path segments).
        if ($method === 'GET' && preg_match('#^/media/([a-z]+)/([a-f0-9]{32}\.(?:jpg|png|webp))$#', $path, $m)) {
            Session::start($https);
            session_write_close();
            (new MediaController())->show($m[1], $m[2]);
            return;
        }

        Session::start($https);

        if (str_starts_with($path, '/admin') || str_starts_with($path, '/account') || $path === '/checkout') {
            Response::noStore();
        }

        try {
            if ($method === 'POST' && !Csrf::verify(Request::input('_csrf'))) {
                throw new HttpException('Your form expired or was sent from another site. Please go back, refresh the page and try again.', 419);
            }

            $router = new Router();
            (require APP_ROOT . '/src/routes.php')($router);
            [$handler, $params] = $router->match($method, $path);
            [$class, $action, $guard] = $handler + [2 => null];

            self::shareViewData();
            self::applyGuard($guard, $path);

            echo (new $class())->$action($params);
        } catch (RedirectSignal) {
            return;
        } catch (HttpException $e) {
            self::renderError($e->getCode(), $e->getMessage(), $path);
        } catch (\Throwable $e) {
            $ref = bin2hex(random_bytes(4));
            error_log("[{$ref}] " . $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            if (ini_get('display_errors') === '1') {
                throw $e;
            }
            self::renderError(500, 'Something went wrong on our side. Reference: ' . $ref, $path);
        }
    }

    private static function applyGuard(?string $guard, string $path): void
    {
        if ($guard === null) {
            return;
        }
        if ($guard === 'customer') {
            if (App::customerAuth()->current() === null) {
                Response::redirect('/login?' . http_build_query(['next' => $path]), 'info', 'Please log in to continue.');
            }
            return;
        }
        if (str_starts_with($guard, 'admin')) {
            $admin = App::adminAuth()->current();
            if ($admin === null) {
                Response::redirect('/admin/login', 'info', 'Please log in to continue.');
            }
            // Admins with a temporary password must change it before anything else.
            if ((int) $admin['must_change_password'] === 1 && !str_starts_with($path, '/admin/profile') && $path !== '/admin/logout') {
                Response::redirect('/admin/profile', 'warning', 'Please set a new password before continuing.');
            }
            $permission = substr($guard, 6); // "admin:catalog.manage"
            if ($permission !== '' && $permission !== false && !App::adminAuth()->can($permission)) {
                App::audit()->record((int) $admin['id'], 'admin.access_denied', null, null, ['path' => $path, 'permission' => $permission], Request::ip());
                throw HttpException::forbidden();
            }
        }
    }

    private static function shareViewData(): void
    {
        View::share('cartCount', Cart::count());
        View::share('flash', Session::pullFlash());
        View::share('customer', App::customerAuth()->id() !== null ? App::customerAuth()->current() : null);
        View::share('admin', App::adminAuth()->id() !== null ? App::adminAuth()->current() : null);
        View::share('appName', (string) Env::get('APP_NAME', 'KD Limited'));
    }

    private static function renderError(int $code, string $message, string $path): void
    {
        $code = in_array($code, [400, 403, 404, 405, 419, 429, 500], true) ? $code : 500;
        http_response_code($code);
        $titles = [400 => 'Bad request', 403 => 'Access denied', 404 => 'Page not found', 405 => 'Not allowed', 419 => 'Form expired', 429 => 'Too many requests', 500 => 'Server error'];
        try {
            self::shareViewData();
            $layout = str_starts_with($path, '/admin') && App::adminAuth()->current() !== null ? 'admin' : 'store';
            echo View::render('errors/error', ['code' => $code, 'title' => $titles[$code], 'message' => $message, 'pageTitle' => $titles[$code]], $layout);
        } catch (\Throwable $e) {
            error_log('Error page failed: ' . $e->getMessage());
            header('Content-Type: text/plain; charset=utf-8');
            echo $titles[$code];
        }
    }
}
