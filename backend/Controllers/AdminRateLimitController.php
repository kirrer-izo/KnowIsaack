<?php

namespace App\Controllers;

use App\Infrastructure\Database\RateLimitRepository;

class AdminRateLimitController
{
    private $rateLimitRepository;

    public function __construct(RateLimitRepository $rateLimitRepository)
    {
        $this->rateLimitRepository = $rateLimitRepository;
    }

    // GET /api/admin/rate-limits
    public function listRateLimits(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? null;
        $action = $_GET['action'] ?? null;

        $result = $this->rateLimitRepository->getPaginatedRateLimits($page, $limit, $search, $action);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'rate_limits' => $result['rate_limits'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($result['total'] / $limit),
            ]
        ]);
        exit;
    }

    // GET /api/admin/rate-limits/export
    public function exportCsv(): void
    {
        $search = $_GET['search'] ?? null;
        $action = $_GET['action'] ?? null;

        $rateLimits = $this->rateLimitRepository->getAllRateLimitsForExport($search, $action);

        $filename = 'rate_limits_export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Headers
        fputcsv($output, ['ID', 'Identifier (IP/Email)', 'Action', 'Attempts', 'First Attempt', 'Last Attempt']);

        foreach ($rateLimits as $limit) {
            fputcsv($output, [
                $limit['id'],
                $limit['identifier'],
                $limit['action'],
                $limit['attempts'],
                $limit['first_attempt_at'],
                $limit['last_attempt_at']
            ]);
        }
        fclose($output);
        exit;
    }
}