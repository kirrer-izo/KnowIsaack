<?php

namespace Infrastructure\Database;

use PDO;
use PDOException;

class UserRepository {
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data): void 
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (email, name, password_hash) VALUES (:email, :name, :password_hash)");
        $stmt->execute([
            'email' => $data['email'],
            'name' => $data['name'],
            'password_hash' => $data['password_hash']
        ]);
    }

    public function findByEmail(string $email): ?array 
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}