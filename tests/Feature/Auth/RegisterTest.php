<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\Feature\FeatureTestCase;
use App\Infrastructure\Database\UserRepository;
use App\Infrastructure\Database\EmailVerificationRepository;
use PHPUnit\Framework\Attributes\Test;

class RegisterTest extends FeatureTestCase
{
    #[Test]
    public function a_user_can_register_successfully(): void
    {
        // 1. Action: Simulate a valid registration POST
        $response = $this->postJson('/auth/register', [
            'name' => 'Isaack New',
            'email' => 'newuser@test.com',
            'password' => 'SecurePass123!',
            'confirm_password' => 'SecurePass123!'
        ]);

        // 2. Assert: API Response
        $this->assertEquals(201, $response['status']);
        $this->assertEquals('success', $response['data']['status']);

        // 3. Assert: Database Persistence
        $userRepo = new UserRepository($this->pdo);
        $user = $userRepo->findByEmail('newuser@test.com');
        $this->assertNotNull($user);
        $this->assertEquals('Isaack New', $user['name']);

        // 4. Assert: Side Effects (Verification Token)
        $verifyRepo = new EmailVerificationRepository($this->pdo);
        $tokenRecord = $verifyRepo->findByUserId((int)$user['id']); 
        // Note: You might need a helper method in your repo to find by user_id for testing
        $this->assertNotNull($tokenRecord);
    }

    #[Test]
    public function registration_fails_if_passwords_do_not_match(): void
    {
        $response = $this->postJson('/auth/register', [
            'name' => 'Typo King',
            'email' => 'typo@test.com',
            'password' => 'password123',
            'confirm_password' => 'mismatch456'
        ]);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('passwords_do_not_match', $response['data']['message']);
        
        // Verify no user was created
        $userRepo = new UserRepository($this->pdo);
        $this->assertNull($userRepo->findByEmail('typo@test.com'));
    }

    #[Test]
    public function registration_fails_if_email_is_already_taken(): void
    {
        // Setup: Create an existing user
        $userRepo = new UserRepository($this->pdo);
        $userRepo->create([
            'name' => 'Original',
            'email' => 'taken@test.com',
            'password_hash' => 'hash'
        ]);

        // Action: Try to register with the same email
        $response = $this->postJson('/auth/register', [
            'name' => 'Imposter',
            'email' => 'taken@test.com',
            'password' => 'password123',
            'confirm_password' => 'password123'
        ]);

        $this->assertNotEquals(201, $response['status']);
        $this->assertEquals('error', $response['data']['status']);
    }
}