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
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new \Exception("Email already in use");
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
            throw new \Exception("Invalid credentials");
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];
    }
}