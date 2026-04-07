<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use Tests\Integration\IntegrationTestCase;
use App\Infrastructure\Database\RateLimitRepository;
use PHPUnit\Framework\Attributes\Test;

class RateLimitRepositoryTest extends IntegrationTestCase
{
    private RateLimitRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RateLimitRepository($this->pdo);
    }

    #[Test]
    public function it_can_create_and_find_a_rate_limit_record(): void
    {
        $identifier = '127.0.0.1';
        $action = 'login';

        $this->repository->create($identifier, $action);
        $record = $this->repository->find($identifier, $action);

        $this->assertNotNull($record);
        $this->assertEquals($identifier, $record['identifier']);
        $this->assertEquals(1, (int)$record['attempts']);
        $this->assertNotNull($record['first_attempt_at']);
    }

    #[Test]
    public function it_can_increment_attempts(): void
    {
        $identifier = '192.168.1.1';
        $action = 'contact_form';

        $this->repository->create($identifier, $action);
        $this->repository->increment($identifier, $action);
        
        $record = $this->repository->find($identifier, $action);
        $this->assertEquals(2, (int)$record['attempts']);
    }

    #[Test]
    public function it_can_reset_attempts_to_one(): void
    {
        $identifier = 'test-id';
        $action = 'reset-action';

        // Set up a record with multiple attempts
        $this->repository->create($identifier, $action);
        $this->repository->increment($identifier, $action);
        
        // Reset it
        $this->repository->reset($identifier, $action);

        $record = $this->repository->find($identifier, $action);
        $this->assertEquals(1, (int)$record['attempts']);
        
        // Verify timestamps were updated (optional, check if they are within 2 seconds of now)
        $this->assertLessThan(2, time() - strtotime($record['last_attempt_at']));
    }

    #[Test]
    public function it_can_delete_old_records_based_on_cutoff(): void
    {
        $identifier = 'clean-up-me';
        $action = 'test';
        
        // Create a record
        $this->repository->create($identifier, $action);
        
        // Scenario 1: Cutoff is in the future (should delete)
        $futureCutoff = date('Y-m-d H:i:s', strtotime('+1 minute'));
        $this->repository->deleteOld($identifier, $action, $futureCutoff);
        $this->assertNull($this->repository->find($identifier, $action));

        // Re-create for Scenario 2
        $this->repository->create($identifier, $action);
        
        // Scenario 2: Cutoff is in the past (should NOT delete)
        $pastCutoff = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $this->repository->deleteOld($identifier, $action, $pastCutoff);
        $this->assertNotNull($this->repository->find($identifier, $action));
    }

    #[Test]
    public function it_handles_pagination_and_search_filters(): void
    {
        // Setup: Create distinct records
        $this->repository->create('user_one', 'login');
        $this->repository->create('user_two', 'login');
        $this->repository->create('attacker', 'brute_force');

        // Test 1: Search for 'user'
        $result = $this->repository->getPaginatedRateLimits(1, 10, 'user');
        $this->assertEquals(2, $result['total']);

        // Test 2: Filter by action 'brute_force'
        $result = $this->repository->getPaginatedRateLimits(1, 10, null, 'brute_force');
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('attacker', $result['rate_limits'][0]['identifier']);
    }

    #[Test]
    public function it_returns_all_records_for_export_with_filtering(): void
    {
        $this->repository->create('export_1', 'login');
        $this->repository->create('export_2', 'api');

        $result = $this->repository->getAllRateLimitsForExport(null, 'api');
        
        $this->assertCount(1, $result);
        $this->assertEquals('export_2', $result[0]['identifier']);
    }
}