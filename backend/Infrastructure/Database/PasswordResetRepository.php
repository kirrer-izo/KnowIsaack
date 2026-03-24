<?php

namespace App\Infrastructure\Database;

use PDO;

class PasswordResetRepository {
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Store a new password reset token for a user
    public function createToken(int $user_id, string $token, string $expires_at): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
        $stmt->execute([
            'user_id' => $user_id,
            'token' => $token,
            'expires_at' => $expires_at
        ]);
    }

    // Find a password reset record by token — returns null if not found
    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    // Delete all password reset tokens for a user — called after successful password reset
    public function deleteByUserId(int $user_id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
    }

}