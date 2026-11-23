# KD-LIMITED

A small clothing e-commerce site in plain PHP 8 and Bootstrap 5: storefront, customer accounts with offline payment, and an admin panel with role-based access and an audit log.

Version 2 is a full rebuild of the original project. Every finding from the security audit is fixed. [SECURITY.md](SECURITY.md) maps each finding to its fix and lists what was tested.

## Features

**Storefront.** Home page with slider and highlights, a shop with type/category filters, search and pages, product pages with a size picker and gallery, a cart, checkout, a contact form and a terms page.

**Customer account.** Register, log in, order history, offline payment submission (UPI, bank transfer, cheque or cash deposit), edit profile, change password and delete account.

**Admin panel.** Dashboard, products, product types, categories, slides, home boxes, terms, orders with a status workflow, payments, customers, contact messages, admin users with roles, own profile, and an audit log.

## Requirements

- PHP 8.2 or newer with `pdo_mysql` (or `pdo_sqlite`), `gd`, `fileinfo` and `mbstring`
- MySQL 8 or MariaDB 10.6+ in production. SQLite works for a quick local demo.
- No Composer packages are needed. Bootstrap 5.3.8 and Bootstrap Icons are bundled in `public/assets/vendor`.

## Quick start (local demo, no database server)

```bash
cp .env.example .env
# in .env set:  APP_ENV=local   APP_DEBUG=true   DB_DRIVER=sqlite

php bin/migrate.php                 # creates storage/database.sqlite
php bin/seed.php --demo-customer    # fake catalogue + a demo customer (password printed once)
php bin/create-admin.php            # asks for your admin email and password

php -S 127.0.0.1:8080 -t public public/index.php
```

Open http://127.0.0.1:8080 for the shop and http://127.0.0.1:8080/admin for the admin panel.

## Production setup (MySQL / MariaDB)

1. **Point the web server's document root at `public/`.** Nothing outside it should be reachable from the web: code, `.env`, the database and uploads all live outside it.
   - Apache: `public/.htaccess` is included (needs `mod_rewrite`).
   - Nginx: `root /path/to/kd-limited/public;` and `try_files $uri /index.php?$query_string;`
   - XAMPP: create a virtual host whose DocumentRoot is the `public` folder (don't serve the project folder itself).
2. **Create the database and a least-privilege user.** Run this as a MySQL admin once:
   ```sql
   CREATE DATABASE kd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'kd_app'@'localhost' IDENTIFIED BY 'a-long-random-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON kd.* TO 'kd_app'@'localhost';
   ```
   Load the tables with a privileged account (the app user can't create tables):
   ```bash
   mysql -u root -p kd < database/schema.mysql.sql
   ```
3. **Configure `.env`.** Copy `.env.example` to `.env`, then set `DB_USER=kd_app`, `DB_PASS`, `APP_ENV=production` and `APP_DEBUG=false`. The app refuses to connect as `root`.
4. **Create the first admin:** `php bin/create-admin.php`. There is no default admin account.
5. **Make `storage/` writable by the web server user.** Keep it unreadable for everyone else, for example `chmod -R 750 storage`.
6. **Serve over HTTPS.** Cookies then get the `Secure` flag and HSTS is sent. If TLS ends at a proxy, set `APP_FORCE_HTTPS=true` and `TRUSTED_PROXIES`.

## Admin roles

| Role | Can do |
|---|---|
| Super admin | Everything, including admin users and the audit log |
| Catalogue manager | Products, product types, categories, slides, home boxes, terms |
| Order manager | Orders, payments, customers, contact messages |

New admins get a one-time temporary password and must change it at first login. Admins are deactivated, not deleted. The last active super admin can't be removed, and nobody can change their own role.

## Order flow

`Awaiting payment → Payment submitted → Paid → Shipped → Delivered` (or `Cancelled`)

A customer can only *submit* payment details, and the amount has to match the order total. An admin checks the reference against the bank or UPI statement before marking the order as paid. In the old site, customers could mark their own orders complete.

## Tests

```bash
php tests/run.php                   # unit and integration tests (in-memory SQLite)
```

`tests/http/security-smoke.php` runs 56 attack checks over HTTP: SQL injection, CSRF, IDOR, price tampering, XSS, open redirect, session fixation, brute force, role bypass and malicious uploads. Run it against a **fresh local development database only** (it registers test accounts, and sign-up rate limits apply on repeat runs):

```bash
php -S 127.0.0.1:8080 -t public public/index.php &
KD_TEST_ADMIN_EMAIL=… KD_TEST_ADMIN_PASSWORD=… \
KD_TEST_CATALOG_EMAIL=… KD_TEST_CATALOG_PASSWORD=… \
php tests/http/security-smoke.php http://127.0.0.1:8080
```

## Project layout

```
bin/          CLI: migrate, seed, create-admin
database/     MySQL and SQLite schemas, demo images (no real data)
public/       web root: index.php, assets
src/          application code (Database, Auth, Services, Controllers, routes)
storage/      uploads, logs, SQLite database (git-ignored)
templates/    PHP views (all output escaped with e())
tests/        test runner, unit, integration and HTTP security tests
```

## Upgrading from version 1

Version 1 stored passwords in plain text, and its `kd.sql` was published in the public repository. Treat every password in it as compromised. Don't import the old customer or admin data. Start from the new schema, then re-enter products through the admin panel (or with a one-off import script), and ask any real customers to register again.
