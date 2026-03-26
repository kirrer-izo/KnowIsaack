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
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM login_activities WHERE success = false AND created_at > NOW() - INTERVAL '24 hours'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }


    //  Get paginated login logs with optional filters
    public function getPaginatedLogs(int $page, int $limit,?string $search = null, ?bool $success = null, ?string $dateFrom = null, ?string $dateTo = null ): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT l.id, l.user_id, u.name as user_name, l.attempted_email, l.ip_address, l.user_agent, l.success, l.created_at FROM login_activities l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (u.name ILIKE :search OR u.email ILIKE :search OR l.attempted_email ILIKE :search OR l.ip_address ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($success !== null) {
            $sql .= " AND l.success = :success";
            $params['success'] = $success ? 'true' : 'false';
        }

        if ($dateFrom) {
            $sql .= " AND DATE(l.created_at) >= :dateFrom";
            $params['dateFrom'] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(l.created_at) <= :dateTo";
            $params['dateTo'] = $dateTo;
        }

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM ({$sql}) AS filtered");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated rows
        $sql .= " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'logs' => $logs
        ];
    }


    //  Get all logs for CSV export (with same filters)
    public function getAllLogsForExport(?string $search = null,?bool $success = null,?string $dateFrom = null,?string $dateTo = null): array
    {
        $sql = "SELECT l.id, l.user_id, u.name as user_name, l.attempted_email, l.ip_address, l.user_agent, l.success, l.created_at FROM login_activities l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (u.name ILIKE :search OR u.email ILIKE :search OR l.attempted_email ILIKE :search OR l.ip_address ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($success !== null) {
            $sql .= " AND l.success = :success";
            $params['success'] = $success ? 'true' : 'false';
        }

        if ($dateFrom) {
            $sql .= " AND DATE(l.created_at) >= :dateFrom";
            $params['dateFrom'] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(l.created_at) <= :dateTo";
            $params['dateTo'] = $dateTo;
        }

        $sql .= " ORDER BY l.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}