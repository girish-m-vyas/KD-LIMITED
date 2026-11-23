<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Controllers\Controller;
use KD\Http\HttpException;
use KD\Http\Request;
use KD\Http\Response;
use KD\Support\Validator;

/**
 * Create / edit / delete for the simple content types. Each type is described
 * once here; table and column names come only from this list, never from input.
 */
final class ContentController extends Controller
{
    public const TYPES = [
        'categories' => [
            'table' => 'categories', 'permission' => 'catalog.manage',
            'plural' => 'Categories', 'singular' => 'category', 'icon' => 'bi-tags',
            'fields' => [
                'title'  => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|text|max:120'],
                'is_top' => ['label' => 'Show first in the shop filters', 'type' => 'checkbox'],
                'image'  => ['label' => 'Image', 'type' => 'image', 'folder' => 'categories'],
            ],
            'columns' => ['image' => 'Image', 'title' => 'Title', 'is_top' => 'Top'],
            'in_use'  => ['products', 'category_id', 'products use this category'],
        ],
        'product-categories' => [
            'table' => 'product_categories', 'permission' => 'catalog.manage',
            'plural' => 'Product types', 'singular' => 'product type', 'icon' => 'bi-diagram-3',
            'fields' => [
                'title'  => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|text|max:120'],
                'is_top' => ['label' => 'Show first in the shop filters', 'type' => 'checkbox'],
                'image'  => ['label' => 'Image', 'type' => 'image', 'folder' => 'categories'],
            ],
            'columns' => ['image' => 'Image', 'title' => 'Title', 'is_top' => 'Top'],
            'in_use'  => ['products', 'product_category_id', 'products use this product type'],
        ],
        'slides' => [
            'table' => 'slides', 'permission' => 'content.manage',
            'plural' => 'Slides', 'singular' => 'slide', 'icon' => 'bi-images',
            'fields' => [
                'name'       => ['label' => 'Name (also the image alt text)', 'type' => 'text', 'rules' => 'required|text|max:120'],
                'image'      => ['label' => 'Image (wide, about 1400×480)', 'type' => 'image', 'folder' => 'slides', 'required' => true],
                'url'        => ['label' => 'Link (a site path like /shop, or an https:// link)', 'type' => 'text', 'rules' => 'url|max:255'],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => 'int|between:0,999'],
            ],
            'columns' => ['image' => 'Image', 'name' => 'Name', 'url' => 'Link', 'sort_order' => 'Order'],
        ],
        'boxes' => [
            'table' => 'boxes', 'permission' => 'content.manage',
            'plural' => 'Home boxes', 'singular' => 'box', 'icon' => 'bi-grid-3x2-gap',
            'fields' => [
                'title'       => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|text|max:120'],
                'description' => ['label' => 'Text', 'type' => 'textarea', 'rules' => 'required|max:400'],
            ],
            'columns' => ['title' => 'Title', 'description' => 'Text'],
        ],
        'terms' => [
            'table' => 'terms', 'permission' => 'content.manage',
            'plural' => 'Terms', 'singular' => 'term', 'icon' => 'bi-file-text',
            'fields' => [
                'title'       => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|text|max:120'],
                'slug'        => ['label' => 'Link name (e.g. refund-policy)', 'type' => 'text', 'rules' => 'required|slug|max:120', 'unique' => true],
                'description' => ['label' => 'Text', 'type' => 'textarea', 'rules' => 'required|max:10000'],
            ],
            'columns' => ['title' => 'Title', 'slug' => 'Link name'],
        ],
    ];

    /** Routes call e.g. index_slides(); split into action + type. */
    public function __call(string $method, array $args): string
    {
        [$action, $slug] = explode('_', $method, 2) + [1 => ''];
        if (!isset(self::TYPES[$slug]) || !in_array($action, ['index', 'create', 'store', 'edit', 'update', 'delete'], true)) {
            throw HttpException::notFound();
        }
        return $this->$action($slug, self::TYPES[$slug], $args[0] ?? []);
    }

    private function index(string $slug, array $type): string
    {
        return $this->view('admin/content/index', [
            'pageTitle' => $type['plural'],
            'slug'      => $slug,
            'type'      => $type,
            'rows'      => $this->db()->fetchAll('SELECT * FROM ' . $type['table'] . ' ORDER BY ' . ($slug === 'slides' ? 'sort_order, id' : 'id')),
        ], 'admin');
    }

    private function create(string $slug, array $type): string
    {
        return $this->view('admin/content/form', ['pageTitle' => 'Add ' . $type['singular'], 'slug' => $slug, 'type' => $type, 'row' => null], 'admin');
    }

    private function edit(string $slug, array $type, array $params): string
    {
        $row = $this->findOr404($type['table'], $params['id']);
        return $this->view('admin/content/form', ['pageTitle' => 'Edit ' . $type['singular'], 'slug' => $slug, 'type' => $type, 'row' => $row], 'admin');
    }

    private function store(string $slug, array $type): string
    {
        [$values, $newImages] = $this->collect($slug, $type, null, "/admin/{$slug}/new");
        $columns = array_keys($values);
        $id = $this->db()->insert(
            'INSERT INTO ' . $type['table'] . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            array_values($values)
        );
        $this->audit(str_replace(' ', '_', $type['singular']) . '.created', $type['table'], $id, array_diff_key($values, $newImages));
        Response::redirect("/admin/{$slug}", 'success', ucfirst($type['singular']) . ' added.');
    }

    private function update(string $slug, array $type, array $params): string
    {
        $row = $this->findOr404($type['table'], $params['id']);
        [$values, $newImages] = $this->collect($slug, $type, $row, "/admin/{$slug}/{$params['id']}/edit");
        $sets = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($values)));
        $this->db()->execute('UPDATE ' . $type['table'] . " SET {$sets} WHERE id = ?", [...array_values($values), $row['id']]);
        foreach ($newImages as $field => $folder) {
            if (!empty($row[$field])) {
                App::uploads()->delete($folder, (string) $row[$field]);
            }
        }
        $changes = [];
        foreach ($values as $k => $v) {
            if ((string) $row[$k] !== (string) $v) {
                $changes[$k] = isset($newImages[$k]) ? 'new image' : [$row[$k], $v];
            }
        }
        $this->audit(str_replace(' ', '_', $type['singular']) . '.updated', $type['table'], (int) $row['id'], $changes);
        Response::redirect("/admin/{$slug}", 'success', ucfirst($type['singular']) . ' saved.');
    }

    private function delete(string $slug, array $type, array $params): string
    {
        $row = $this->findOr404($type['table'], $params['id']);
        if (isset($type['in_use'])) {
            [$table, $column, $message] = $type['in_use'];
            $count = (int) $this->db()->value("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?", [$row['id']]);
            if ($count > 0) {
                Response::redirect("/admin/{$slug}", 'danger', "Can't delete: {$count} {$message}. Move them first.");
            }
        }
        $this->db()->execute('DELETE FROM ' . $type['table'] . ' WHERE id = ?', [$row['id']]);
        foreach ($type['fields'] as $field => $def) {
            if ($def['type'] === 'image' && !empty($row[$field])) {
                App::uploads()->delete($def['folder'], (string) $row[$field]);
            }
        }
        $this->audit(str_replace(' ', '_', $type['singular']) . '.deleted', $type['table'], (int) $row['id'], ['title' => $row['title'] ?? $row['name'] ?? '']);
        Response::redirect("/admin/{$slug}", 'success', ucfirst($type['singular']) . ' deleted.');
    }

    /**
     * Validate input against the type's field list.
     * @return array{0: array<string, mixed>, 1: array<string, string>} [column => value], [image column => folder]
     */
    private function collect(string $slug, array $type, ?array $row, string $back): array
    {
        $rules = [];
        foreach ($type['fields'] as $field => $def) {
            if (isset($def['rules'])) {
                $rules[$field] = $def['rules'];
            }
        }
        $old    = Request::only(array_keys($rules));
        $errors = Validator::validate($_POST, $rules);

        foreach ($type['fields'] as $field => $def) {
            if (!empty($def['unique']) && !isset($errors[$field])) {
                $exists = $this->db()->value('SELECT 1 FROM ' . $type['table'] . " WHERE {$field} = ? AND id <> ?", [$old[$field], $row['id'] ?? 0]);
                if ($exists !== null) {
                    $errors[$field] = 'That ' . strtolower($def['label']) . ' is already used.';
                }
            }
        }
        if ($errors) {
            Response::back($back, $errors, $old);
        }

        $values    = [];
        $newImages = [];
        foreach ($type['fields'] as $field => $def) {
            switch ($def['type']) {
                case 'checkbox':
                    $values[$field] = Request::input($field) === '1' ? 1 : 0;
                    break;
                case 'number':
                    $values[$field] = (int) ($old[$field] ?: 0);
                    break;
                case 'image':
                    $file = $this->optionalImage($field, $def['folder'], $back, $old);
                    if ($file !== null) {
                        $values[$field]    = $file;
                        $newImages[$field] = $def['folder'];
                    } elseif ($row === null) {
                        if (!empty($def['required'])) {
                            Response::back($back, [$field => 'Please choose an image.'], $old);
                        }
                        $values[$field] = '';
                    }
                    break;
                default:
                    $values[$field] = $old[$field];
            }
        }
        return [$values, $newImages];
    }
}
