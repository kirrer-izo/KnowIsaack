<?php

namespace App\Infrastructure\Database;

use PDO;
use PDOException;

class UserRepository {
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data): void 
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (email, name, password_hash) VALUES (:email, :name, :password_hash)");
        $stmt->execute([
            'email'         => $data['email'],
            'name'          => $data['name'],
            'password_hash' => $data['password_hash'],
        ]);
    }

    public function findByEmail(string $email): ?array 
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function markEmailVerified(int $user_id): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET email_verified = TRUE WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
    }

    // Admin-only: explicitly set email_verified to true or false
    public function setEmailVerified(int $id, bool $verified): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET email_verified = :verified, updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute([
            'id'       => $id,
            'verified' => $verified ? 'true' : 'false',
        ]);
    }

    public function updatePassword(int $user_id, string $password_hash): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $user_id, 'password_hash' => $password_hash]);
    }

    public function countAll(): int 
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    public function countVerified(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE email_verified = true");
        return (int) $stmt->fetchColumn();
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, email, email_verified, created_at FROM users ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginatedUsers(int $page, int $limit, ?string $search = null, ?bool $verified = null): array
    {
        $offset = ($page - 1) * $limit;

        $sql    = "SELECT id, name, email, email_verified, created_at FROM users WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (name ILIKE :search OR email ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($verified !== null) {
            $sql .= " AND email_verified = :verified";
            $params['verified'] = $verified ? 'true' : 'false';
        }

        // Total count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM ({$sql}) AS filtered");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Paginated rows
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'total' => $total,
            'users' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function deleteUser(int $id): bool
    {
        $this->pdo->beginTransaction();
        try {
            $tables = ['email_verification', 'password_resets', 'remember_tokens', 'login_activities'];
            foreach ($tables as $table) {
                $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $id]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log('Error deleting user: ' . $e->getMessage());
            return false;
        }
    }

    public function updateProfile(int $userId, string $name, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id"
        );
        return $stmt->execute([
            'id'    => $userId,
            'name'  => $name,
            'email' => $email,
        ]);
    }
}