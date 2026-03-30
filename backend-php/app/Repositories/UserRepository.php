<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, password_hash, role, created_at, updated_at FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'passwordHash' => $row['password_hash'],
            'role' => $row['role'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }

    public function createDefaultAdminIfMissing(string $email, string $plainPassword): void
    {
        if ($email === '' || $plainPassword === '') {
            return;
        }

        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        if ($hash === false) {
            return;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password_hash, role) VALUES ('admin', :email, :password_hash, 'admin') ON CONFLICT (email) DO NOTHING"
        );

        $stmt->execute([
            'email' => $email,
            'password_hash' => $hash,
        ]);
    }
}
