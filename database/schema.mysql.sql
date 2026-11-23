-- KD-LIMITED schema (MySQL 8 / MariaDB 10.6+).
-- Keep in step with schema.sqlite.sql.
--
-- Run as a privileged user once, then let the app connect as `kd_app` (see README).

SET NAMES utf8mb4;

CREATE TABLE admins (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(120)  NOT NULL,
    email                VARCHAR(190)  NOT NULL UNIQUE,
    password_hash        VARCHAR(255)  NOT NULL,
    role                 VARCHAR(40)   NOT NULL DEFAULT 'catalog_manager',
    contact              VARCHAR(30)   NOT NULL DEFAULT '',
    job                  VARCHAR(120)  NOT NULL DEFAULT '',
    about                TEXT          NOT NULL,
    image                VARCHAR(64)   NOT NULL DEFAULT '',
    is_active            TINYINT(1)    NOT NULL DEFAULT 1,
    must_change_password TINYINT(1)    NOT NULL DEFAULT 0,
    last_login_at        DATETIME      NULL,
    created_at           DATETIME      NOT NULL,
    updated_at           DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    contact       VARCHAR(30)  NOT NULL DEFAULT '',
    city          VARCHAR(120) NOT NULL DEFAULT '',
    address       TEXT         NOT NULL,
    image         VARCHAR(64)  NOT NULL DEFAULT '',
    created_at    DATETIME     NOT NULL,
    updated_at    DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title  VARCHAR(120) NOT NULL,
    is_top TINYINT(1)   NOT NULL DEFAULT 0,
    image  VARCHAR(64)  NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_categories (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title  VARCHAR(120) NOT NULL,
    is_top TINYINT(1)   NOT NULL DEFAULT 0,
    image  VARCHAR(64)  NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_category_id INT UNSIGNED NOT NULL,
    category_id         INT UNSIGNED NOT NULL,
    title               VARCHAR(200) NOT NULL,
    image1              VARCHAR(64)  NOT NULL DEFAULT '',
    image2              VARCHAR(64)  NOT NULL DEFAULT '',
    image3              VARCHAR(64)  NOT NULL DEFAULT '',
    price_paise         INT UNSIGNED NOT NULL,
    sale_price_paise    INT UNSIGNED NULL,
    label               VARCHAR(20)  NOT NULL DEFAULT '',
    keywords            VARCHAR(255) NOT NULL DEFAULT '',
    description         TEXT         NOT NULL,
    created_at          DATETIME     NOT NULL,
    updated_at          DATETIME     NOT NULL,
    KEY idx_products_pcat (product_category_id),
    KEY idx_products_cat (category_id),
    CONSTRAINT fk_products_pcat FOREIGN KEY (product_category_id) REFERENCES product_categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_products_cat  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE slides (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    image      VARCHAR(64)  NOT NULL,
    url        VARCHAR(255) NOT NULL DEFAULT '',
    sort_order INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE boxes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(120) NOT NULL,
    description TEXT         NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE terms (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(120) NOT NULL,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    description TEXT         NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    invoice_no  VARCHAR(20)  NOT NULL UNIQUE,
    total_paise INT UNSIGNED NOT NULL,
    status      VARCHAR(30)  NOT NULL,
    created_at  DATETIME     NOT NULL,
    updated_at  DATETIME     NOT NULL,
    KEY idx_orders_customer (customer_id),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id         INT UNSIGNED NOT NULL,
    product_id       INT UNSIGNED NULL,
    product_title    VARCHAR(200) NOT NULL,
    unit_price_paise INT UNSIGNED NOT NULL,
    qty              INT UNSIGNED NOT NULL,
    size             VARCHAR(5)   NOT NULL,
    KEY idx_items_order (order_id),
    CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    amount_paise INT UNSIGNED NOT NULL,
    mode         VARCHAR(30)  NOT NULL,
    ref_no       VARCHAR(60)  NOT NULL,
    code         VARCHAR(60)  NOT NULL DEFAULT '',
    payment_date DATE         NOT NULL,
    created_at   DATETIME     NOT NULL,
    KEY idx_payments_order (order_id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    subject    VARCHAR(190) NOT NULL,
    message    TEXT         NOT NULL,
    created_at DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    key_hash     CHAR(64)     NOT NULL,
    attempted_at INT UNSIGNED NOT NULL,
    KEY idx_attempts_key (key_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_id   INT UNSIGNED NULL,
    action     VARCHAR(60)  NOT NULL,
    entity     VARCHAR(60)  NULL,
    entity_id  INT UNSIGNED NULL,
    details    TEXT         NOT NULL,
    ip         VARCHAR(45)  NULL,
    created_at DATETIME     NOT NULL,
    KEY idx_audit_created (created_at),
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
