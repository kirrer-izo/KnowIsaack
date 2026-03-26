<?php

namespace App\Infrastructure\Database;

use PDO;

class LoginActivityRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Insert a login activity record.
    public function create(?int $userId, ?string $attemptedEmail, string $ip, string $userAgent, bool $success): void {
        $stmt = $this->pdo->prepare("INSERT INTO login_activities (user_id, attempted_email, ip_address, user_agent, success) VALUES (:user_id, :attempted_email, :ip, :user_agent, :success)");
        $stmt->execute([
            'user_id' => $userId,
            'attempted_email' => $attemptedEmail,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'success' => $success ? 1 : 0,
        ]);
    }

    // Get recent login activities for a user 
    public function getRecentForUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM login_activities WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit" );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Count failed login attemots in the last 24 hours
    public function countFailedLast24Hours(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE success = false AND created_at > NOW() - INTERVAL '24 hours'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}