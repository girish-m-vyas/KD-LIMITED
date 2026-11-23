<?php
declare(strict_types=1);

/**
 * Loads clearly fake demo data: categories, products with generated images,
 * slides, home boxes and terms. Adds a demo customer only if you ask for one:
 *
 *   php bin/seed.php
 *   php bin/seed.php --demo-customer     prints a random password for demo@example.com
 *
 * Never run this against a live shop's database.
 */

require __DIR__ . '/bootstrap-cli.php';

use KD\Database;
use KD\Support\Password;

$opts = cli_options($argv);
$db   = Database::fromEnv();

if ((int) $db->value('SELECT COUNT(*) FROM products') > 0) {
    fwrite(STDERR, "The database already has products. Seed only an empty database.\n");
    exit(1);
}

$uploads = APP_ROOT . '/storage/uploads';
$copy = static function (string $file, string $folder) use ($uploads): string {
    $src = APP_ROOT . '/database/demo-images/' . $file;
    if (!is_file($src)) {
        return '';
    }
    @mkdir($uploads . '/' . $folder, 0750, true);
    $name = bin2hex(random_bytes(16)) . '.jpg';
    copy($src, $uploads . '/' . $folder . '/' . $name);
    return $name;
};

$now = now();

$db->transaction(function (Database $db) use ($copy, $now): void {
    $cats = [];
    foreach ([['Men', 1], ['Women', 1], ['Kids', 0]] as [$title, $top]) {
        $cats[$title] = $db->insert('INSERT INTO categories (title, is_top, image) VALUES (?, ?, ?)', [$title, $top, '']);
    }
    $pcats = [];
    foreach ([['T-shirts', 1], ['Shirts', 1], ['Jackets', 1], ['Dresses', 0], ['Jeans', 0], ['Footwear', 0]] as [$title, $top]) {
        $pcats[$title] = $db->insert('INSERT INTO product_categories (title, is_top, image) VALUES (?, ?, ?)', [$title, $top, '']);
    }

    // [image, product type, category, title, price ₹, sale price ₹ or null, label, keywords, description]
    $products = [
        ['p01', 'T-shirts', 'Men',   'Classic Crew Tee – Indigo',      699,  null, 'new',  'tshirt cotton men', "Soft 180 GSM combed cotton.\nRegular fit, ribbed crew neck."],
        ['p02', 'T-shirts', 'Women', 'Relaxed Tee – Terracotta',       749,  599,  'sale', 'tshirt women cotton', "Relaxed fit with dropped shoulders.\n100% cotton, pre-washed."],
        ['p03', 'Shirts',   'Men',   'Oxford Shirt – Off White',       1499, null, '',     'shirt formal oxford', "Button-down collar, single chest pocket.\nCotton Oxford weave."],
        ['p04', 'Shirts',   'Men',   'Linen Shirt – Olive',            1799, 1399, 'sale', 'shirt linen summer', "Breathable linen blend for hot days.\nRegular fit."],
        ['p05', 'Jackets',  'Men',   'Tailored Blazer – Charcoal',     3999, null, 'new',  'blazer jacket formal', "Two-button blazer with notch lapels.\nFully lined."],
        ['p06', 'Jackets',  'Women', 'Suede-Look Jacket – Tan',        3499, 2799, 'sale', 'jacket women winter', "Soft suede-look finish with front pockets."],
        ['p07', 'Dresses',  'Women', 'A-Line Midi Dress – Berry',      1999, null, 'new',  'dress midi women', "Flared A-line skirt with a waist seam.\nViscose blend."],
        ['p08', 'Dresses',  'Women', 'Sleeveless Dress – Teal',        1799, null, '',     'dress summer', "Easy sleeveless cut for everyday wear."],
        ['p09', 'Jeans',    'Men',   'Straight Jeans – Mid Blue',      1999, null, '',     'jeans denim men', "Straight leg, mid rise.\n98% cotton, 2% elastane."],
        ['p10', 'Jeans',    'Women', 'Slim Jeans – Black',             2199, 1699, 'sale', 'jeans denim women black', "Slim fit with stretch for comfort."],
        ['p11', 'Footwear', 'Men',   'Canvas Sneakers – Mustard',      1599, null, 'new',  'shoes sneakers canvas', "Lightweight canvas upper with rubber sole."],
        ['p12', 'Footwear', 'Kids',  'Kids Sneakers – Royal Blue',     999,  null, '',     'kids shoes sneakers', "Hook-and-loop strap for easy wear."],
        ['p13', 'T-shirts', 'Kids',  'Kids Tee – Sunflower',           449,  null, '',     'kids tshirt', "Soft cotton tee in a bright yellow."],
        ['p14', 'Dresses',  'Kids',  'Kids Party Dress – Violet',      1299, 999,  'sale', 'kids dress party', "Twirl-ready skirt with a soft lining."],
    ];
    foreach ($products as [$img, $pcat, $cat, $title, $price, $sale, $label, $keywords, $desc]) {
        $db->insert(
            'INSERT INTO products (product_category_id, category_id, title, image1, image2, image3, price_paise, sale_price_paise, label, keywords, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$pcats[$pcat], $cats[$cat], $title, $copy($img . 'a.jpg', 'products'), $copy($img . 'b.jpg', 'products'), '', $price * 100, $sale !== null ? $sale * 100 : null, $label, $keywords, $desc, $now, $now]
        );
    }

    foreach ([['New season', 's1.jpg', '/shop?pcat=' . $pcats['Jackets']], ['Summer edit', 's2.jpg', '/shop?pcat=' . $pcats['Dresses']], ['Sale', 's3.jpg', '/shop?q=']] as $i => [$name, $img, $url]) {
        $db->insert('INSERT INTO slides (name, image, url, sort_order) VALUES (?, ?, ?, ?)', [$name, $copy($img, 'slides'), $url === '/shop?q=' ? '/shop' : $url, $i]);
    }

    foreach ([
        ['Best prices', 'Fair everyday prices and seasonal sales on selected styles.'],
        ['Easy returns', 'Changed your mind? Return unworn items within 15 days.'],
        ['Quality checked', 'Every item is checked before it leaves our warehouse.'],
    ] as [$title, $desc]) {
        $db->insert('INSERT INTO boxes (title, description) VALUES (?, ?)', [$title, $desc]);
    }

    foreach ([
        ['Rules and regulations', 'rules', "These are sample terms for the demo shop. Replace them with your own before going live.\n\nOrders are confirmed once payment has been verified."],
        ['Refund policy', 'refunds', "Unworn items can be returned within 15 days of delivery.\nRefunds are made to the original payment method within 7 working days."],
        ['Promotions', 'promotions', "Sale prices apply while stocks last and can't be combined with other offers."],
    ] as [$title, $slug, $desc]) {
        $db->insert('INSERT INTO terms (title, slug, description) VALUES (?, ?, ?)', [$title, $slug, $desc]);
    }
});

echo "Demo catalogue loaded: 14 products, 3 slides, 3 boxes, 3 terms.\n";

if (isset($opts['demo-customer'])) {
    $password = 'Demo-' . bin2hex(random_bytes(5));
    $db->insert(
        'INSERT INTO customers (name, email, password_hash, contact, city, address, image, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        ['Demo Customer', 'demo@example.com', Password::hash($password), '+91 90000 00000', 'Thane', '1 Example Road, Demo Nagar', '', $now, $now]
    );
    echo "Demo customer: demo@example.com / {$password}  (shown once; not stored anywhere in plain text)\n";
}
