<?php

namespace App\Controllers;

use App\Infrastructure\Database\UserRepository;
use App\Infrastructure\Database\EmailVerificationRepository;
use App\Services\UserService;

/**
 * AdminUserController
 *
 * Handles admin-only user management endpoints.
 *
 * Routes (all guarded by guard.php — GitHub admin only):
 *   GET    /api/admin/users                        → index()
 *   GET    /api/admin/users/{id}                   → show()
 *   PUT    /api/admin/users/{id}                   → update()
 *   DELETE /api/admin/users/{id}                   → destroy()
 *   POST   /api/admin/users/{id}/resend-verification → resendVerification()
 *   GET    /api/admin/users/export                 → export()
 */
class AdminUserController
{
    private UserRepository $userRepository;
    private EmailVerificationRepository $emailVerificationRepository;
    private UserService $userService;

    public function __construct(
        UserRepository $userRepository,
        EmailVerificationRepository $emailVerificationRepository,
        UserService $userService
    ) {
        $this->userRepository             = $userRepository;
        $this->emailVerificationRepository = $emailVerificationRepository;
        $this->userService                = $userService;
    }

    // ── GET /api/admin/users ─────────────────────────────────────────────────
    // Returns a paginated, searchable, filterable list of users.
    //
    // Query params:
    //   page     (int,    default 1)
    //   limit    (int,    default 10, max 100)
    //   search   (string, searches name + email)
    //   verified (bool,   'true' | 'false')

    public function index(): void
    {
        header('Content-Type: application/json');

        $page     = max(1, (int) ($_GET['page']  ?? 1));
        $limit    = min(100, max(1, (int) ($_GET['limit'] ?? 10)));
        $search   = trim($_GET['search']   ?? '') ?: null;
        $verified = isset($_GET['verified'])
            ? filter_var($_GET['verified'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $result     = $this->userRepository->getPaginatedUsers($page, $limit, $search, $verified);
        $totalPages = (int) ceil($result['total'] / $limit);

        echo json_encode([
            'status' => 'success',
            'data'   => [
                'users'       => $result['users'],
                'total'       => $result['total'],
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => max(1, $totalPages),
            ],
        ]);
        exit;
    }

    // ── GET /api/admin/users/{id} ────────────────────────────────────────────
    // Returns a single user by ID.

    public function show(int $id): void
    {
        header('Content-Type: application/json');

        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        // Strip sensitive fields before returning
        unset($user['password_hash']);

        echo json_encode(['status' => 'success', 'data' => $user]);
        exit;
    }

    // ── PUT /api/admin/users/{id} ────────────────────────────────────────────
    // Updates a user's name, email, and optionally their verification status.
    //
    // Body (JSON):
    //   name           (string, required)
    //   email          (string, required)
    //   email_verified (bool,   optional)

    public function update(int $id): void
    {
        header('Content-Type: application/json');

        $user = $this->userRepository->findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        $body = $this->parseJsonBody();

        $name  = trim($body['name']  ?? '');
        $email = trim($body['email'] ?? '');

        // Validate
        if (empty($name) || empty($email)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Name and email are required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            exit;
        }

        // Guard against duplicate email (exclude current user)
        $existing = $this->userRepository->findByEmail($email);
        if ($existing && (int) $existing['id'] !== $id) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Email is already in use by another account']);
            exit;
        }

        // Update name + email
        $updated = $this->userRepository->updateProfile($id, $name, $email);
        if (!$updated) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update user']);
            exit;
        }

        // Optionally toggle email_verified
        if (isset($body['email_verified'])) {
            $verified = filter_var($body['email_verified'], FILTER_VALIDATE_BOOLEAN);
            $this->userRepository->setEmailVerified($id, $verified);
        }

        echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
        exit;
    }

    // ── DELETE /api/admin/users/{id} ─────────────────────────────────────────
    // Permanently deletes a user and all associated data.

    public function destroy(int $id): void
    {
        header('Content-Type: application/json');

        $user = $this->userRepository->findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        $deleted = $this->userRepository->deleteUser($id);

        if (!$deleted) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
        exit;
    }

    // ── POST /api/admin/users/{id}/resend-verification ───────────────────────
    // Resends the email verification link to an unverified user.

    public function resendVerification(int $id): void
    {
        header('Content-Type: application/json');

        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        if ($user['email_verified']) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'User email is already verified']);
            exit;
        }

        try {
            $this->userService->resendVerificationEmail($user['id']);
            echo json_encode(['status' => 'success', 'message' => 'Verification email sent']);
        } catch (\Exception $e) {
            error_log('Resend verification error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to send verification email']);
        }
        exit;
    }

    // ── GET /api/admin/users/export ──────────────────────────────────────────
    // Streams a CSV of all users (honours search + verified filter).

    public function export(): void
    {
        $search   = trim($_GET['search']   ?? '') ?: null;
        $verified = isset($_GET['verified'])
            ? filter_var($_GET['verified'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        // Fetch all matching rows (no pagination for export)
        $result = $this->userRepository->getPaginatedUsers(1, PHP_INT_MAX, $search, $verified);
        $users  = $result['users'];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Name', 'Email', 'Verified', 'Created At']);

        foreach ($users as $user) {
            fputcsv($out, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['email_verified'] ? 'Yes' : 'No',
                $user['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}