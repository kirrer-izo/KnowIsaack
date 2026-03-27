<?php

namespace App\Infrastructure\Database;

use App\Utils\DateTimeHelper;
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


    // Get paginated rate limit records with optional search and filters
    public function getPaginatedRateLimits(int $page, int $limit, ?string $search = null, ?string $action = null): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT id, identifier, action, attempts, first_attempt_at, last_attempt_at FROM rate_limits WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (identifier ILIKE :search OR action ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($action) {
            $sql .= " AND action = :action";
            $params['action'] = $action;
        }

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM ({$sql}) AS filtered");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated rows
        $sql .= " ORDER BY last_attempt_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rateLimits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert created_at to UTC ISO
        foreach ($rateLimits as &$rateLimit) {
            $rateLimit = DateTimeHelper::convertTimestamps($rateLimit, ['first_attempt_at', 'last_attempt_at']);
        }

        return [
            'total' => $total,
            'rate_limits' => $rateLimits
        ];
    }

    
    //  Get all rate limit records for CSV export (with same filters)
    public function getAllRateLimitsForExport(?string $search = null, ?string $action = null): array
    {
        $sql = "SELECT id, identifier, action, attempts, first_attempt_at, last_attempt_at FROM rate_limits WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (identifier ILIKE :search OR action ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($action) {
            $sql .= " AND action = :action";
            $params['action'] = $action;
        }

        $sql .= " ORDER BY last_attempt_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rateLimits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert created_at to UTC ISO
        foreach ($rateLimits as &$rateLimit) {
            $rateLimit = DateTimeHelper::convertTimestamps($rateLimit, ['first_attempt_at', 'last_attempt_at']);
        }

        return $rateLimits;
    }
}