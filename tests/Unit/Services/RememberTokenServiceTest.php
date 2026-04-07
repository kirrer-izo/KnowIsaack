<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RememberTokenService;
use App\Infrastructure\Database\RememberTokenRepository;
use App\Infrastructure\Database\UserRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;

class RememberTokenServiceTest extends TestCase
{
    /** @var RememberTokenRepository|MockObject */
    private $tokenRepo;
    /** @var UserRepository|MockObject */
    private $userRepo;
    private $service;

    protected function setUp(): void
    {
        $this->tokenRepo = $this->createMock(RememberTokenRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);

        $this->service = new RememberTokenService(
            $this->tokenRepo,
            $this->userRepo
        );
    }

    #[Test]
    public function it_creates_and_stores_a_hashed_token(): void
    {
        $userId = 123;

        // Verify that the repository's create method is called exactly once
        $this->tokenRepo->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo($userId),
                $this->callback(function (string $hashedToken) {
                    // Check if the token is a valid SHA-256 hash (64 hex characters)
                    return strlen($hashedToken) === 64 && ctype_xdigit($hashedToken);
                }),
                $this->callback(function (string $expiresAt) {
                    // Verify expiry is set in the future (around 30 days)
                    return strtotime($expiresAt) > time() + (29 * 24 * 3600);
                })
            );

        $plainToken = $this->service->createToken($userId);

        $this->assertIsString($plainToken);
        $this->assertEquals(64, strlen($plainToken)); // bin2hex(32 bytes)
    }

    #[Test]
    public function it_returns_null_if_token_record_is_not_found(): void
    {
        $this->tokenRepo->method('findByToken')->willReturn(null);

        $result = $this->service->validateToken('non-existent-token');

        $this->assertNull($result);
    }

    #[Test]
    public function it_deletes_token_and_returns_null_if_expired(): void
    {
        $plainToken = 'some-token';
        $hashedToken = hash('sha256', $plainToken);

        $this->tokenRepo->method('findByToken')->willReturn([
            'user_id' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);

        // Expect the service to cleanup the expired token
        $this->tokenRepo->expects($this->once())
            ->method('deleteByToken')
            ->with($hashedToken);

        $result = $this->service->validateToken($plainToken);

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_user_data_if_token_is_valid_and_not_expired(): void
    {
        $plainToken = 'valid-token';
        $hashedToken = hash('sha256', $plainToken);
        $userData = ['id' => 1, 'name' => 'Isaack', 'email' => 'isaack@example.com'];

        $this->tokenRepo->method('findByToken')->willReturn([
            'user_id' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))
        ]);

        $this->userRepo->method('findById')->with(1)->willReturn($userData);

        $result = $this->service->validateToken($plainToken);

        $this->assertEquals($userData, $result);
    }

    #[Test]
    public function it_deletes_the_token_correctly(): void
    {
        $plainToken = 'token-to-delete';
        $hashedToken = hash('sha256', $plainToken);

        $this->tokenRepo->expects($this->once())
            ->method('deleteByToken')
            ->with($hashedToken);

        $this->service->deleteToken($plainToken);
    }
}