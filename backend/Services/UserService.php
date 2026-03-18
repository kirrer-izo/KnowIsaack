<?php

namespace App\Services;

use App\Infrastructure\Database\UserRepository;

class UserService {
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(string $name, string $email, string $password): void
    {   
        // Checking Password Strength
        if (strlen($password) < 8) {
            throw new \Exception("password_too_short");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new \Exception("password_no_uppercase");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new \Exception("password_no_lowercase");
        }
        if (!preg_match('/\d/', $password)) {
            throw new \Exception("password_no_number");
        }
        if (!preg_match('/[@$!%*?&]/', $password)) {
            throw new \Exception("password_no_special");
        }

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new \Exception("email_taken");
        }


        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new \Exception("invalid_credentials");
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];
    }
}