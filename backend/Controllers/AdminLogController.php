<?php

namespace App\Controllers;

use App\Infrastructure\Database\LoginActivityRepository;

class AdminLogController
{
    private $logRepository;

    public function __construct(LoginActivityRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    // GET /api/admin/logs
    public function listLogs(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 10);
        $search = $_GET['search'] ?? null;
        $success = isset($_GET['success']) ? filter_var($_GET['success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $result = $this->logRepository->getPaginatedLogs($page, $limit, $search, $success, $dateFrom, $dateTo);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'logs' => $result['logs'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($result['total'] / $limit),
            ]
        ]);
        exit;
    }

    // GET /api/admin/logs/export
    public function exportCsv(): void
    {
        $search = $_GET['search'] ?? null;
        $success = isset($_GET['success']) ? filter_var($_GET['success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $logs = $this->logRepository->getAllLogsForExport($search, $success, $dateFrom, $dateTo);

        $filename = 'login_logs_export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Headers
        fputcsv($output, ['ID', 'User ID', 'User Name', 'Attempted Email', 'IP Address', 'User Agent', 'Success', 'Created At']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['user_id'],
                $log['user_name'] ?? '', // null for failed attempts with non-existent email
                $log['attempted_email'],
                $log['ip_address'],
                $log['user_agent'],
                $log['success'] ? 'Yes' : 'No',
                $log['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
}