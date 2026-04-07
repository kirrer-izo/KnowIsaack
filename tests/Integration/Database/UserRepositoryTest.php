<?php

declare(strict_types=1);

namespace Tests\Integration\Database;


use App\Infrastructure\Database\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository($this->pdo);
    }

    #[Test]
    public function it_can_create_and_find_a_user_by_email(): void
    {
        $data = [
            'name' => 'Isaack Test',
            'email' => 'integration@test.com',
            'password_hash' => 'hashed_value'
        ];

        $this->repository->create($data);
        $user = $this->repository->findByEmail('integration@test.com');

        $this->assertNotNull($user);
        $this->assertEquals('Isaack Test', $user['name']);
        $this->assertFalse($user['email_verified']); // Default value check
    }

    #[Test]
    public function it_returns_null_for_non_existent_email(): void
    {
        $user = $this->repository->findByEmail('nonexistent@test.com');
        $this->assertNull($user);
    }

    #[Test]
    public function it_can_mark_email_as_verified(): void
    {
        // 1. Setup: Create a user manually
        $this->repository->create([
            'name' => 'Verify Me',
            'email' => 'verify@test.com',
            'password_hash' => 'hash'
        ]);
        $user = $this->repository->findByEmail('verify@test.com');

        // 2. Action: Verify them
        $this->repository->markEmailVerified((int)$user['id']);

        // 3. Assert: Fetch again and check
        $updatedUser = $this->repository->findById((int)$user['id']);
        $this->assertTrue($updatedUser['email_verified']);
    }

    #[Test]
    public function it_handles_pagination_and_search(): void
    {
        // Setup: Create two distinct users
        $this->repository->create(['name' => 'Alice', 'email' => 'alice@test.com', 'password_hash' => 'h']);
        $this->repository->create(['name' => 'Bob', 'email' => 'bob@test.com', 'password_hash' => 'h']);

        // Test search for Alice
        $result = $this->repository->getPaginatedUsers(1, 10, 'Alice');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Alice', $result['users'][0]['name']);
    }

    #[Test]
    public function it_deletes_user_and_related_data_in_transaction(): void
    {
        // 1. Setup
        $this->repository->create(['name' => 'DeleteMe', 'email' => 'delete@test.com', 'password_hash' => 'h']);
        $user = $this->repository->findByEmail('delete@test.com');
        $userId = (int)$user['id'];

        // 2. Action
        $success = $this->repository->deleteUser($userId);

        // 3. Assert
        $this->assertTrue($success);
        $this->assertNull($this->repository->findById($userId));
    }
}