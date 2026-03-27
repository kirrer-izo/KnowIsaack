<?php

namespace App\Infrastructure\Database;

use App\Utils\DateTimeHelper;
use PDO;

class RememberTokenRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Store a new token (already hashed)
    public function create(int $userId, string $hashedToken, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)"
        );
        $stmt->execute([
            'user_id' => $userId,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
        ]);
    }

    // Find token record by hashed token
    public function findByToken(string $hashedToken): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM remember_tokens WHERE token = :token");
        $stmt->execute(['token' => $hashedToken]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result = DateTimeHelper::convertTimestamps($result, ['created_at', 'expires_at']);
        }
        return $result ?: null;
    }

    // Delete a specific token (logout)
    public function deleteByToken(string $hashedToken): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE token = :token");
        $stmt->execute(['token' => $hashedToken]);
    }

    // Delete all tokens for a user (e.g., password change)
    public function deleteByUserId(int $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
    }

    // Clean up expired tokens
    public function deleteExpired(): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
        $stmt->execute();
    }
}