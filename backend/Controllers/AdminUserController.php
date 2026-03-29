<?php

namespace App\Controllers;

use App\Infrastructure\Database\UserRepository;
use App\Infrastructure\Database\EmailVerificationRepository;
use App\Services\UserService;

/**
 * AdminUserController
 *
 * GET    /api/admin/users                          → index()
 * POST   /api/admin/users                          → create()
 * GET    /api/admin/users/{id}                     → show()
 * PUT    /api/admin/users/{id}                     → update()
 * DELETE /api/admin/users/{id}                     → destroy()
 * POST   /api/admin/users/{id}/resend-verification → resendVerification()
 * GET    /api/admin/users/export                   → export()
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
        $this->userRepository              = $userRepository;
        $this->emailVerificationRepository = $emailVerificationRepository;
        $this->userService                 = $userService;
    }

    // ── GET /api/admin/users ─────────────────────────────────────────────────

    public function index(): void
    {
        header('Content-Type: application/json');

        $page     = max(1, (int) ($_GET['page']  ?? 1));
        $limit    = min(100, max(1, (int) ($_GET['limit'] ?? 10)));
        $search   = trim($_GET['search'] ?? '') ?: null;
        $verified = isset($_GET['verified'])
            ? filter_var($_GET['verified'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $result     = $this->userRepository->getPaginatedUsers($page, $limit, $search, $verified);
        $totalPages = max(1, (int) ceil($result['total'] / $limit));

        echo json_encode([
            'status' => 'success',
            'data'   => [
                'users'       => $result['users'],
                'total'       => $result['total'],
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $totalPages,
            ],
        ]);
        exit;
    }

    // ── POST /api/admin/users ────────────────────────────────────────────────
    // Admin creates a new user account directly (bypasses registration flow).
    // Body: { name, email, password, email_verified? }

    public function create(): void
    {
        header('Content-Type: application/json');

        $body     = $this->parseJsonBody();
        $name     = trim($body['name']     ?? '');
        $email    = trim($body['email']    ?? '');
        $password = $body['password']      ?? '';

        if (empty($name)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Name is required']);
            exit;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'A valid email address is required']);
            exit;
        }
        if (strlen($password) < 8) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
            exit;
        }
        if ($this->userRepository->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists']);
            exit;
        }

        $verified = isset($body['email_verified'])
            ? filter_var($body['email_verified'], FILTER_VALIDATE_BOOLEAN)
            : false;

        $this->userRepository->adminCreate([
            'name'           => $name,
            'email'          => $email,
            'password_hash'  => password_hash($password, PASSWORD_BCRYPT),
            'email_verified' => $verified,
        ]);

        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'User created successfully']);
        exit;
    }

    // ── GET /api/admin/users/{id} ────────────────────────────────────────────

    public function show(int $id): void
    {
        header('Content-Type: application/json');

        $user = $this->userRepository->findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        unset($user['password_hash']);
        echo json_encode(['status' => 'success', 'data' => $user]);
        exit;
    }

    // ── PUT /api/admin/users/{id} ────────────────────────────────────────────

    public function update(int $id): void
    {
        header('Content-Type: application/json');

        $user = $this->userRepository->findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        $body  = $this->parseJsonBody();
        $name  = trim($body['name']  ?? '');
        $email = trim($body['email'] ?? '');

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

        $existing = $this->userRepository->findByEmail($email);
        if ($existing && (int) $existing['id'] !== $id) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Email is already in use by another account']);
            exit;
        }

        $updated = $this->userRepository->updateProfile($id, $name, $email);
        if (!$updated) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update user']);
            exit;
        }

        if (isset($body['email_verified'])) {
            $this->userRepository->setEmailVerified(
                $id,
                filter_var($body['email_verified'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
        exit;
    }

    // ── DELETE /api/admin/users/{id} ─────────────────────────────────────────

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
            $this->userService->resendVerificationEmail($user);
            echo json_encode(['status' => 'success', 'message' => 'Verification email sent']);
        } catch (\Exception $e) {
            error_log('Resend verification error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to send verification email']);
        }
        exit;
    }

    // ── GET /api/admin/users/export ──────────────────────────────────────────

    public function export(): void
    {
        $search   = trim($_GET['search']   ?? '') ?: null;
        $verified = isset($_GET['verified'])
            ? filter_var($_GET['verified'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $result = $this->userRepository->getPaginatedUsers(1, PHP_INT_MAX, $search, $verified);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Name', 'Email', 'Verified', 'Created At']);
        foreach ($result['users'] as $user) {
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
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
}