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
            'email' => $data['email'],
            'name' => $data['name'],
            'password_hash' => $data['password_hash']
        ]);
    }

    public function findByEmail(string $email): ?array 
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    
    // Find a user by their ID — returns null if not found
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    // Mark a user's email as verified
    public function markEmailVerified(int $user_id): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET email_verified = TRUE WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
    }

    // Enables user to reset password
    public function updatePassword(int $user_id, string $password_hash): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
        $stmt->execute(['id' => $user_id, 'password_hash' => $password_hash]);
    }

    // Get total number of registered users
    public function countAll(): int 
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    // Get number of users with verified email
    public function countVerified(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE email_verified = true");
        return (int) $stmt->fetchColumn();
    }

    // Get all users
    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, email, email_verified, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get paginated users with search and verification filter
    public function getPaginatedUsers(int $page, int $limit, ?string $search = null, ?bool $verified = null): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT id, name, email, email_verified, created_at FROM users WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (name ILIKE :search OR email ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($verified != null) {
            $sql .= " AND email_verified = :verified";
            $params['verified'] = $verified ? 'true' : 'false';
        }

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM ({$sql}) AS filtered");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated rows
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'users' => $users
        ];
    }

    // Delete a user and all associated data
    public function deleteUser(int $id): bool
    {
        // Begin transaction
        $this->pdo->beginTransaction();
        try {
            // Delete from tables that reference users
            $tables = ['email_verification', 'password_resets', 'remember_tokens', 'login_activities'];
            foreach ($tables as $table) {
                $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $id]);
            }

            // FInally delete the user
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
}