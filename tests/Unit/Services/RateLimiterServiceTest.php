<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RateLimiterService;
use App\Infrastructure\Database\RateLimitRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;

class RateLimiterServiceTest extends TestCase
{
    /** @var RateLimitRepository|MockObject */
    private $repository;
    private $service;
    private int $maxAttempts = 5;
    private int $decayMinutes = 60;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RateLimitRepository::class);
        $this->service = new RateLimiterService(
            $this->repository,
            $this->maxAttempts,
            $this->decayMinutes
        );
    }

    #[Test]
    public function it_allows_and_creates_record_when_no_previous_attempts_exist(): void
    {
        $id = '127.0.0.1';
        $action = 'login';

        // 1. Mock that no record is found
        $this->repository->method('find')->willReturn(null);

        // 2. Verify that 'create' is called once
        $this->repository->expects($this->once())
            ->method('create')
            ->with($id, $action);

        $result = $this->service->attempt($id, $action);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_allows_and_increments_when_under_the_limit(): void
    {
        $id = '127.0.0.1';
        $action = 'login';

        // 1. Mock existing record with 2 attempts
        $this->repository->method('find')->willReturn([
            'attempts' => 2,
            'first_attempt_at' => date('Y-m-d H:i:s')
        ]);

        // 2. Verify that 'increment' is called
        $this->repository->expects($this->once())
            ->method('increment')
            ->with($id, $action);

        $result = $this->service->attempt($id, $action);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_blocks_attempts_when_limit_is_reached_within_the_window(): void
    {
        $id = '127.0.0.1';
        $action = 'login';

        // 1. Mock record at max attempts within the last 10 minutes
        $this->repository->method('find')->willReturn([
            'attempts' => 5,
            'first_attempt_at' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
        ]);

        // 2. Verify no increment or reset happens
        $this->repository->expects($this->never())->method('increment');
        $this->repository->expects($this->never())->method('reset');

        $result = $this->service->attempt($id, $action);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_resets_and_allows_when_limit_reached_but_window_has_passed(): void
    {
        $id = '127.0.0.1';
        $action = 'login';

        // 1. Mock record at max attempts but from 2 hours ago (outside 60min window)
        $this->repository->method('find')->willReturn([
            'attempts' => 5,
            'first_attempt_at' => date('Y-m-d H:i:s', strtotime('-120 minutes'))
        ]);

        // 2. Verify that 'reset' is called to start a new window
        $this->repository->expects($this->once())
            ->method('reset')
            ->with($id, $action);

        $result = $this->service->attempt($id, $action);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_clears_the_rate_limit_successfully(): void
    {
        $id = 'user@example.com';
        $action = 'login';

        // Verify that clear() simply delegates to the repository reset
        $this->repository->expects($this->once())
            ->method('reset')
            ->with($id, $action);

        $this->service->clear($id, $action);
    }
}