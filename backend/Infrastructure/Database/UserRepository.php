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
        $stmt = $this->pdo->query("SELECY COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    // Get number of users with verified email
    public function countVerified(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE email_verified = true");
        return (int) $stmt->fetchColumn();
    }
}