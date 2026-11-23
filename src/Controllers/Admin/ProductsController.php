<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Controllers\Controller;
use KD\Http\Request;
use KD\Http\Response;
use KD\Support\Money;
use KD\Support\Validator;

final class ProductsController extends Controller
{
    public const LABELS = ['' => 'None', 'new' => 'New', 'sale' => 'Sale'];
    private const FIELDS = ['title', 'product_category_id', 'category_id', 'price', 'sale_price', 'label', 'keywords', 'description'];

    public function index(): string
    {
        $q      = mb_substr(Request::query('q'), 0, 80);
        $params = [];
        $where  = '';
        if ($q !== '') {
            $where    = " WHERE p.title LIKE ? ESCAPE '!'";
            $params[] = '%' . strtr($q, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        }
        return $this->view('admin/products/index', [
            'pageTitle' => 'Products',
            'q'         => $q,
            'products'  => $this->db()->fetchAll(
                'SELECT p.*, pc.title AS pcat_title, c.title AS cat_title FROM products p
                 JOIN product_categories pc ON pc.id = p.product_category_id
                 JOIN categories c ON c.id = p.category_id' . $where . ' ORDER BY p.id DESC',
                $params
            ),
        ], 'admin');
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(array $params): string
    {
        return $this->form($this->findOr404('products', $params['id']));
    }

    private function form(?array $product): string
    {
        return $this->view('admin/products/form', [
            'pageTitle' => $product ? 'Edit product' : 'Add product',
            'product'   => $product,
            'pcats'     => $this->db()->fetchAll('SELECT id, title FROM product_categories ORDER BY title'),
            'cats'      => $this->db()->fetchAll('SELECT id, title FROM categories ORDER BY title'),
            'labels'    => self::LABELS,
        ], 'admin');
    }

    public function store(): string
    {
        $data = $this->validated('/admin/products/new', true);
        $old  = Request::only(self::FIELDS);
        $img1 = $this->optionalImage('image1', 'products', '/admin/products/new', $old);
        $img2 = $this->optionalImage('image2', 'products', '/admin/products/new', $old);
        $img3 = $this->optionalImage('image3', 'products', '/admin/products/new', $old);
        if ($img1 === null) {
            Response::back('/admin/products/new', ['image1' => 'A main image is required.'], $old);
        }
        $now = now();
        $id  = $this->db()->insert(
            'INSERT INTO products (product_category_id, category_id, title, image1, image2, image3, price_paise, sale_price_paise, label, keywords, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$data['product_category_id'], $data['category_id'], $data['title'], $img1, $img2 ?? '', $img3 ?? '', $data['price_paise'], $data['sale_price_paise'], $data['label'], $data['keywords'], $data['description'], $now, $now]
        );
        $this->audit('product.created', 'product', $id, ['title' => $data['title'], 'price_paise' => $data['price_paise']]);
        Response::redirect('/admin/products', 'success', 'Product added.');
    }

    public function update(array $params): string
    {
        $product = $this->findOr404('products', $params['id']);
        $back    = '/admin/products/' . $params['id'] . '/edit';
        $data    = $this->validated($back, false);
        $old     = Request::only(self::FIELDS);

        $images = [];
        foreach (['image1', 'image2', 'image3'] as $field) {
            $new = $this->optionalImage($field, 'products', $back, $old);
            $remove = $field !== 'image1' && Request::input('remove_' . $field) === '1';
            if ($new !== null) {
                $images[$field] = $new;
            } elseif ($remove) {
                $images[$field] = '';
            }
        }

        $sql = 'UPDATE products SET product_category_id = ?, category_id = ?, title = ?, price_paise = ?, sale_price_paise = ?, label = ?, keywords = ?, description = ?, updated_at = ?';
        $values = [$data['product_category_id'], $data['category_id'], $data['title'], $data['price_paise'], $data['sale_price_paise'], $data['label'], $data['keywords'], $data['description'], now()];
        foreach ($images as $field => $file) {
            $sql .= ", {$field} = ?"; // $field comes from the fixed list above
            $values[] = $file;
        }
        $values[] = $product['id'];
        $this->db()->execute($sql . ' WHERE id = ?', $values);

        foreach ($images as $field => $_file) {
            if ($product[$field] !== '') {
                App::uploads()->delete('products', (string) $product[$field]);
            }
        }

        $changes = [];
        foreach (['title', 'price_paise', 'sale_price_paise', 'label'] as $k) {
            if ((string) $product[$k] !== (string) $data[$k]) {
                $changes[$k] = [$product[$k], $data[$k]];
            }
        }
        $this->audit('product.updated', 'product', (int) $product['id'], $changes + ($images ? ['images' => array_keys($images)] : []));
        Response::redirect('/admin/products', 'success', 'Product saved.');
    }

    public function delete(array $params): string
    {
        $product = $this->findOr404('products', $params['id']);
        $this->db()->execute('DELETE FROM products WHERE id = ?', [$product['id']]);
        foreach (['image1', 'image2', 'image3'] as $f) {
            if ($product[$f] !== '') {
                App::uploads()->delete('products', (string) $product[$f]);
            }
        }
        $this->audit('product.deleted', 'product', (int) $product['id'], ['title' => $product['title']]);
        Response::redirect('/admin/products', 'success', 'Product deleted. Past orders keep their own copy of its name and price.');
    }

    /** @return array<string, mixed> */
    private function validated(string $back, bool $creating): array
    {
        $old    = Request::only(self::FIELDS);
        $errors = Validator::validate($_POST, [
            'title'               => 'required|text|max:200',
            'product_category_id' => 'required|int',
            'category_id'         => 'required|int',
            'price'               => 'required|money',
            'sale_price'          => 'money',
            'label'               => 'in:new,sale',
            'keywords'            => 'text|max:255',
            'description'         => 'max:5000',
        ]);
        $pcat = (int) $old['product_category_id'];
        $cat  = (int) $old['category_id'];
        if (!isset($errors['product_category_id']) && $this->db()->value('SELECT 1 FROM product_categories WHERE id = ?', [$pcat]) === null) {
            $errors['product_category_id'] = 'Choose a product type.';
        }
        if (!isset($errors['category_id']) && $this->db()->value('SELECT 1 FROM categories WHERE id = ?', [$cat]) === null) {
            $errors['category_id'] = 'Choose a category.';
        }
        $price = Money::parse($old['price']);
        $sale  = $old['sale_price'] !== '' ? Money::parse($old['sale_price']) : null;
        if ($old['label'] === 'sale' && $sale === null && !isset($errors['sale_price'])) {
            $errors['sale_price'] = 'A sale price is required when the label is Sale.';
        }
        if ($sale !== null && $price !== null && $sale >= $price) {
            $errors['sale_price'] = 'The sale price must be lower than the regular price.';
        }
        if ($errors) {
            Response::back($back, $errors, $old);
        }
        return [
            'title'               => $old['title'],
            'product_category_id' => $pcat,
            'category_id'         => $cat,
            'price_paise'         => $price,
            'sale_price_paise'    => $sale,
            'label'               => $old['label'],
            'keywords'            => $old['keywords'],
            'description'         => $old['description'],
        ];
    }
}
