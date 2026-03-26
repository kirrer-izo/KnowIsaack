<?php

namespace App\Controllers;

use App\Infrastructure\Database\UserRepository;
use App\Services\UserService;

class AdminUserController
{
    private $userRepository;
    private $userService;

    public function __construct(UserRepository $userRepository, UserService $userService)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    // GET /api/admin/users
    public function listUsers(): void
    {
    
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 10);
        $search = $_GET['search'] ?? null;
        $verified = isset($_GET['verified']) ? filter_var($_GET['verified'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $result = $this->userRepository->getPaginatedUsers($page, $limit, $search, $verified);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'users' => $result['users'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($result['total'] / $limit),
            ]
        ]);
        exit;
    }

    // POST /api/admin/users/{id}/resend-verification
    public function resendVerification(int $id): void
    {
        try {
            $this->userService->resendVerificationEmail($id);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Verification email resent']);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // DELETE /api/admin/users/{id}
    public function deleteUser(int $id): void
    {
        $deleted = $this->userRepository->deleteUser($id);
        if ($deleted) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'User deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
        }
        exit;
    }

    // GET /api/admin/users/export
    public function exportCsv(): void
    {
        // Get all users (no pagination)
        $users = $this->userRepository->getAllUsers();

        $filename = 'users_export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Headers
        fputcsv($output, ['ID', 'Name', 'Email', 'Verified', 'Created At']);

        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['email_verified'] ? 'Yes' : 'No',
                $user['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
}