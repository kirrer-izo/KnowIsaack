<?php

namespace App\Infrastructure\Database;

use App\Utils\DateTimeHelper;
use PDO;

class EmailVerificationRepository {
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Store a new verification token for a user
    public function createToken(int $user_id, string $token, string $expires_at): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
        $stmt->execute([
            'user_id' => $user_id,
            'token' => $token,
            'expires_at' => $expires_at
        ]);
    }

    // Find a verification record by token — returns null if not found
    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM email_verifications WHERE token = :token");
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result = DateTimeHelper::convertTimestamps($result, ['created_at', 'expires_at']);
        }
        return $result ?: null;
    }

    // Delete all verification tokens for a user — called after successful verification
    public function deleteByUserId(int $user_id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM email_verifications WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
    }

}