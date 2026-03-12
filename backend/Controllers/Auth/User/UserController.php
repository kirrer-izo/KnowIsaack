<?php

namespace App\Controllers\Auth\User;

use App\Services\UserService;

class UserController{

private $userService;

public function __construct(UserService $userService)
{
    $this->userService = $userService;
}

public function handleRegisterRequest(): void {
   
    $method = $_SERVER['REQUEST_METHOD'];
    
    match ($method) {
        'GET' => $this->showRegister(),
        'POST' => $this->handleRegister(), 
    };
}

public function handleLoginRequest(): void {
   
    $method = $_SERVER['REQUEST_METHOD'];
    
    match ($method) {
        'GET' => $this->showLogin(),
        'POST' => $this->handleLogin(), 
    };
}

public function showLogin() : void {
    require __DIR__ . '/../../../../frontend/pages/login.html';
}

public function showRegister() : void {
    require __DIR__ . '/../../../../frontend/pages/register.html';
}

public function handleRegister() : void {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        header('Location: /auth/register?error=missing_fields');
        exit;
    }

    try {
        $this->userService->register($name, $email, $password);
        header('Location: /auth/login?message=registered');
    } catch (\Exception $e) {
        header('Location: /auth/register?error=' . urlencode($e->getMessage()));
    }

    exit;
}

public function handleLogin() : void {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: /auth/login?error=missing_fields');
        exit;
    }

    try {
        $user = $this->userService->login($email, $password);
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['db_user'] = $user;
        header('Location: /admin');
    } catch (\Exception $e) {
        header('Location: /auth/login?error=' . urlencode($e->getMessage()));
    }

    exit;

}

public function logout() : void {
    session_unset();
    session_destroy();
    
    header('Location: /auth/login?message=logged_out');
    exit;
}

}