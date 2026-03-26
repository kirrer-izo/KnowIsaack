<?php

namespace App\Controllers;

use App\Infrastructure\Database\UserRepository;

class UserProfileController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // GET /api/user/profile
    public function getProfile(): void
    {
        // Ensure user is logged in
        if (empty($_SESSION['authenticated'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // Get user data from session
        $userData = $_SESSION['db_user'] ?? null;

        if (!$userData) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        // Fetch fresh user data from database
        $user = $this->userRepository->findById($userData['id']);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        // Return safe profile data (exclude password_hash)
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified' => $user['email_verified'],
                'created_at' => $user['created_at']
            ]
        ]);
        exit;
    }

    // PUT /api/user/profile
    public function updateProfile(): void
    {
        // Ensure user is logged in
        if (empty($_SESSION['authenticated'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $userData = $_SESSION['db_user'] ?? null;

        if (!$userData) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        // Read input
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');

        // Validate
        if (empty($name) || empty($email)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Name and email are required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            exit;
        }

        // Check if email is already taken by another user
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser && $existingUser['id'] !== $userData['id']) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email already in use']);
            exit;
        }

        // Update profile
        $updated = $this->userRepository->updateProfile($userData['id'], $name, $email);

        if ($updated) {
            // Update session data
            $_SESSION['db_user']['name'] = $name;
            $_SESSION['db_user']['email'] = $email;

            echo json_encode([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'name' => $name,
                    'email' => $email
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
        }
        exit;
    }

    // PUT /api/user/password
    public function updatePassword(): void
    {
        // Ensure user is logged in
        if (empty($_SESSION['authenticated'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $userData = $_SESSION['db_user'] ?? null;

        if (!$userData) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        // Read input
        $input = json_decode(file_get_contents('php://input'), true);
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        // Validate
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All password fields are required']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'New passwords do not match']);
            exit;
        }

        // Validate password strength (same as registration)
        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
            exit;
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one uppercase letter']);
            exit;
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one lowercase letter']);
            exit;
        }
        if (!preg_match('/\d/', $newPassword)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one number']);
            exit;
        }
        if (!preg_match('/[@$!%*?&]/', $newPassword)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one special character']);
            exit;
        }

        // Verify current password
        $user = $this->userRepository->findById($userData['id']);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
            exit;
        }

        // Hash new password and update
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updated = $this->userRepository->updatePassword($userData['id'], $newPasswordHash);

        if ($updated) {
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
        }
        exit;
    }
}