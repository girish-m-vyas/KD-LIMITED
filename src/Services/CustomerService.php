<?php
declare(strict_types=1);

namespace KD\Services;

use KD\Database;
use KD\Support\Password;

final class CustomerService
{
    public function __construct(private readonly Database $db) {}

    /**
     * @param array{name: string, email: string, password: string, contact: string, city: string, address: string} $data
     */
    public function register(array $data, ?string $image): int
    {
        $email = strtolower(trim($data['email']));
        if ($this->db->value('SELECT 1 FROM customers WHERE LOWER(email) = ?', [$email]) !== null) {
            throw new \DomainException('An account with that email already exists. Try logging in.');
        }
        $now = now();
        return $this->db->insert(
            'INSERT INTO customers (name, email, password_hash, contact, city, address, image, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [trim($data['name']), $email, Password::hash($data['password']), trim($data['contact']), trim($data['city']), trim($data['address']), $image ?? '', $now, $now]
        );
    }

    /** @param array{name: string, email: string, contact: string, city: string, address: string} $data */
    public function update(int $id, array $data, ?string $image): void
    {
        $email = strtolower(trim($data['email']));
        if ($this->db->value('SELECT 1 FROM customers WHERE LOWER(email) = ? AND id <> ?', [$email, $id]) !== null) {
            throw new \DomainException('That email is already used by another account.');
        }
        $sql    = 'UPDATE customers SET name = ?, email = ?, contact = ?, city = ?, address = ?, updated_at = ?';
        $params = [trim($data['name']), $email, trim($data['contact']), trim($data['city']), trim($data['address']), now()];
        if ($image !== null) {
            $sql     .= ', image = ?';
            $params[] = $image;
        }
        $params[] = $id;
        $this->db->execute($sql . ' WHERE id = ?', $params);
    }

    /** Returns an error message, or null on success. */
    public function changePassword(int $id, string $current, string $new): ?string
    {
        $row = $this->db->fetch('SELECT email, password_hash FROM customers WHERE id = ?', [$id]);
        if ($row === null || !Password::verify($current, (string) $row['password_hash'])) {
            return 'Your current password is incorrect.';
        }
        if ($error = Password::policyError($new, (string) $row['email'])) {
            return $error;
        }
        if (Password::verify($new, (string) $row['password_hash'])) {
            return 'Choose a password you have not used here before.';
        }
        $this->db->execute('UPDATE customers SET password_hash = ?, updated_at = ? WHERE id = ?', [Password::hash($new), now(), $id]);
        return null;
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $hash = $this->db->value('SELECT password_hash FROM customers WHERE id = ?', [$id]);
        return is_string($hash) && Password::verify($password, $hash);
    }

    /**
     * Customers with orders are anonymised so order and payment records stay
     * intact for accounting; customers without orders are removed.
     *
     * @return string|null the old image file name, so the caller can delete it
     */
    public function delete(int $id): ?string
    {
        return $this->db->transaction(function (Database $db) use ($id): ?string {
            $image = $db->value('SELECT image FROM customers WHERE id = ?', [$id]);
            $hasOrders = $db->value('SELECT 1 FROM orders WHERE customer_id = ? LIMIT 1', [$id]) !== null;
            if ($hasOrders) {
                $db->execute(
                    'UPDATE customers SET name = ?, email = ?, password_hash = ?, contact = ?, city = ?, address = ?, image = ?, updated_at = ? WHERE id = ?',
                    ['Deleted customer', 'deleted-' . $id . '-' . bin2hex(random_bytes(4)) . '@invalid.local', Password::hash(bin2hex(random_bytes(32))), '', '', '', '', now(), $id]
                );
            } else {
                $db->execute('DELETE FROM customers WHERE id = ?', [$id]);
            }
            return is_string($image) && $image !== '' ? $image : null;
        });
    }
}
