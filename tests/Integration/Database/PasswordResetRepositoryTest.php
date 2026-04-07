<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use Tests\Integration\IntegrationTestCase;
use App\Infrastructure\Database\PasswordResetRepository;
use App\Infrastructure\Database\UserRepository;
use PHPUnit\Framework\Attributes\Test;

class PasswordResetRepositoryTest extends IntegrationTestCase
{
    private PasswordResetRepository $repo;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new PasswordResetRepository($this->pdo);
        $userRepo = new UserRepository($this->pdo);

        $userRepo->create(['name' => 'Reset User', 'email' => 'r@test.com', 'password_hash' => 'h']);
        $user = $userRepo->findByEmail('r@test.com');
        $this->userId = (int)$user['id'];
    }

    #[Test]
    public function it_manages_reset_tokens_correctly(): void
    {
        $token = 'reset_token_abc';
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->repo->createToken($this->userId, $token, $expires);
        $record = $this->repo->findByToken($token);

        $this->assertNotNull($record);
        $this->repo->deleteByUserId($this->userId);
        $this->assertNull($this->repo->findByToken($token));
    }
}