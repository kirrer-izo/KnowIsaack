<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LoginActivityService;
use App\Infrastructure\Database\LoginActivityRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;

class LoginActivityServiceTest extends TestCase
{
    /** @var LoginActivityRepository|MockObject */
    private $repository;
    private $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LoginActivityRepository::class);
        $this->service = new LoginActivityService($this->repository);
    }

    #[Test]
    public function it_records_a_successful_login_activity(): void
    {
        $userId = 1;
        $ip = '192.168.1.1';
        $userAgent = 'Mozilla/5.0';

        // Verify repository receives the correct data for a success event
        // Success events use a userId and set attempted_email to null
        $this->repository->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo($userId),
                $this->isNull(),
                $this->equalTo($ip),
                $this->equalTo($userAgent),
                $this->isTrue()
            );

        $this->service->recordSuccess($userId, $ip, $userAgent);
    }

    #[Test]
    public function it_records_a_failed_login_attempt(): void
    {
        $email = 'malicious@example.com';
        $ip = '10.0.0.5';
        $userAgent = 'Bot/1.0';

        // Verify repository receives the correct data for a failure event
        // Failure events use an email string and set userId to null
        $this->repository->expects($this->once())
            ->method('create')
            ->with(
                $this->isNull(),
                $this->equalTo($email),
                $this->equalTo($ip),
                $this->equalTo($userAgent),
                $this->isFalse()
            );

        $this->service->recordFailure($email, $ip, $userAgent);
    }
}