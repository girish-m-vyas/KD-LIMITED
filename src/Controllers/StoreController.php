<?php
declare(strict_types=1);

namespace KD\Controllers;

use KD\App;
use KD\Http\HttpException;
use KD\Http\Request;
use KD\Http\Response;
use KD\Services\Cart;
use KD\Services\OrderService;
use KD\Support\Validator;

final class StoreController extends Controller
{
    private const PER_PAGE = 9;

    public function home(): string
    {
        return $this->view('store/home', [
            'pageTitle' => 'Home',
            'slides'    => $this->db()->fetchAll('SELECT * FROM slides ORDER BY sort_order, id LIMIT 6'),
            'boxes'     => $this->db()->fetchAll('SELECT * FROM boxes ORDER BY id LIMIT 3'),
            'products'  => $this->db()->fetchAll('SELECT * FROM products ORDER BY id DESC LIMIT 8'),
        ]);
    }

    public function shop(): string
    {
        $pcat = Request::queryInt('pcat');
        $cat  = Request::queryInt('cat');
        $q    = mb_substr(Request::query('q'), 0, 80);
        $page = Request::queryInt('page') ?? 1;

        $where  = [];
        $params = [];
        if ($pcat !== null) {
            $where[]  = 'product_category_id = ?';
            $params[] = $pcat;
        }
        if ($cat !== null) {
            $where[]  = 'category_id = ?';
            $params[] = $cat;
        }
        if ($q !== '') {
            // LIKE wildcards in the search text are escaped so they match literally.
            $like     = '%' . strtr($q, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $where[]  = "(title LIKE ? ESCAPE '!' OR keywords LIKE ? ESCAPE '!')";
            $params[] = $like;
            $params[] = $like;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db()->value('SELECT COUNT(*) FROM products' . $whereSql, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $products = $this->db()->fetchAll(
            'SELECT * FROM products' . $whereSql . ' ORDER BY id DESC LIMIT ? OFFSET ?',
            [...$params, self::PER_PAGE, ($page - 1) * self::PER_PAGE]
        );

        return $this->view('store/shop', [
            'pageTitle'   => 'Shop',
            'products'    => $products,
            'total'       => $total,
            'page'        => $page,
            'pages'       => $pages,
            'filters'     => ['pcat' => $pcat, 'cat' => $cat, 'q' => $q],
            'pcats'       => $this->db()->fetchAll('SELECT * FROM product_categories ORDER BY is_top DESC, title'),
            'cats'        => $this->db()->fetchAll('SELECT * FROM categories ORDER BY is_top DESC, title'),
        ]);
    }

    public function product(array $params): string
    {
        $product = $this->db()->fetch(
            'SELECT p.*, pc.title AS pcat_title, c.title AS cat_title FROM products p
             JOIN product_categories pc ON pc.id = p.product_category_id
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?',
            [$params['id']]
        );
        if ($product === null) {
            throw HttpException::notFound();
        }
        $related = $this->db()->fetchAll(
            'SELECT * FROM products WHERE product_category_id = ? AND id <> ? ORDER BY id DESC LIMIT 3',
            [$product['product_category_id'], $product['id']]
        );
        return $this->view('store/product', [
            'pageTitle' => $product['title'],
            'product'   => $product,
            'related'   => $related,
            'sizes'     => Cart::SIZES,
        ]);
    }

    public function cartAdd(): string
    {
        $productId = Request::inputInt('product_id') ?? 0;
        $back      = '/product/' . $productId;
        $errors = Validator::validate($_POST, [
            'product_id' => 'required|int',
            'qty'        => 'required|int|between:1,' . Cart::MAX_QTY,
            'size'       => 'required|in:' . implode(',', array_keys(Cart::SIZES)),
        ]);
        if ($productId < 1 || $this->db()->value('SELECT 1 FROM products WHERE id = ?', [$productId]) === null) {
            throw HttpException::notFound();
        }
        if ($errors) {
            Response::back($back, $errors, Request::only(['qty', 'size']));
        }
        try {
            Cart::add($productId, (int) Request::input('qty'), Request::input('size'));
        } catch (\InvalidArgumentException $e) {
            Response::redirect($back, 'danger', $e->getMessage());
        }
        Response::redirect('/cart', 'success', 'Added to your cart.');
    }

    public function cart(): string
    {
        return $this->view('store/cart', ['pageTitle' => 'Cart', ...$this->cartLines()]);
    }

    public function cartUpdate(): string
    {
        $qtys    = $_POST['qty'] ?? [];
        $removes = $_POST['remove'] ?? [];
        if (is_array($qtys)) {
            foreach ($qtys as $key => $qty) {
                if (is_string($key) && is_string($qty) && preg_match('/^\d{1,2}$/', $qty)) {
                    Cart::update($key, (int) $qty);
                }
            }
        }
        if (is_array($removes)) {
            foreach ($removes as $key) {
                if (is_string($key)) {
                    Cart::remove($key);
                }
            }
        }
        Response::redirect('/cart', 'success', 'Cart updated.');
    }

    public function checkout(): string
    {
        if (App::customerAuth()->current() === null) {
            Response::redirect('/login?next=/checkout', 'info', 'Log in or create an account to check out.');
        }
        $lines = $this->cartLines();
        if ($lines['lines'] === []) {
            Response::redirect('/cart', 'info', 'Your cart is empty.');
        }
        return $this->view('store/checkout', ['pageTitle' => 'Checkout', ...$lines]);
    }

    public function placeOrder(): string
    {
        $customerId = (int) App::customerAuth()->id();
        try {
            $orderId = App::orders()->place($customerId, Cart::items());
        } catch (\DomainException $e) {
            Response::redirect('/cart', 'danger', $e->getMessage());
        }
        Cart::clear();
        Response::redirect('/account/orders/' . $orderId, 'success', 'Order placed. Pay offline, then submit your payment details below.');
    }

    public function terms(): string
    {
        return $this->view('store/terms', [
            'pageTitle' => 'Terms and conditions',
            'terms'     => $this->db()->fetchAll('SELECT * FROM terms ORDER BY id'),
        ]);
    }

    public function contact(): string
    {
        return $this->view('store/contact', ['pageTitle' => 'Contact us']);
    }

    public function contactSend(): string
    {
        $old    = Request::only(['name', 'email', 'subject', 'message']);
        $errors = Validator::validate($_POST, [
            'name'    => 'required|text|max:120',
            'email'   => 'required|email|max:190',
            'subject' => 'required|text|max:190',
            'message' => 'required|min:10|max:4000',
        ]);
        if ($errors) {
            Response::back('/contact', $errors, $old);
        }
        $key = 'contact-ip:' . Request::ip();
        if (App::limiter()->isLocked($key)) {
            Response::back('/contact', ['message' => 'You have sent several messages recently. Please try again later.'], $old);
        }
        App::limiter()->hit($key);
        $this->db()->insert(
            'INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, ?)',
            [$old['name'], strtolower($old['email']), $old['subject'], $old['message'], now()]
        );
        Response::redirect('/contact', 'success', 'Thanks, your message has been sent. We will reply by email.');
    }

    /** Cart lines joined with live product data. Prices always come from the database. */
    private function cartLines(): array
    {
        $lines = [];
        $total = 0;
        foreach (Cart::items() as $key => $item) {
            $product = $this->db()->fetch('SELECT id, title, image1, price_paise, sale_price_paise, label FROM products WHERE id = ?', [$item['product_id']]);
            if ($product === null) {
                Cart::remove($key); // product was deleted
                continue;
            }
            $unit    = OrderService::unitPrice($product);
            $sub     = $unit * $item['qty'];
            $total  += $sub;
            $lines[] = ['key' => $key, 'product' => $product, 'qty' => $item['qty'], 'size' => $item['size'], 'unit' => $unit, 'subtotal' => $sub];
        }
        return ['lines' => $lines, 'total' => $total];
    }
}
