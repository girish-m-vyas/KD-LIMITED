-- KD-LIMITED schema (SQLite). Used for local development and the test suite.
-- Keep in step with schema.mysql.sql.
PRAGMA foreign_keys = ON;

CREATE TABLE admins (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    name                 TEXT    NOT NULL,
    email                TEXT    NOT NULL UNIQUE COLLATE NOCASE,
    password_hash        TEXT    NOT NULL,
    role                 TEXT    NOT NULL DEFAULT 'catalog_manager',
    contact              TEXT    NOT NULL DEFAULT '',
    job                  TEXT    NOT NULL DEFAULT '',
    about                TEXT    NOT NULL DEFAULT '',
    image                TEXT    NOT NULL DEFAULT '',
    is_active            INTEGER NOT NULL DEFAULT 1,
    must_change_password INTEGER NOT NULL DEFAULT 0,
    last_login_at        TEXT    NULL,
    created_at           TEXT    NOT NULL,
    updated_at           TEXT    NOT NULL
);

CREATE TABLE customers (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    contact       TEXT NOT NULL DEFAULT '',
    city          TEXT NOT NULL DEFAULT '',
    address       TEXT NOT NULL DEFAULT '',
    image         TEXT NOT NULL DEFAULT '',
    created_at    TEXT NOT NULL,
    updated_at    TEXT NOT NULL
);

CREATE TABLE categories (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    title  TEXT    NOT NULL,
    is_top INTEGER NOT NULL DEFAULT 0,
    image  TEXT    NOT NULL DEFAULT ''
);

CREATE TABLE product_categories (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    title  TEXT    NOT NULL,
    is_top INTEGER NOT NULL DEFAULT 0,
    image  TEXT    NOT NULL DEFAULT ''
);

CREATE TABLE products (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    product_category_id INTEGER NOT NULL REFERENCES product_categories(id) ON DELETE RESTRICT,
    category_id         INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    title               TEXT    NOT NULL,
    image1              TEXT    NOT NULL DEFAULT '',
    image2              TEXT    NOT NULL DEFAULT '',
    image3              TEXT    NOT NULL DEFAULT '',
    price_paise         INTEGER NOT NULL CHECK (price_paise >= 0),
    sale_price_paise    INTEGER NULL CHECK (sale_price_paise IS NULL OR sale_price_paise >= 0),
    label               TEXT    NOT NULL DEFAULT '',
    keywords            TEXT    NOT NULL DEFAULT '',
    description         TEXT    NOT NULL DEFAULT '',
    created_at          TEXT    NOT NULL,
    updated_at          TEXT    NOT NULL
);
CREATE INDEX idx_products_pcat ON products(product_category_id);
CREATE INDEX idx_products_cat  ON products(category_id);

CREATE TABLE slides (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL,
    image      TEXT    NOT NULL,
    url        TEXT    NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE boxes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT NOT NULL,
    description TEXT NOT NULL
);

CREATE TABLE terms (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT NOT NULL,
    slug        TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL
);

CREATE TABLE orders (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    invoice_no  TEXT    NOT NULL UNIQUE,
    total_paise INTEGER NOT NULL CHECK (total_paise >= 0),
    status      TEXT    NOT NULL,
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);
CREATE INDEX idx_orders_customer ON orders(customer_id);

CREATE TABLE order_items (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id         INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id       INTEGER NULL REFERENCES products(id) ON DELETE SET NULL,
    product_title    TEXT    NOT NULL,
    unit_price_paise INTEGER NOT NULL,
    qty              INTEGER NOT NULL CHECK (qty > 0),
    size             TEXT    NOT NULL
);
CREATE INDEX idx_items_order ON order_items(order_id);

CREATE TABLE payments (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id     INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    amount_paise INTEGER NOT NULL,
    mode         TEXT    NOT NULL,
    ref_no       TEXT    NOT NULL,
    code         TEXT    NOT NULL DEFAULT '',
    payment_date TEXT    NOT NULL,
    created_at   TEXT    NOT NULL
);
CREATE INDEX idx_payments_order ON payments(order_id);

CREATE TABLE contact_messages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    subject    TEXT NOT NULL,
    message    TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    key_hash     TEXT    NOT NULL,
    attempted_at INTEGER NOT NULL
);
CREATE INDEX idx_attempts_key ON login_attempts(key_hash, attempted_at);

CREATE TABLE audit_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id   INTEGER NULL REFERENCES admins(id) ON DELETE SET NULL,
    action     TEXT    NOT NULL,
    entity     TEXT    NULL,
    entity_id  INTEGER NULL,
    details    TEXT    NOT NULL DEFAULT '{}',
    ip         TEXT    NULL,
    created_at TEXT    NOT NULL
);
CREATE INDEX idx_audit_created ON audit_log(created_at);
