<?php

namespace App\Controllers\Auth\User;

use App\Controllers\BaseController;
use App\Services\LoginActivityService;
use App\Services\RateLimiterService;
use App\Services\RememberTokenService;
use App\Services\UserService;

class UserController extends BaseController {

    private $userService;
    private $rateLimiterService;
    private $loginActivityService;
    private $rememberTokenService;

    public function __construct(
        UserService $userService, 
        RateLimiterService $rateLimiterService, 
        LoginActivityService $loginActivityService, 
        RememberTokenService $rememberTokenService
    ) {
        $this->userService = $userService;
        $this->rateLimiterService = $rateLimiterService;
        $this->loginActivityService = $loginActivityService;
        $this->rememberTokenService = $rememberTokenService;
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
            $this->jsonResponse(['status' => 'error', 'message' => 'missing_fields'], 400);
            return;
        }

        if ($password !== $confirm) {
            $this->jsonResponse(['status' => 'error', 'message' => 'passwords_do_not_match'], 400);
            return;
        }

        try {
            $this->userService->register($name, $email, $password);
            $this->jsonResponse([
                'status' => 'success',
                'redirect' => '/auth/login?message=registered',
            ], 201);
            return;
        } catch (\Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
            return;
        }
    }

    public function handleLogin() : void {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'missing_fields'], 400);
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $identifier = $email . '_' . $ip;

        if (!$this->rateLimiterService->attempt($identifier, 'login')) {
            $this->jsonResponse(['status' => 'error', 'message' => 'too_many_attempts'], 429);
            return;
        }

        try {
            $user = $this->userService->login($email, $password);

            $this->rateLimiterService->clear($identifier, 'login');
            $this->loginActivityService->recordSuccess($user['id'], $ip, $userAgent);

            $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
            if ($remember) {
                $plainToken = $this->rememberTokenService->createToken($user['id']);
                setcookie('remember_token', $plainToken, time() + 86400 * 30, '/', '', false, true);
            }

            session_regenerate_id(true);
            $_SESSION['authenticated'] = true;
            $_SESSION['db_user'] = $user;

            $this->jsonResponse(['status' => 'success', 'redirect' => '/admin']);
            return;
        } catch (\Exception $e) {
            $this->loginActivityService->recordFailure($email, $ip, $userAgent);
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 401);
            return;
        }
    }

    public function logout() : void {
        if (isset($_COOKIE['remember_token'])) {
            $this->rememberTokenService->deleteToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        session_unset();
        session_destroy();
        
        $this->redirect('/auth/login?message=logged_out');
    }

    public function handleVerifyRequest(): void {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->redirect('/auth/login?error=no_token');
            return;
        }

        try {
            $this->userService->verifyEmail($token);
            $this->redirect('/auth/login?message=email_verified');
            return;
        } catch (\Exception $e) {
            $this->redirect('/auth/login?error=' . urlencode($e->getMessage()));
            return;
        }
    }

    public function showForgotPassword(): void {
        require __DIR__ . '/../../../../frontend/pages/forgot-password.html';
    }

    public function handleForgotPassword(): void {
       $email = $_POST['email'] ?? '';
       
       if (empty($email)) {
            $this->redirect('/auth/forgot-password?error=missing_fields');
            return;
       }

       $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->rateLimiterService->attempt($ip, 'forgot_password')) {
            $this->redirect('/auth/login?error=too_many_attempts');
            return;
        }

       try {
            $this->userService->forgotPassword($email);
        } catch (\Exception $e) {
            error_log("Forgot password failed: " . $e->getMessage());
        }
        
        $this->redirect('/auth/login?message=password_reset_sent');
    }

    public function showResetPassword(): void {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $this->redirect('/auth/login?error=no_token');
            return;
        }
        require __DIR__ . '/../../../../frontend/pages/reset-password.html';
    }

    public function handleResetPassword(): void {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($password)) {
            $this->redirect('/auth/reset-password?error=missing_fields&token=' . urlencode($token));
            return;
        }

        if ($password !== $confirm) {
            $this->redirect('/auth/reset-password?error=passwords_do_not_match&token=' . urlencode($token));
            return;
        }

        try {
            $this->userService->resetPassword($token, $password);
            $this->redirect('/auth/login?message=password_reset_success');
            return;
        } catch (\Exception $e) {
            $this->redirect('/auth/reset-password?error=' . urlencode($e->getMessage()) . '&token=' . urlencode($token));
            return;
        }
    }
}