<?php

namespace App\Infrastructure\Database;

use PDO;

class RateLimitRepository {

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Find record by identifier and action
    public function find(string $identifier, string $action): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * from rate_limits WHERE identifier = :identifier AND action = :action");
        $stmt->execute([
            'identifier' => $identifier,
            'action' => $action
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    // Insert and new record with attempts = 1
    public function create(string $identifier, string $action): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO rate_limits (identifier, action,  attempts, first_attempt_at, last_attempt_at) VALUES (:identifier, :action, 1, NOW(), NOW())");
        $stmt->execute([
            'identifier' => $identifier,
            'action' => $action
        ]);
    }

    // Increament attempts and update last_attempt_at
    public function increment(string $identifier, string $action): void
    {
        $stmt = $this->pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt_at = NOW() WHERE identifier= :identifier AND action = :action");
        $stmt->execute([
            'identifier' => $identifier,
            'action' => $action
        ]);
    }

    // Reset attempts to 1 and update first_attempt_at and last_attempt_at to NOW
    public function reset(string $identifier, string $action): void
    {
        $stmt = $this->pdo->prepare("UPDATE rate_limits SET attempts = 1, first_attempt_at = NOW(), last_attempt_at = NOW() WHERE identifier= :identifier AND action = :action");
        $stmt->execute([
            'identifier' => $identifier,
            'action' => $action
        ]);
    }

    // Delete records older than the given cutoff time
    public function deleteOld(string $identifier, string $action, string $cutoff): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE identifier = :identifier AND action = :action AND first_attempt_at < :cutoff");
        $stmt->execute([
            'identifier' => $identifier,
            'action' => $action,
            'cutoff' => $cutoff
        ]);
    }
}