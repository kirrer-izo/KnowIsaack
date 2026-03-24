<?php

namespace App\Services;

use App\Infrastructure\Database\LoginActivityRepository;

class LoginActivityService
{
    private LoginActivityRepository $repository;

    public function __construct(LoginActivityRepository $repository)
    {
        $this->repository = $repository;
    }

    //  Record a successful login.
    public function recordSuccess(int $userId, string $ip, string $userAgent): void
    {
        $this->repository->create($userId, null, $ip, $userAgent, true);
    }

    // Record a failed login attempt. Stores the email that was attempted (may not exist in the database).

    public function recordFailure(string $attemptedEmail, string $ip, string $userAgent): void
    {
        $this->repository->create(null, $attemptedEmail, $ip, $userAgent, false);
    }
}