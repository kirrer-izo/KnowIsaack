<?php

namespace App\Services;

use App\Infrastructure\Database\RememberTokenRepository;
use App\Infrastructure\Database\UserRepository;

class RememberTokenService
{
    private  $rememberTokenRepository;
    private  $userRepository;

    public function __construct(RememberTokenRepository $rememberTokenRepository, UserRepository $userRepository)
    {
        $this->rememberTokenRepository = $rememberTokenRepository;
        $this->userRepository = $userRepository;
    }

    //  Generate a secure token, store it, and return the plain token .

    public function createToken(int $userId): string
    {
        // 32 bytes = 64 hex characters
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->rememberTokenRepository->create($userId, $hashedToken, $expiresAt);
        return $token;
    }

    //  Validate a plain token and return the associated user if valid and not expired.
    public function validateToken(string $token): ?array
    {
        $hashedToken = hash('sha256', $token);
        $record = $this->rememberTokenRepository->findByToken($hashedToken);

        if (!$record) {
            return null;
        }

        // Check expiry
        if (strtotime($record['expires_at']) < time()) {
            $this->rememberTokenRepository->deleteByToken($hashedToken);
            return null;
        }

        // Return user data
        return $this->userRepository->findById($record['user_id']);
    }

    // Delete a token.
    public function deleteToken(string $token): void
    {
        $hashedToken = hash('sha256', $token);
        $this->rememberTokenRepository->deleteByToken($hashedToken);
    }
}