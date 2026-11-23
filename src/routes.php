<?php
declare(strict_types=1);

use KD\Controllers\AccountController;
use KD\Controllers\Admin\AdminAuthController;
use KD\Controllers\Admin\AuditController;
use KD\Controllers\Admin\ContentController;
use KD\Controllers\Admin\CustomersController;
use KD\Controllers\Admin\DashboardController;
use KD\Controllers\Admin\OrdersController;
use KD\Controllers\Admin\ProductsController;
use KD\Controllers\Admin\ProfileController;
use KD\Controllers\Admin\UsersController;
use KD\Controllers\AuthController;
use KD\Controllers\StoreController;
use KD\Http\Router;

/**
 * Every route: [Controller, method, guard].
 * guard: null (public) | 'customer' | 'admin' (any admin) | 'admin:<permission>'
 */
return static function (Router $r): void {
    // Storefront
    $r->get('/',                [StoreController::class, 'home']);
    $r->get('/shop',            [StoreController::class, 'shop']);
    $r->get('/product/{id}',    [StoreController::class, 'product']);
    $r->get('/cart',            [StoreController::class, 'cart']);
    $r->post('/cart/add',       [StoreController::class, 'cartAdd']);
    $r->post('/cart/update',    [StoreController::class, 'cartUpdate']);
    $r->get('/checkout',        [StoreController::class, 'checkout']);
    $r->post('/checkout',       [StoreController::class, 'placeOrder', 'customer']);
    $r->get('/terms',           [StoreController::class, 'terms']);
    $r->get('/contact',         [StoreController::class, 'contact']);
    $r->post('/contact',        [StoreController::class, 'contactSend']);

    // Customer authentication
    $r->get('/login',           [AuthController::class, 'loginForm']);
    $r->post('/login',          [AuthController::class, 'login']);
    $r->get('/register',        [AuthController::class, 'registerForm']);
    $r->post('/register',       [AuthController::class, 'register']);
    $r->post('/logout',         [AuthController::class, 'logout']);

    // Customer account (ownership checked inside every action)
    $r->get('/account',                   [AccountController::class, 'index', 'customer']);
    $r->get('/account/orders',            [AccountController::class, 'orders', 'customer']);
    $r->get('/account/orders/{id}',       [AccountController::class, 'order', 'customer']);
    $r->post('/account/orders/{id}/pay',  [AccountController::class, 'pay', 'customer']);
    $r->get('/account/edit',              [AccountController::class, 'editForm', 'customer']);
    $r->post('/account/edit',             [AccountController::class, 'edit', 'customer']);
    $r->get('/account/password',          [AccountController::class, 'passwordForm', 'customer']);
    $r->post('/account/password',         [AccountController::class, 'password', 'customer']);
    $r->get('/account/delete',            [AccountController::class, 'deleteForm', 'customer']);
    $r->post('/account/delete',           [AccountController::class, 'delete', 'customer']);

    // Admin authentication
    $r->get('/admin/login',     [AdminAuthController::class, 'loginForm']);
    $r->post('/admin/login',    [AdminAuthController::class, 'login']);
    $r->post('/admin/logout',   [AdminAuthController::class, 'logout', 'admin']);

    $r->get('/admin',           [DashboardController::class, 'index', 'admin:dashboard.view']);

    // Products
    $r->get('/admin/products',              [ProductsController::class, 'index',  'admin:catalog.manage']);
    $r->get('/admin/products/new',          [ProductsController::class, 'create', 'admin:catalog.manage']);
    $r->post('/admin/products',             [ProductsController::class, 'store',  'admin:catalog.manage']);
    $r->get('/admin/products/{id}/edit',    [ProductsController::class, 'edit',   'admin:catalog.manage']);
    $r->post('/admin/products/{id}',        [ProductsController::class, 'update', 'admin:catalog.manage']);
    $r->post('/admin/products/{id}/delete', [ProductsController::class, 'delete', 'admin:catalog.manage']);

    // Simple content types share one controller (see ContentController::TYPES)
    foreach (ContentController::TYPES as $slug => $type) {
        $guard = 'admin:' . $type['permission'];
        $r->get("/admin/{$slug}",              [ContentController::class, 'index_' . $slug,  $guard]);
        $r->get("/admin/{$slug}/new",          [ContentController::class, 'create_' . $slug, $guard]);
        $r->post("/admin/{$slug}",             [ContentController::class, 'store_' . $slug,  $guard]);
        $r->get("/admin/{$slug}/{id}/edit",    [ContentController::class, 'edit_' . $slug,   $guard]);
        $r->post("/admin/{$slug}/{id}",        [ContentController::class, 'update_' . $slug, $guard]);
        $r->post("/admin/{$slug}/{id}/delete", [ContentController::class, 'delete_' . $slug, $guard]);
    }

    // Orders, payments, customers, messages
    $r->get('/admin/orders',              [OrdersController::class, 'index',    'admin:orders.manage']);
    $r->get('/admin/orders/{id}',         [OrdersController::class, 'show',     'admin:orders.manage']);
    $r->post('/admin/orders/{id}/status', [OrdersController::class, 'status',   'admin:orders.manage']);
    $r->get('/admin/payments',            [OrdersController::class, 'payments', 'admin:payments.view']);
    $r->get('/admin/customers',              [CustomersController::class, 'index',    'admin:customers.manage']);
    $r->post('/admin/customers/{id}/delete', [CustomersController::class, 'delete',   'admin:customers.manage']);
    $r->get('/admin/messages',               [CustomersController::class, 'messages', 'admin:messages.view']);

    // Admin users and roles
    $r->get('/admin/users',                  [UsersController::class, 'index',  'admin:users.manage']);
    $r->get('/admin/users/new',              [UsersController::class, 'create', 'admin:users.manage']);
    $r->post('/admin/users',                 [UsersController::class, 'store',  'admin:users.manage']);
    $r->get('/admin/users/{id}/edit',        [UsersController::class, 'edit',   'admin:users.manage']);
    $r->post('/admin/users/{id}',            [UsersController::class, 'update', 'admin:users.manage']);
    $r->post('/admin/users/{id}/reset',      [UsersController::class, 'resetPassword', 'admin:users.manage']);

    // Own profile (any admin)
    $r->get('/admin/profile',            [ProfileController::class, 'edit',     'admin']);
    $r->post('/admin/profile',           [ProfileController::class, 'update',   'admin']);
    $r->post('/admin/profile/password',  [ProfileController::class, 'password', 'admin']);

    $r->get('/admin/audit',              [AuditController::class, 'index', 'admin:audit.view']);
};
