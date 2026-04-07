<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\Feature\FeatureTestCase;
use App\Infrastructure\Database\UserRepository;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends FeatureTestCase
{
    #[Test]
    public function a_user_can_login_with_correct_credentials(): void
    {
        // 1. Setup: Create a real user in the test DB
        $userRepo = new UserRepository($this->pdo);
        $userRepo->create([
            'name' => 'Isaack Dev',
            'email' => 'isaack@test.com',
            'password_hash' => password_hash('correct-password', PASSWORD_BCRYPT)
        ]);

        // 2. Action: Simulate the POST request
        $response = $this->postJson('/auth/login', [
            'email' => 'isaack@test.com',
            'password' => 'correct-password'
        ]);

        // 3. Assert: Verify the response and session
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('success', $response['data']['status']);
        $this->assertTrue($_SESSION['authenticated']);
        $this->assertEquals('isaack@test.com', $_SESSION['db_user']['email']);
    }

    #[Test]
    public function it_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrong-pass'
        ]);

        $this->assertNotEquals(200, $response['status']);
        $this->assertEquals('error', $response['data']['status']);
        $this->assertEmpty($_SESSION);
    }
}