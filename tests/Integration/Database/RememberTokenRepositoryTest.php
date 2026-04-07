<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Infrastructure\Database\RememberTokenRepository;
use App\Infrastructure\Database\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

class RememberTokenRepositoryTest extends IntegrationTestCase
{
    private RememberTokenRepository $tokenRepo;
    private UserRepository $userRepo;
    private int $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenRepo = new RememberTokenRepository($this->pdo);
        $this->userRepo = new UserRepository($this->pdo);

        // Setup: We MUST create a user first to avoid Foreign Key violations
        $this->userRepo->create([
            'name' => 'Token Tester',
            'email' => 'token@test.com',
            'password_hash' => 'hash'
        ]);
        $user = $this->userRepo->findByEmail('token@test.com');
        $this->testUserId = (int)$user['id'];
    }

    #[Test]
    public function it_can_persist_and_retrieve_a_hashed_token(): void
    {
        $hashedToken = hash('sha256', 'raw-persistent-token');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->tokenRepo->create($this->testUserId, $hashedToken, $expiresAt);
        $record = $this->tokenRepo->findByToken($hashedToken);

        $this->assertNotNull($record);
        $this->assertEquals($this->testUserId, (int)$record['user_id']);
        $this->assertEquals($hashedToken, $record['token']);
    }

    #[Test]
    public function it_can_delete_a_specific_token_on_logout(): void
    {
        $token = hash('sha256', 'logout-token');
        $this->tokenRepo->create($this->testUserId, $token, date('Y-m-d H:i:s', strtotime('+1 day')));

        $this->tokenRepo->deleteByToken($token);
        
        $this->assertNull($this->tokenRepo->findByToken($token));
    }

    #[Test]
    public function it_can_purge_all_tokens_for_a_user_during_security_reset(): void
    {
        // Create multiple tokens for the same user (simulating login on different devices)
        $this->tokenRepo->create($this->testUserId, hash('sha256', 'device-1'), date('Y-m-d H:i:s', strtotime('+1 day')));
        $this->tokenRepo->create($this->testUserId, hash('sha256', 'device-2'), date('Y-m-d H:i:s', strtotime('+1 day')));

        $this->tokenRepo->deleteByUserId($this->testUserId);

        $this->assertNull($this->tokenRepo->findByToken(hash('sha256', 'device-1')));
        $this->assertNull($this->tokenRepo->findByToken(hash('sha256', 'device-2')));
    }

    #[Test]
    public function it_successfully_deletes_expired_tokens_using_database_time(): void
    {
        $validToken = hash('sha256', 'valid-token');
        $expiredToken = hash('sha256', 'expired-token');

        // Insert one valid token
        $this->tokenRepo->create($this->testUserId, $validToken, date('Y-m-d H:i:s', strtotime('+1 hour')));
        
        // Insert one token that expired 1 hour ago
        $this->tokenRepo->create($this->testUserId, $expiredToken, date('Y-m-d H:i:s', strtotime('-1 hour')));

        // Action: The DB logic "DELETE WHERE expires_at < NOW()"
        $this->tokenRepo->deleteExpired();

        $this->assertNotNull($this->tokenRepo->findByToken($validToken), "Valid token should still exist.");
        $this->assertNull($this->tokenRepo->findByToken($expiredToken), "Expired token should have been purged.");
    }
}