<?php

namespace App\Services;

use App\Infrastructure\Database\EmailVerificationRepository;
use App\Infrastructure\Database\PasswordResetRepository;
use App\Infrastructure\Database\UserRepository;
use App\Infrastructure\Mail\ResendMailer;

class UserService {
    private $userRepository;
    private $emailVerificationRepository;
    private $mailer;
    private $passwordResetRepository;

    public function __construct(UserRepository $userRepository, EmailVerificationRepository $emailVerificationRepository, ResendMailer $mailer, PasswordResetRepository $passwordResetRepository)
    {
        $this->userRepository = $userRepository;
        $this->emailVerificationRepository = $emailVerificationRepository;
        $this->mailer = $mailer;
        $this->passwordResetRepository = $passwordResetRepository;
    }

    public function register(string $name, string $email, string $password): void
    {   
        // Checking Password Strength
        if (strlen($password) < 8) {
            throw new \Exception("password_too_short");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new \Exception("password_no_uppercase");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new \Exception("password_no_lowercase");
        }
        if (!preg_match('/\d/', $password)) {
            throw new \Exception("password_no_number");
        }
        if (!preg_match('/[@$!%*?&]/', $password)) {
            throw new \Exception("password_no_special");
        }

        // Check if email is already taken
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new \Exception("email_taken");
        }

        // Hash password and create user
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);

        // Fetch the newly created user to get their ID
        $user = $this->userRepository->findByEmail($email);

        // Generate a secure random token - 32 bytes = 64 hex characters
        $token = bin2hex(random_bytes(32));

        // Token expires in 24 hours
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Store the token
        $this->emailVerificationRepository->createToken($user['id'], $token, $expires_at);

        // Send verification email
        $this->mailer->sendVerificationEmail($email, $name, $token);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);
        // Use same error for both missing user and wrong password — prevents user enumeration
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new \Exception("invalid_credentials");
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];
    }

    public function verifyEmail(string $token): void 
    {
        // Find the token record
        $record = $this->emailVerificationRepository->findByToken($token);

        if (!$record) {
            throw new \Exception("invalid_token");
        }

        // Check if token is expired
        if (strtotime($record['expires_at']) < time()) {
            throw new \Exception("token_expired");
        }

        // Mark user as verified
        $this->userRepository->markEmailVerified($record['user_id']);

        // Delete the used token
        $this->emailVerificationRepository->deleteByUserId($record['user_id']);
    }

    public function forgotPassword(string $email) :void
    {
        $user = $this->userRepository->findByEmail($email);

        if(!$user) {
            throw new \Exception("email_not_found_or_unverified");
        }

        $token = bin2hex(random_bytes(32));

         // Token expires in 24 hours
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->passwordResetRepository->createToken($user['id'], $token, $expires_at);
        $this->mailer->sendPasswordResetEmail($email, $user['name'], $token);

    }

    public function resetPassword(string $token, string $password): void
    {
        // Find token record
        $record = $this->passwordResetRepository->findByToken($token);

        if (!$record) {
            throw new \Exception("invalid_token");
        }

        // Check if token is expired
        if (strtotime($record['expires_at']) < time()) {
            throw new \Exception("token_expired");
        }

        // Validate new password strength
        if (strlen($password) < 8) {
            throw new \Exception("password_too_short");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new \Exception("password_no_uppercase");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new \Exception("password_no_lowercase");
        }
        if (!preg_match('/\d/', $password)) {
            throw new \Exception("password_no_number");
        }
        if (!preg_match('/[@$!%*?&]/', $password)) {
            throw new \Exception("password_no_special");
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepository->updatePassword($record['user_id'], $passwordHash);

        $this->passwordResetRepository->deleteByUserId($record['user_id']);

    }
}