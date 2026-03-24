<?php

namespace App\Controllers\Auth\User;

use App\Services\LoginActivityService;
use App\Services\RateLimiterService;
use App\Services\UserService;


class UserController{

private $userService;
private $rateLimiterService;
private $loginActivityService;

public function __construct(UserService $userService, RateLimiterService $rateLimiterService, LoginActivityService $loginActivityService)
{
    $this->userService = $userService;
    $this->rateLimiterService = $rateLimiterService;
    $this->loginActivityService = $loginActivityService;
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

public function handleForgotPasswordRequest(): void {
    $method = $_SERVER['REQUEST_METHOD'];
    match ($method) {
        'GET' => $this->showForgotPassword(),
        'POST' => $this->handleForgotPassword(),
    };
}

public function handleResetPasswordRequest(): void {
    $method = $_SERVER['REQUEST_METHOD'];
    match ($method) {
        'GET' => $this->showResetPassword(),
        'POST' => $this->handleResetPassword(),
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
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        header('Location: /auth/register?error=missing_fields');
        exit;
    }

    if ($password !== $confirm) {
        header('Location: /auth/register?error=passwords_do_not_match');
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
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $identifier = $email . '_' . $ip;
    if (!$this->rateLimiterService->attempt($identifier, 'login')) {
        header('Location: /auth/login?error=too_many_attempts');
        exit;
    }

    try {
        $user = $this->userService->login($email, $password);

         // Log the successful attempt
        $this->loginActivityService->recordSuccess($user['id'], $ip, $userAgent);
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['db_user'] = $user;
        header('Location: /admin');
    } catch (\Exception $e) {
        // Log the failed attempt (store the email even if it doesn't exist)
        $this->loginActivityService->recordFailure($email, $ip, $userAgent);
        
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

// Handle email verification from the link sent to the user's email
public function handleVerifyRequest(): void
{
    // Read the token from the URL query string e.g. /auth/verify?token=abc123
    $token = $_GET['token'] ?? '';

    // Reject if no token was provided
    if (empty($token)) {
        header('Location: /auth/login?error=no_token');
        exit;
    }

    try {
        // Validate token, check expiry and mark user as verified
        $this->userService->verifyEmail($token);
        header('Location: /auth/login?message=email_verified');
        exit;
    } catch (\Exception $e) {
        // Redirect with the slug thrown by UserService e.g. invalid_token, token_expired
        header('Location: /auth/login?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Show forgot password form (GET)
public function showForgotPassword(): void
{
    require __DIR__ . '/../../../../frontend/pages/forgot-password.html';
}

// Handle forgot password form submission (POST)
public function handleForgotPassword(): void
{
   $email = $_POST['email'] ?? '';
   
   if (empty($email)) {
    header('Location: /auth/forgot-password?error=missing_fields');
    exit;
   }

   $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!$this->rateLimiterService->attempt($ip, 'forgot_password')) {
        header('Location: /auth/login?error=too_many_attempts');
        exit;
    }

   try {
        $this->userService->forgotPassword($email);
    } catch (\Exception $e) {
        error_log("Forgot password failed: " . $e->getMessage());
    }
    
    header('Location: /auth/login?message=password_reset_sent');
    exit;
}

// Show reset password form(GET)
public function showResetPassword(): void 
{
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        header('Location: /auth/login?error=no_token');
        exit;
    }

    require __DIR__ . '/../../../../frontend/pages/reset-password.html';
}

// Handle reset password form submission (POST)
public function handleResetPassword(): void
{
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password)) {
        header('Location: /auth/reset-password?error=missing_fields&token=' . urlencode($token));
        exit;
    }

    if ($password !== $confirm) {
        header('Location: /auth/reset-password?error=passwords_do_not_match&token=' . urlencode($token));
        exit;
    }

    try {
        $this->userService->resetPassword($token, $password);
        header('Location: /auth/login?message=password_reset_success');
    } catch (\Exception $e) {
        header('Location: /auth/reset-password?error=' . urlencode($e->getMessage()) . '&token=' . urlencode($token));
    }

    exit;
}


}