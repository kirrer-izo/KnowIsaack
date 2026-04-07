<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use Tests\Integration\IntegrationTestCase;
use App\Infrastructure\Database\LoginActivityRepository;
use App\Infrastructure\Database\UserRepository;
use PHPUnit\Framework\Attributes\Test;

class LoginActivityRepositoryTest extends IntegrationTestCase
{
    private LoginActivityRepository $repo;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new LoginActivityRepository($this->pdo);
        $userRepo = new UserRepository($this->pdo);

        $userRepo->create(['name' => 'Audit User', 'email' => 'audit@test.com', 'password_hash' => 'h']);
        $user = $userRepo->findByEmail('audit@test.com');
        $this->userId = (int)$user['id'];
    }

    #[Test]
    public function it_records_success_and_failure_correctly(): void
    {
        // 1. Success (with User ID)
        $this->repo->create($this->userId, null, '127.0.0.1', 'Mozilla', true);
        
        // 2. Failure (with attempted email)
        $this->repo->create(null, 'attacker@test.com', '192.168.1.1', 'Bot', false);

        $recent = $this->repo->getRecentForUser($this->userId, 1);
        $this->assertCount(1, $recent);
        $this->assertTrue((bool)$recent[0]['success']);
    }

    #[Test]
    public function it_verifies_time_based_failed_counts(): void
    {
        // Create a failure right now
        $this->repo->create(null, 'fail@test.com', '1.1.1.1', 'Agent', false);
        
        $count = $this->repo->countFailedLast24Hours();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    #[Test]
    public function it_executes_complex_7_day_summary_sql(): void
    {
        // Record a mix of activity
        $this->repo->create($this->userId, null, '1.1.1.1', 'Agent', true);
        $this->repo->create(null, 'bad@test.com', '1.1.1.1', 'Agent', false);

        $summary = $this->repo->getLast7DaysSummary();

        $this->assertCount(7, $summary); // Ensure the PHP loop filled the missing days
        $today = $summary[6]; // Last entry in the filled array
        $this->assertEquals(date('Y-m-d'), $today['day']);
        $this->assertEquals(1, $today['success']);
        $this->assertEquals(1, $today['failed']);
    }

    #[Test]
    public function it_handles_pagination_search_and_joins(): void
    {
        $this->repo->create($this->userId, null, '123.123.123.123', 'CustomAgent', true);

        // Search for the specific IP
        $result = $this->repo->getPaginatedLogs(1, 10, '123.123.123.123');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Audit User', $result['logs'][0]['user_name']); // Verifies the LEFT JOIN worked
        $this->assertEquals('CustomAgent', $result['logs'][0]['user_agent']);
    }
}