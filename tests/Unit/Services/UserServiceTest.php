<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\UserService;
use App\Infrastructure\Database\UserRepository;
use App\Infrastructure\Database\EmailVerificationRepository;
use App\Infrastructure\Database\PasswordResetRepository;
use App\Infrastructure\Mail\ResendMailer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
// Add these imports for PHPUnit 11 Attributes
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class UserServiceTest extends TestCase
{
    /** @var UserRepository|MockObject */
    private $userRepo;
    /** @var EmailVerificationRepository|MockObject */
    private $emailRepo;
    /** @var ResendMailer|MockObject */
    private $mailer;
    /** @var PasswordResetRepository|MockObject */
    private $resetRepo;
    private $userService;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->emailRepo = $this->createMock(EmailVerificationRepository::class);
        $this->mailer = $this->createMock(ResendMailer::class);
        $this->resetRepo = $this->createMock(PasswordResetRepository::class);

        $this->userService = new UserService(
            $this->userRepo,
            $this->emailRepo,
            $this->mailer,
            $this->resetRepo
        );
    }

    // ─── REGISTRATION TESTS ──────────────────────────────────────────────────

    #[Test]
    #[DataProvider('invalidPasswordProvider')]
    public function registration_fails_with_invalid_passwords(string $password, string $expectedMessage): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->userService->register('Isaack', 'test@example.com', $password);
    }

    public static function invalidPasswordProvider(): array
    {
        return [
            ['short', 'password_too_short'],
            ['nouppercase1!', 'password_no_uppercase'],
            ['NOLOWERCASE1!', 'password_no_lowercase'],
            ['NoNumber!', 'password_no_number'],
            ['NoSpecial1', 'password_no_special'],
        ];
    }

    #[Test]
    public function registration_fails_if_email_is_already_taken(): void
    {
        $this->userRepo->method('findByEmail')
            ->willReturn(['id' => 1, 'email' => 'taken@example.com']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('email_taken');

        $this->userService->register('Isaack', 'taken@example.com', 'StrongPass123!');
    }

    #[Test]
    public function registration_completes_workflow_on_success(): void
    {
        $userData = ['name' => 'Isaack', 'email' => 'new@example.com', 'pass' => 'StrongPass123!'];

        $this->userRepo->expects($this->exactly(2))
            ->method('findByEmail')
            ->willReturnOnConsecutiveCalls(null, ['id' => 99, 'name' => $userData['name'], 'email' => $userData['email']]);

        $this->userRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($userData) {
                return $data['email'] === $userData['email'] && 
                       password_verify($userData['pass'], $data['password_hash']);
            }));

        $this->emailRepo->expects($this->once())->method('createToken')->with(99, $this->isType('string'));
        $this->mailer->expects($this->once())->method('sendVerificationEmail');

        $this->userService->register($userData['name'], $userData['email'], $userData['pass']);
    }

    // ─── LOGIN TESTS ─────────────────────────────────────────────────────────

    #[Test]
    public function login_throws_exception_on_invalid_credentials(): void
    {
        $this->userRepo->method('findByEmail')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('invalid_credentials');

        $this->userService->login('fake@example.com', 'anyPassword');
    }

    #[Test]
    public function login_returns_safe_user_array_on_success(): void
    {
        $password = 'Secret123!';
        $this->userRepo->method('findByEmail')->willReturn([
            'id' => 10,
            'name' => 'Isaack',
            'email' => 'isaack@example.com',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT)
        ]);

        $result = $this->userService->login('isaack@example.com', $password);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayNotHasKey('password_hash', $result);
        $this->assertEquals('Isaack', $result['name']);
    }

    // ─── EMAIL VERIFICATION TESTS ────────────────────────────────────────────

    #[Test]
    public function verifyEmail_throws_exception_on_expired_token(): void
    {
        $this->emailRepo->method('findByToken')->willReturn([
            'user_id' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('token_expired');

        $this->userService->verifyEmail('expired-token');
    }

    #[Test]
    public function verifyEmail_marks_user_verified_and_cleans_up(): void
    {
        $this->emailRepo->method('findByToken')->willReturn([
            'user_id' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);

        $this->userRepo->expects($this->once())->method('markEmailVerified')->with(1);
        $this->emailRepo->expects($this->once())->method('deleteByUserId')->with(1);

        $this->userService->verifyEmail('valid-token');
    }
}