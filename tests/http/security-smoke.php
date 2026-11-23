<?php
declare(strict_types=1);

/**
 * End-to-end security smoke test against a RUNNING DEVELOPMENT copy of the site.
 * It registers test customers, places orders and tries real attacks over HTTP.
 * Never point it at a live shop.
 *
 *   php -S 127.0.0.1:8080 -t public public/index.php      (in another terminal)
 *   KD_TEST_ADMIN_EMAIL=… KD_TEST_ADMIN_PASSWORD=… \
 *   KD_TEST_CATALOG_EMAIL=… KD_TEST_CATALOG_PASSWORD=… \
 *   php tests/http/security-smoke.php http://127.0.0.1:8080
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
if (!preg_match('#^http://(127\.0\.0\.1|localhost)(:\d+)?$#', $base)) {
    fwrite(STDERR, "Refusing to run against {$base}: only localhost development servers are allowed.\n");
    exit(1);
}
foreach (['KD_TEST_ADMIN_EMAIL', 'KD_TEST_ADMIN_PASSWORD', 'KD_TEST_CATALOG_EMAIL', 'KD_TEST_CATALOG_PASSWORD'] as $var) {
    if (!getenv($var)) {
        fwrite(STDERR, "Set {$var} (see the comment at the top of this file).\n");
        exit(1);
    }
}

final class Client
{
    private string $jar;
    public string $body = '';
    public int $status = 0;
    public array $headers = [];

    public function __construct(private readonly string $base)
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'kdjar');
    }

    public function get(string $path): self
    {
        return $this->send('GET', $path);
    }

    public function post(string $path, array $data, bool $withCsrf = true, ?string $csrfFrom = null): self
    {
        if ($withCsrf) {
            $data['_csrf'] = $this->csrf($csrfFrom ?? $path);
        }
        return $this->send('POST', $path, $data);
    }

    public function csrf(string $page): string
    {
        $html = $this->send('GET', $page)->body;
        if (!preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $html, $m)) {
            throw new RuntimeException("No CSRF token on {$page} (HTTP {$this->status})");
        }
        return $m[1];
    }

    public function location(): string
    {
        return $this->headers['location'] ?? '';
    }

    public function cookie(string $name): ?string
    {
        foreach (file($this->jar) ?: [] as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) === 7 && $parts[5] === $name) {
                return $parts[6];
            }
        }
        return null;
    }

    private function send(string $method, string $path, ?array $data = null): self
    {
        $ch = curl_init($this->base . $path);
        $this->headers = [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $this->headers[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            },
        ]);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $this->body   = (string) curl_exec($ch);
        $this->status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return $this;
    }
}

$passed = 0;
$failed = 0;
function check(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? "  PASS  " : "  FAIL  ") . $label . ($ok || $detail === '' ? '' : "  ({$detail})") . "\n";
}

function registerCustomer(string $base, string $tag): array
{
    $c = new Client($base);
    $email = "smoke-{$tag}-" . bin2hex(random_bytes(3)) . '@example.com';
    $pw = 'Smoke-Test-' . random_int(1000, 9999);
    $c->post('/register', [
        'name' => "Smoke {$tag}", 'email' => $email, 'password' => $pw, 'password_confirm' => $pw,
        'contact' => '+91 90000 00000', 'city' => 'Thane', 'address' => '1 Test Road',
    ]);
    return [$c, $email, $pw];
}

function firstProductId(Client $c): int
{
    $c->get('/shop');
    preg_match('#/product/(\d+)#', $c->body, $m);
    return (int) ($m[1] ?? 0);
}

echo "KD-LIMITED security smoke test against {$base}\n\n";

// ---------------------------------------------------------------- headers
echo "Headers and exposure\n";
$anon = new Client($base);
$anon->get('/');
check('Content-Security-Policy is set', str_contains($anon->headers['content-security-policy'] ?? '', "default-src 'self'"));
check('X-Frame-Options DENY', ($anon->headers['x-frame-options'] ?? '') === 'DENY');
check('X-Content-Type-Options nosniff', ($anon->headers['x-content-type-options'] ?? '') === 'nosniff');
check('No X-Powered-By header', !isset($anon->headers['x-powered-by']));
$setCookie = '';
$anon2 = new Client($base);
$anon2->get('/login');
$setCookie = strtolower($anon2->headers['set-cookie'] ?? '');
check('Session cookie is HttpOnly and SameSite', str_contains($setCookie, 'httponly') && str_contains($setCookie, 'samesite=lax'), $setCookie);
foreach (['/.env', '/../.env', '/src/Database.php', '/storage/database.sqlite', '/database/schema.mysql.sql', '/kd.sql', '/composer.json'] as $p) {
    $anon->get($p);
    check("{$p} is not downloadable", $anon->status === 404 || !str_contains($anon->body, 'DB_'), "HTTP {$anon->status}");
}

// ---------------------------------------------------------------- CSRF
echo "\nCSRF\n";
$csrf = new Client($base);
$csrf->post('/login', ['email' => 'x@example.com', 'password' => 'x'], false);
check('POST without a token is rejected (419)', $csrf->status === 419, "HTTP {$csrf->status}");
$csrf->post('/contact', ['_csrf' => str_repeat('a', 64), 'name' => 'x', 'email' => 'x@example.com', 'subject' => 's', 'message' => 'hello there'], false);
check('POST with a forged token is rejected (419)', $csrf->status === 419, "HTTP {$csrf->status}");

// ---------------------------------------------------------------- SQL injection
echo "\nSQL injection\n";
$sqli = new Client($base);
foreach (["' OR '1'='1", "admin@example.com' -- ", "\" OR 1=1 #"] as $payload) {
    $sqli->post('/admin/login', ['email' => $payload, 'password' => $payload], true, '/admin/login');
    check("Admin login refuses " . json_encode($payload), $sqli->location() === '' || str_ends_with($sqli->location(), '/admin/login'), $sqli->location());
}
$sqli->get("/product/1'%20OR%20'1'='1");
check('Injected product ID returns 404', $sqli->status === 404, "HTTP {$sqli->status}");
$sqli->get('/shop?pcat=1%20OR%201=1&q=%25%27%20OR%201=1--');
check('Injected shop filters are handled safely', $sqli->status === 200);

// ---------------------------------------------------------------- customer flow + IDOR + tampering
echo "\nOrders, IDOR and price tampering\n";
[$alice] = registerCustomer($base, 'alice');
[$bob]   = registerCustomer($base, 'bob');
$pid = firstProductId($alice);
check('Registration logs the customer in', $alice->get('/account/orders')->status === 200);

$alice->post('/cart/add', ['product_id' => (string) $pid, 'qty' => '2', 'size' => 'M', 'price' => '1', 'p_price' => '1'], true, "/product/{$pid}");
$alice->get('/cart');
preg_match('#Total</dt><dd[^>]*>₹([\d,\.]+)#', $alice->body, $m);
$cartTotal = $m[1] ?? '';
check('Extra price fields in the form are ignored', $cartTotal !== '' && $cartTotal !== '0.02' && $cartTotal !== '2.00', "total {$cartTotal}");

$alice->post('/cart/add', ['product_id' => (string) $pid, 'qty' => '-5', 'size' => 'M'], true, "/product/{$pid}");
check('Negative quantity is rejected', str_contains($alice->location(), "/product/{$pid}"));
$alice->post('/cart/add', ['product_id' => (string) $pid, 'qty' => '1', 'size' => '<script>'], true, "/product/{$pid}");
check('Unknown size is rejected', str_contains($alice->location(), "/product/{$pid}"));

$alice->post('/checkout', [], true, '/checkout');
preg_match('#/account/orders/(\d+)#', $alice->location(), $m);
$orderId = (int) ($m[1] ?? 0);
check('Checkout creates an order', $orderId > 0, $alice->location());

$bob->get("/account/orders/{$orderId}");
check("Another customer can't view the order (404)", $bob->status === 404, "HTTP {$bob->status}");
$bob->post("/account/orders/{$orderId}/pay", ['amount' => '1', 'mode' => 'UPI', 'ref_no' => 'X', 'payment_date' => date('Y-m-d')], true, '/account/orders');
check("Another customer can't submit payment for it (404)", $bob->status === 404, "HTTP {$bob->status}");

$alice->get("/account/orders/{$orderId}");
preg_match('#name="amount"[^>]*value="([\d\.]+)"#', $alice->body, $m);
$amount = $m[1] ?? '0';
$alice->post("/account/orders/{$orderId}/pay", ['amount' => '1.00', 'mode' => 'UPI', 'ref_no' => 'UTR1', 'payment_date' => date('Y-m-d')], true, "/account/orders/{$orderId}");
$alice->get("/account/orders/{$orderId}");
check('Underpayment is refused', str_contains($alice->body, 'must match the order total'));
$alice->post("/account/orders/{$orderId}/pay", ['amount' => $amount, 'mode' => 'UPI', 'ref_no' => 'UTR123456', 'payment_date' => date('Y-m-d')], true, "/account/orders/{$orderId}");
$alice->get("/account/orders/{$orderId}");
check('Customer payment only marks the order "Payment submitted"', str_contains($alice->body, 'Payment submitted') && !str_contains($alice->body, '>Paid<'));

// ---------------------------------------------------------------- XSS
echo "\nCross-site scripting\n";
$xss = '<script>alert(1)</script><img src=x onerror=alert(2)>';
$anon->post('/contact', ['name' => $xss, 'email' => 'xss@example.com', 'subject' => $xss, 'message' => $xss . ' long enough'], true, '/contact');
$anon->get('/shop?q=' . rawurlencode($xss));
check('Search text is escaped', !str_contains($anon->body, '<script>alert(1)') && str_contains($anon->body, '&lt;script&gt;'));

// ---------------------------------------------------------------- open redirect + session fixation
echo "\nRedirects and sessions\n";
[$carol, $carolEmail, $carolPw] = registerCustomer($base, 'carol');
$carol->post('/logout', [], true, '/');
$before = $carol->cookie('kd_session');
$carol->post('/login', ['email' => $carolEmail, 'password' => $carolPw, 'next' => 'https://evil.example/'], true, '/login');
check('Login ignores an external ?next= (no open redirect)', !str_contains($carol->location(), 'evil.example'), $carol->location());
check('Session ID changes on login (no fixation)', $before !== null && $carol->cookie('kd_session') !== $before);

// ---------------------------------------------------------------- brute force
echo "\nBrute force\n";
$bf = new Client($base);
for ($i = 0; $i < 6; $i++) {
    $bf->post('/login', ['email' => $carolEmail, 'password' => 'wrong-password-' . $i, 'next' => '/account'], true, '/login');
}
$bf->post('/login', ['email' => $carolEmail, 'password' => $carolPw, 'next' => '/account'], true, '/login');
$bf->get('/login');
check('Account locks after 5 failures, even with the right password', str_contains($bf->body, 'Too many failed attempts'));

// ---------------------------------------------------------------- admin RBAC
echo "\nAdmin roles\n";
$cat = new Client($base);
$cat->post('/admin/login', ['email' => getenv('KD_TEST_CATALOG_EMAIL'), 'password' => getenv('KD_TEST_CATALOG_PASSWORD')], true, '/admin/login');
check('Catalogue manager can log in', str_ends_with($cat->location(), '/admin'), $cat->location());
check('Catalogue manager can open products', $cat->get('/admin/products')->status === 200);
foreach (['/admin/users', '/admin/orders', '/admin/audit', '/admin/customers'] as $p) {
    check("Catalogue manager is blocked from {$p} (403)", $cat->get($p)->status === 403, "HTTP {$cat->status}");
}
$cat->post('/admin/users', ['name' => 'Evil', 'email' => 'evil@example.com', 'role' => 'super_admin'], true, '/admin/products');
check("Catalogue manager can't create admins by POST (403)", $cat->status === 403, "HTTP {$cat->status}");

// ---------------------------------------------------------------- uploads
echo "\nUploads\n";
$shell = tempnam(sys_get_temp_dir(), 'shell') . '.php';
file_put_contents($shell, "<?php echo 'PWNED'; system(\$_GET['c'] ?? 'id');");
$cat->post('/admin/products', [
    'title' => 'Upload test', 'product_category_id' => '1', 'category_id' => '1', 'price' => '100', 'label' => '',
    'image1' => new CURLFile($shell, 'image/jpeg', 'shell.php.jpg'),
], true, '/admin/products/new');
$cat->get('/admin/products/new');
check('A PHP file posing as an image is rejected', str_contains($cat->body, 'Only JPEG, PNG or WebP'));
$anon->get('/uploads/products/shell.php.jpg');
check('Uploads are not served from the web root', $anon->status === 404);
$cat->get('/media/customers/' . str_repeat('a', 32) . '.jpg');
check('Private media folders hide missing files (404)', $cat->status === 404);

// ---------------------------------------------------------------- admin verifies payment, audit
echo "\nAdmin workflow and audit\n";
$owner = new Client($base);
$owner->post('/admin/login', ['email' => getenv('KD_TEST_ADMIN_EMAIL'), 'password' => getenv('KD_TEST_ADMIN_PASSWORD')], true, '/admin/login');
check('Super admin can log in', str_ends_with($owner->location(), '/admin'), $owner->location());
$owner->get('/admin/messages');
check('Stored XSS in a contact message is shown as text', !str_contains($owner->body, '<script>alert(1)') && str_contains($owner->body, '&lt;script&gt;'));
$owner->post("/admin/orders/{$orderId}/status", ['status' => 'shipped'], true, "/admin/orders/{$orderId}");
$owner->get("/admin/orders/{$orderId}");
check("Can't skip straight from payment submitted to shipped", str_contains($owner->body, "can&#039;t be changed"));
$owner->post("/admin/orders/{$orderId}/status", ['status' => 'paid'], true, "/admin/orders/{$orderId}");
$owner->get("/admin/orders/{$orderId}");
check('Admin can verify the payment (Paid)', str_contains($owner->body, 'status-paid'));
$owner->get('/admin/audit');
check('Audit log recorded the status change', str_contains($owner->body, 'order.status_changed'));
check('Audit log recorded the denied access', str_contains($owner->body, 'admin.access_denied'));
check('Audit log recorded failed admin logins', str_contains($owner->body, 'admin.login_failed'));

// ---------------------------------------------------------------- admin user lifecycle (joiner / mover / leaver)
echo "\nAdmin user lifecycle\n";
$newEmail = 'orders-' . bin2hex(random_bytes(3)) . '@example.com';
$owner->post('/admin/users', ['name' => 'Order Desk', 'email' => $newEmail, 'role' => 'order_manager', 'job' => '', 'contact' => ''], true, '/admin/users/new');
$owner->get('/admin/users');
preg_match('/Temporary password \(shown once[^:]*\): (Kd-[a-f0-9]{6}-[a-f0-9]{6}\d)/', $owner->body, $m);
$temp = $m[1] ?? '';
check('Super admin creates an order manager with a one-time password', $temp !== '');

$desk = new Client($base);
$desk->post('/admin/login', ['email' => $newEmail, 'password' => $temp], true, '/admin/login');
$desk->get('/admin/orders');
check('New admin is forced to change the temporary password first', str_ends_with($desk->location(), '/admin/profile'), $desk->location());
$desk->post('/admin/profile/password', ['current_password' => $temp, 'new_password' => 'Order-Desk-Pass-9', 'new_password_confirm' => 'Order-Desk-Pass-9'], true, '/admin/profile');
check('After changing it, order manager can open orders', $desk->get('/admin/orders')->status === 200);
check('Order manager is blocked from products (403)', $desk->get('/admin/products')->status === 403);

// Find the new admin's id and deactivate them (leaver): their live session must end immediately.
$owner->get('/admin/users');
preg_match('#' . preg_quote($newEmail, '#') . '.*?/admin/users/(\d+)/edit#s', $owner->body, $m);
$deskId = (int) ($m[1] ?? 0);
$owner->post("/admin/users/{$deskId}", ['name' => 'Order Desk', 'email' => $newEmail, 'role' => 'order_manager', 'job' => '', 'contact' => ''], true, "/admin/users/{$deskId}/edit");
$desk->get('/admin/orders');
check('Deactivating an admin ends their session at once', str_ends_with($desk->location(), '/admin/login'), $desk->location() ?: "HTTP {$desk->status}");

// An admin can't demote or deactivate themselves.
$owner->get('/admin/users');
preg_match_all('#/admin/users/(\d+)/edit#', $owner->body, $all);
$selfId = 0;
foreach ($all[1] as $id) {
    $owner->get("/admin/users/{$id}/edit");
    if (str_contains($owner->body, "change your own role.")) {
        $selfId = (int) $id;
        break;
    }
}
$owner->post("/admin/users/{$selfId}", ['name' => 'Shop Owner', 'email' => getenv('KD_TEST_ADMIN_EMAIL'), 'role' => 'catalog_manager', 'job' => '', 'contact' => '', 'is_active' => '1'], true, "/admin/users/{$selfId}/edit");
$owner->get("/admin/users/{$selfId}/edit");
check("An admin can't change their own role", $selfId > 0 && str_contains($owner->body, 'is-invalid'));
$owner->get('/admin/audit?action=user.deactivated');
check('Deactivation is in the audit log', str_contains($owner->body, 'user.deactivated'));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
