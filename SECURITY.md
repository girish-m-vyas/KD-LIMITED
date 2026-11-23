# Security notes

This rebuild follows the *KD-LIMITED Security Audit and Rebuild Plan*. Each finding is listed below with where it is fixed and how it is tested.

## Confirmed findings from the audit

| # | Finding (version 1) | Fix in version 2 | Tested by |
|---|---|---|---|
| 1 | SQL injection in admin login | `AdminAuth::attempt()` uses prepared statements and `password_verify()` | `AdminAuthTest`, smoke test "SQL injection" |
| 2 | SQL injection in customer login | `CustomerAuth::attempt()`, same approach | `CustomerAuthTest::testSqlInjectionLoginFails` |
| 3 | No prepared statements anywhere | All SQL goes through `src/Database.php` with `?` placeholders and native (not emulated) prepares. Route IDs can only be digits. | `DatabaseTest`, `RouterTest` |
| 4 | Plain-text passwords | `password_hash()` / `password_verify()` with automatic rehash, plus a password policy (10+ characters, letters and digits, not common, not the email) | `PasswordTest`, `OrderFlowTest::testRegisterStoresHashNotPassword` |
| 5 | Admin credentials in public `kd.sql` | `kd.sql` removed. The schema files hold no data, and the first admin is created from the CLI (`bin/create-admin.php`). | — |
| 6 | Customer data in public dump | Demo data is clearly fake, and `.gitignore` blocks dumps, databases and uploads. | — |
| 7 | DB connects as `root` with no password | Credentials come from `.env` (git-ignored). The app refuses `root`, and the README shows a least-privilege grant. | — |
| 8 | Deletes and updates run from GET links | Every state change is a POST route with a CSRF token. GET routes never change data. | `RouterTest::testWrongMethodIs405`, smoke test "CSRF" |
| 9 | Three copies of the DB config | One `.env` and one `Database` class | — |

## Issues the audit flagged to verify

| Issue | Fix |
|---|---|
| No CSRF tokens | `Kernel` rejects **every** POST without a valid token (HTTP 419), so a form can't forget it. |
| Stored and reflected XSS | All template output goes through `e()`. Rich-text (TinyMCE) descriptions are now plain text. A strict Content-Security-Policy (`script-src 'self'`, no inline scripts or styles) is a second layer. |
| Unsafe image uploads | Files must decode as real JPEG/PNG/WebP. They are re-encoded with GD (which strips embedded code), renamed randomly and stored in `storage/uploads/` outside the web root. They're served only through `/media/…`, with an allow-listed folder and file name. Customer and admin photos are private. |
| Weak sessions | Strict mode, HttpOnly, SameSite=Lax, Secure on HTTPS (`__Host-` cookie), a new ID on login, logout and password change, a 30-minute idle timeout for admins (2 hours for customers) and a 12-hour absolute limit |
| No role checks in admin | `Rbac` with three roles and least-privilege permissions. Every admin route declares its permission. The role is re-read from the database on each request, so deactivation and role changes apply immediately. Denied attempts are written to the audit log. |
| IDOR on customer pages | Every customer query is scoped to the session's customer ID. Another customer's order returns 404. |
| No brute-force protection | 5 failures per account, or 30 per IP, in 15 minutes locks login. Unknown emails take the same time and show the same message as wrong passwords. Contact and registration forms are rate-limited too. |
| Raw MySQL errors shown | `display_errors` is off in production. Errors are logged with a reference ID, and the user sees a generic page. |
| `edit_css.php` let admins write files | Removed. No admin feature writes to code or asset files. |
| Old Bootstrap 3 / jQuery 3.3 | Replaced with Bootstrap 5.3.8, bundled locally (no CDN, no jQuery). |

## Business-logic fixes found during the rebuild

- **Customers could mark their own order complete.** `customer/confirm.php` set the status to "Complete". Now a customer can only submit payment details (status *Payment submitted*). The amount must equal the order total, and an admin must verify it.
- **Order status jumps.** Statuses follow a fixed transition table (`OrderService::TRANSITIONS`). For example, an order can't ship before it is paid, and a cancelled order stays cancelled.
- **Orders could be created for any customer ID.** `order.php?c_id=` trusted the URL. Now the customer comes from the session.
- **Cart keyed by a spoofable IP header.** The cart now lives in the session and stores no prices. Prices are always read from the database at checkout.
- **Open redirect** on `?next=`: only local paths are accepted.
- **Customer deletion** used to lose order history. Customers with orders are now anonymised and their order records kept.
- **Admin accounts** are deactivated, not deleted. The last super admin can't be removed, nobody can change their own role, and new or reset accounts get a one-time temporary password that must be changed.

## Audit log

The `audit_log` table records admin logins (successful, failed and locked), logouts, denied access, and every create, update or delete in the admin panel, with before/after values for important fields, the IP address and the time. The admin panel has no way to edit or delete it.

## Still your responsibility when deploying

- Serve the site over HTTPS, with `public/` as the only web root.
- Keep `.env` out of git and readable only by the web server user.
- Back up the database, and keep the backups private.
- Change the password of any account that used the version 1 credentials. They remain visible in the old repository's git history.
- Keep PHP and MySQL/MariaDB up to date.
