<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use Tests\Integration\IntegrationTestCase;
use App\Infrastructure\Database\EmailVerificationRepository;
use App\Infrastructure\Database\UserRepository;
use PHPUnit\Framework\Attributes\Test;

class EmailVerificationRepositoryTest extends IntegrationTestCase
{
    private EmailVerificationRepository $repo;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EmailVerificationRepository($this->pdo);
        $userRepo = new UserRepository($this->pdo);

        // Create a parent user record
        $userRepo->create(['name' => 'Verify User', 'email' => 'v@test.com', 'password_hash' => 'h']);
        $user = $userRepo->findByEmail('v@test.com');
        $this->userId = (int)$user['id'];
    }

    #[Test]
    public function it_can_create_and_find_verification_token(): void
    {
        $token = 'verify_token_123';
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->repo->createToken($this->userId, $token, $expires);
        $record = $this->repo->findByToken($token);

        $this->assertNotNull($record);
        $this->assertEquals($this->userId, (int)$record['user_id']);
        $this->assertEquals($token, $record['token']);
    }

    #[Test]
    public function it_returns_null_for_non_existent_token(): void
    {
        $this->assertNull($this->repo->findByToken('invalid'));
    }

    #[Test]
    public function it_cleans_up_all_tokens_for_a_user(): void
    {
        $this->repo->createToken($this->userId, 't1', date('Y-m-d H:i:s', strtotime('+1 hour')));
        $this->repo->createToken($this->userId, 't2', date('Y-m-d H:i:s', strtotime('+1 hour')));

        $this->repo->deleteByUserId($this->userId);

        $this->assertNull($this->repo->findByToken('t1'));
        $this->assertNull($this->repo->findByToken('t2'));
    }
}